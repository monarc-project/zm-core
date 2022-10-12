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
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\ObjectCategory;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Service\Traits\TreeStructureTrait;
use Monarc\Core\Table\AnrObjectCategoryTable;
use Monarc\Core\Table\ModelTable;
use Monarc\Core\Table\ObjectCategoryTable;
use Monarc\Core\Table\MonarcObjectTable;

class ObjectCategoryService
{
    use TreeStructureTrait;
    use PositionUpdateTrait;

    private ObjectCategoryTable $objectCategoryTable;

    private MonarcObjectTable $monarcObjectTable;

    private AnrObjectCategoryTable $anrObjectCategoryTable;

    private ModelTable $modelTable;

    private UserSuperClass $connectedUser;

    public function __construct(
        ObjectCategoryTable $objectCategoryTable,
        MonarcObjectTable $monarcObjectTable,
        AnrObjectCategoryTable $anrObjectCategoryTable,
        ModelTable $modelTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->objectCategoryTable = $objectCategoryTable;
        $this->monarcObjectTable = $monarcObjectTable;
        $this->anrObjectCategoryTable = $anrObjectCategoryTable;
        $this->modelTable = $modelTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getObjectCategoryData(int $id): array
    {
        /** @var ObjectCategory $objectCategory */
        $objectCategory = $this->objectCategoryTable->findById($id);

        $objectCategoryData = [
            'id' => $objectCategory->getId(),
            'root' => $objectCategory->getRoot() !== null
                ? ['id' => $objectCategory->getRoot()->getId()]
                : null,
            'parent' => $objectCategory->getParent() !== null
                ? [
                    'id' => $objectCategory->getParent()->getId(),
                    'label1' => $objectCategory->getParent()->getLabel(1),
                    'label2' => $objectCategory->getParent()->getLabel(2),
                    'label3' => $objectCategory->getParent()->getLabel(3),
                    'label4' => $objectCategory->getParent()->getLabel(4),
                ]
                : null,
            'label1' => $objectCategory->getLabel(1),
            'label2' => $objectCategory->getLabel(2),
            'label3' => $objectCategory->getLabel(3),
            'label4' => $objectCategory->getLabel(4),
            'position' => $objectCategory->getPosition(),
            'previous' => null,
            'implicitPosition' => 1,
        ];

        if ($objectCategory->getPosition() > 1) {
            $maxPosition = $this->objectCategoryTable
                ->findMaxPosition($objectCategory->getImplicitPositionRelationsValues());
            if ($objectCategory->getPosition() >= $maxPosition) {
                $objectCategoryData['implicitPosition'] = 2;
            } else {
                $objectCategoryData['implicitPosition'] = 3;
                $previousObjectCategory = $this->objectCategoryTable->findPreviousCategory($objectCategory);
                if ($previousObjectCategory !== null) {
                    $objectCategoryData['previous'] = $previousObjectCategory->getId();
                }
            }
        }

        return $objectCategoryData;
    }

    public function getList(FormattedInputParams $formattedInputParams)
    {
        $this->prepareCategoryFilter($formattedInputParams);
        $includeChildren = empty($formattedInputParams->getFilterFor('parentId')['value'])
            || empty($formattedInputParams->getFilterFor('lock')['value']);
        $includeParents = empty($formattedInputParams->getFilterFor('catid')['value']);

        $model = null;
        if (!empty($formattedInputParams->getFilterFor('model'))) {
            $modelId = $formattedInputParams->getFilterFor('model')['value'];
            /** @var Model $model */
            $model = $this->modelTable->findById($modelId);
        }

        $categoriesData = [];
        /** @var ObjectCategory[] $objectCategories */
        $objectCategories = $this->objectCategoryTable->findByParams($formattedInputParams);
        foreach ($objectCategories as $objectCategory) {
            $categoriesData[$objectCategory->getId()] = $this->getPreparedObjectCategoryData(
                $objectCategory,
                $includeChildren,
                $model
            );
            if ($includeParents
                && $objectCategory->getParent() !== null
                && !isset($categoriesData[$objectCategory->getParent()->getId()])
            ) {
                $categoriesData[$objectCategory->getParent()->getId()] = $this->getPreparedObjectCategoryData(
                    $objectCategory->getParent(),
                    false,
                    $model
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
            $this->linkRootCategoryToAnr($objectCategory);
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

    private function getPreparedObjectCategoryData(
        ObjectCategorySuperClass $objectCategory,
        bool $includeChildren = true,
        ?Model $model = null
    ): array {
        $result = [
            'id' => $objectCategory->getId(),
            'label1' => $objectCategory->getLabel(1),
            'label2' => $objectCategory->getLabel(2),
            'label3' => $objectCategory->getLabel(3),
            'label4' => $objectCategory->getLabel(4),
            'position' => $objectCategory->getPosition(),
        ];

        /*
         * If the $model parameter is passed we include the objects list linked to the categories.
         * This allows to link the new objects to the model.
         */
        if ($model !== null) {
            foreach ($objectCategory->getObjects() as $object) {
                $result['objects'][] = [
                    'uuid' => $object->getUuid(),
                    'name1' => $object->getName(1),
                    'name2' => $object->getName(2),
                    'name3' => $object->getName(3),
                    'name4' => $object->getName(4),
                    'isLinkedToAnr' => $object->hasAnrLink($model->getAnr()),
                ];
            }
        }

        if ($includeChildren) {
            foreach ($objectCategory->getChildren() as $childCategory) {
                $result['child'] = $this->getPreparedObjectCategoryData($childCategory, true, $model);
            }
        }

        return $result;
    }

    private function unlinkCategoryFromAnrs(ObjectCategorySuperClass $objectCategory): void
    {
        foreach ($objectCategory->getAnrObjectCategories() as $anrObjectCategory) {
            $this->anrObjectCategoryTable->remove($anrObjectCategory, false);
        }
    }

    private function unlinkCategoryFromAnrsOrLinkItsRoot(ObjectCategorySuperClass $objectCategory): void
    {
        foreach ($objectCategory->getAnrObjectCategories() as $anrObjectCategory) {
            if ($objectCategory->getRoot()
                && $objectCategory->getRoot()->hasAnrLink($anrObjectCategory->getAnr())
            ) {
                $this->anrObjectCategoryTable->remove($anrObjectCategory, false);
            } else {
                $anrObjectCategory->setCategory($objectCategory->getRoot())
                    ->setUpdater($this->connectedUser->getEmail());
                $this->anrObjectCategoryTable->save($anrObjectCategory, false);
            }
        }
    }

    /**
     * Link every Anr object that is under the root category, or it's children.
     */
    private function linkRootCategoryToAnr(ObjectCategorySuperClass $objectCategory): void
    {
        if (!$objectCategory->isCategoryRoot()) {
            return;
        }

        $objects = $this->monarcObjectTable->getObjectsUnderRootCategory($objectCategory);
        foreach ($objects as $object) {
            foreach ($object->getAnrs() as $anr) {
                if (!$objectCategory->hasAnrLink($anr)) {
                    $anrObjectCategory = (new AnrObjectCategory())
                        ->setAnr($anr)
                        ->setCategory($objectCategory)
                        ->setCreator($this->connectedUser->getEmail());
                    $this->updatePositions($anrObjectCategory, $this->anrObjectCategoryTable);

                    $this->anrObjectCategoryTable->save($anrObjectCategory, false);
                }
            }
        }
    }
}
