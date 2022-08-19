<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\Common\Collections\Expr\Comparison;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity\AnrObjectCategory;
use Monarc\Core\Model\Entity\ObjectCategory;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Service\Traits\TreeStructureTrait;
use Monarc\Core\Table\AnrObjectCategoryTable;
use Monarc\Core\Table\ObjectCategoryTable;
use Monarc\Core\Table\MonarcObjectTable;

// TODO: refactor along with object service...
class ObjectCategoryService
{
    use TreeStructureTrait;
    use PositionUpdateTrait;

    protected $anrTable;//required for autopositionning of anrobjectcategories
    protected $dependencies = ['root', 'parent', 'anr'];//required for autopositionning

    private ObjectCategoryTable $objectCategoryTable;

    private MonarcObjectTable $monarcObjectTable;

    private AnrObjectCategoryTable $anrObjectCategoryTable;

    private UserSuperClass $connectedUser;

    public function __construct(
        ObjectCategoryTable $objectCategoryTable,
        MonarcObjectTable $monarcObjectTable,
        AnrObjectCategoryTable $anrObjectCategoryTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->objectCategoryTable = $objectCategoryTable;
        $this->monarcObjectTable = $monarcObjectTable;
        $this->anrObjectCategoryTable = $anrObjectCategoryTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    // TODO: fix this method
    public function getEntity($id)
    {
        $entity = $this->objectCategoryTable->findById($id);

        $entity['previous'] = null;
        if ($entity['position'] == 1) {
            $entity['implicitPosition'] = 1;
        } else {
            $pos = $this->get('table')->getRepository()->createQueryBuilder('t')->select('count(t.id)');
            if (empty($entity['parent'])) {
                $pos = $pos->where('t.parent IS NULL');
            } else {
                $pos = $pos->where('t.parent = :parent')
                    ->setParameter(':parent', $entity['parent']->id);
            }

            if ($entity['anr']) {
                $pos->andWhere('t.anr = :anr')->setParameter(':anr', $entity['anr']->id);
            }

            $pos = $pos->getQuery()->getSingleScalarResult();
            if ($entity['position'] >= $pos) {
                $entity['implicitPosition'] = 2;
            } else {
                $entity['implicitPosition'] = 3;
                // Autre chose ?te
                $prev = $this->get('table')->getRepository()->createQueryBuilder('t')->select('t.id');
                if (empty($entity['parent'])) {
                    $prev = $prev->where('t.parent IS NULL');
                } else {
                    $prev = $prev->where('t.parent = :parent')
                        ->setParameter(':parent', $entity['parent']->id);
                }
                if ($entity['anr']) {
                    $prev->andWhere('t.anr = :anr')->setParameter(':anr', $entity['anr']->id);
                }
                $prev = $prev->andWhere('t.position = :pos')
                    ->setParameter(':pos', $entity['position'] - 1)
                    ->getQuery()->getSingleScalarResult();
                $entity['previous'] = $prev;
            }
        }

        return $entity;
    }

    public function getList(FormattedInputParams $formattedInputParams)
    {
        $this->prepareCategoryFilter($formattedInputParams);
        $includeChildren = empty($formattedInputParams->getFilterFor('parentId')['value'])
            || empty($formattedInputParams->getFilterFor('lock')['value']);
        $includeParents = empty($formattedInputParams->getFilterFor('catid')['value']);

        $categoriesData = [];
        /** @var ObjectCategory[] $objectCategories */
        $objectCategories = $this->objectCategoryTable->findByParams($formattedInputParams);
        foreach ($objectCategories as $objectCategory) {
            $categoriesData[$objectCategory->getId()] = $this->getPreparedObjectCategoryData(
                $objectCategory,
                $includeChildren
            );
            if ($includeParents
                && $objectCategory->getParent() !== null
                && !isset($categoriesData[$objectCategory->getParent()->getId()])
            ) {
                $categoriesData[$objectCategory->getParent()->getId()] = $this->getPreparedObjectCategoryData(
                    $objectCategory->getParent(),
                    false
                );
            }
        }

        return array_values($categoriesData);
    }

    public function getCount(): int
    {
        return $this->objectCategoryTable->countAll();
    }

    public function create(array $data): ObjectCategory
    {
        $objectCategory = (new ObjectCategory())
            ->setLabels($data)
            ->setCreator($this->connectedUser->getEmail());

        if (!empty($data['parent'])) {
            /** @var ObjectCategory $parent */
            $parent = $this->objectCategoryTable->findById((int)$data['parent']);
            $objectCategory->setParent($parent);
            $objectCategory->setRoot($parent->getRoot());
        }

        $this->updatePositions($objectCategory, $this->objectCategoryTable, $data);

        $this->objectCategoryTable->save($objectCategory);

        return $objectCategory;
    }

    public function update(int $id, array $data): ObjectCategory
    {
        /** @var ObjectCategory $objectCategory */
        $objectCategory = $this->objectCategoryTable->findById($id);

        $objectCategory->setLabels($data)
            ->setUpdater($this->connectedUser->getEmail());

        $isRootCategoryBeforeUpdated = $objectCategory->isCategoryRoot();
        $previousRootCategory = $objectCategory->getRoot();

        /* Perform operations to update the category links with anrs (only root categories are linked to anr). */
        if (!empty($data['parent'])
            && ($objectCategory->getParent() === null
                || (int)$data['parent'] !== $objectCategory->getParent()->getId()
            )
        ) {
            /** @var ObjectCategory $parent */
            $parent = $this->objectCategoryTable->findById((int)$data['parent']);
            $objectCategory->setParent($parent)->setRoot($parent->getRoot() ?? $parent);
            if ($isRootCategoryBeforeUpdated) {
                /* The category was root, now we will set parent, and it's not root anymore. */
                $this->unlinkCategoryFromAnrsOrLinkItsRoot($objectCategory);
            }
        } elseif (empty($data['parent']) && $objectCategory->getParent() !== null) {
            $objectCategory->setParent(null)->setRoot(null);
            /* The category become root now, before it had a parent and was not root. */
            $this->linkCategoryToAnr($objectCategory);
            if ($previousRootCategory !== null
                && !$this->monarcObjectTable->hasObjectsUnderRootCategoryExcludeObject($previousRootCategory)
            ) {
                $this->unlinkCategoryFromAnrs($previousRootCategory);
            }
        }

        $this->objectCategoryTable->save($objectCategory);

        return $objectCategory;
    }

    public function delete(int $id): void
    {
        /** @var ObjectCategory $objectCategory */
        $objectCategory = $this->objectCategoryTable->findById($id);
        /* Set the removing category's parent for all its children. */
        foreach ($objectCategory->getChildren() as $childCategory) {
            $childCategory
                ->setParent($objectCategory->getParent())
                ->setUpdater($this->connectedUser->getEmail());
            $this->objectCategoryTable->save($childCategory, false);
        }

        $this->objectCategoryTable->remove($objectCategory);
    }

    private function prepareCategoryFilter(FormattedInputParams $formattedInputParams): void
    {
        $lockFilter = $formattedInputParams->getFilterFor('lock');
        $parentIdFilter = $formattedInputParams->getFilterFor('parentId');
        $categoryIdFilter = $formattedInputParams->getFilterFor('catid');

        if (!empty($categoryIdFilter['value'])) {
            $excludeCategoriesIds = [$categoryIdFilter['value']];
            if (!empty($parentIdFilter['value'])) {
                $excludeCategoriesIds[] = $parentIdFilter['value'];
                $formattedInputParams->setFilterValueFor('parent', $parentIdFilter['value']);
            }
            $formattedInputParams->setFilterFor('id', [
                'value' => $excludeCategoriesIds,
                'operator' => Comparison::NIN,
            ]);
        }

        if (!empty($lockFilter['value']) || empty($parentIdFilter['value'])) {
            $formattedInputParams->setFilterValueFor('parent', null);
        }
    }

    private function getPreparedObjectCategoryData(ObjectCategory $objectCategory, bool $includeChildren = true): array
    {
        $result = [
            'id' => $objectCategory->getId(),
            'label1' => $objectCategory->getLabel(1),
            'label2' => $objectCategory->getLabel(2),
            'label3' => $objectCategory->getLabel(3),
            'label4' => $objectCategory->getLabel(4),
            'position' => $objectCategory->getPosition(),
        ];

        if ($includeChildren) {
            foreach ($objectCategory->getChildren() as $childCategory) {
                $result['child'] = $this->getPreparedObjectCategoryData($childCategory);
            }
        }

        return $result;
    }

    // TODO: ....

    /**
     * Patches the Library Category
     * @param int $categoryId The category ID to patch
     * @param array $data The new data
     * @return mixed|null The resulting object
     */
    public function patchLibraryCategory($categoryId, $data)
    {
        $anrId = $data['anr'];

        /** @var ObjectCategorySuperClass $anrObjectCategory */
        $anrObjectCategory = $this->anrObjectCategoryTable
            ->getEntityByFields(['anr' => $anrId, 'category' => $categoryId])[0];
        //$anrObjectCategory->setDbAdapter($anrObjectCategoryTable->getDb());

        //Specific handle of previous data
        if (isset($data['previous'])) {//we get a position but we need an id
            $id = $this->anrObjectCategoryTable->getRepository()->createQueryBuilder('t')
                ->select('t.id')
                ->where('t.anr = :anrid')
                ->andWhere('t.position = :pos')
                ->setParameters([':anrid' => $anrId, ':pos' => $data['previous']])
                ->getQuery()->getSingleScalarResult();

            $data['previous'] = $id ? $id : null;
        }

        $anrObjectCategory->exchangeArray($data);
        $this->setDependencies($anrObjectCategory, ['anr']);

        return $this->anrObjectCategoryTable->save($anrObjectCategory);
    }

    public function unlinkCategoryFromAnrs(ObjectCategory $objectCategory): void
    {
        foreach ($objectCategory->getAnrObjectCategories() as $anrObjectCategory) {
            $this->anrObjectCategoryTable->remove($anrObjectCategory, false);
        }
    }

    protected function unlinkCategoryFromAnrsOrLinkItsRoot(ObjectCategorySuperClass $objectCategory): void
    {
        foreach ($objectCategory->getAnrObjectCategories() as $anrObjectCategory) {
            if ($objectCategory->getRoot() && $objectCategory->getRoot()->hasAnrLink($anrObjectCategory->getAnr())) {
                $this->anrObjectCategoryTable->remove($anrObjectCategory, false);
            } else {
                $anrObjectCategory->setCategory($objectCategory->getRoot())
                    ->setUpdater($this->connectedUser->getEmail());
                $this->anrObjectCategoryTable->save($anrObjectCategory, false);
            }
        }
    }

    /**
     * We need to link every Anr of Objects that are under the root category, or it's children.
     */
    protected function linkCategoryToAnr(ObjectCategorySuperClass $objectCategory): void
    {
        $anrs = [];
        $objects = $this->monarcObjectTable->getObjectsUnderRootCategory($objectCategory);
        foreach ($objects as $object) {
            foreach ($object->getAnrs() as $anr) {
                if (isset($anrs[$anr->getId()])
                    || $objectCategory->hasAnrLink($anr)
                ) {
                    continue;
                }

                $anrObjectCategory = (new AnrObjectCategory())
                    ->setAnr($anr)
                    ->setCategory($objectCategory);
                $this->updatePositions($anrObjectCategory, $this->anrObjectCategoryTable);

                $this->anrObjectCategoryTable->save($anrObjectCategory, false);

                $anrs[$anr->getId()] = true;
            }
        }
    }
}
