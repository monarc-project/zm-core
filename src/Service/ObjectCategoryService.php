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
use Monarc\Core\Table\AnrObjectCategoryTable;
use Monarc\Core\Table\ModelTable;
use Monarc\Core\Table\ObjectCategoryTable;
use Monarc\Core\Table\MonarcObjectTable;

class ObjectCategoryService
{
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
            'parent' => $objectCategory->hasParent()
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

        /*
         * Fetch only root categories and populates their children in case if no filter by parentId or categoryId.
         */
        if ($includeChildren && empty($formattedInputParams->getFilterFor('catid')['value'])) {
            $formattedInputParams->setFilterValueFor('parent', null);
        }

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
            $categoriesData[] = $this->getPreparedObjectCategoryData(
                $objectCategory,
                $includeChildren,
                $model
            );
        }

        return $categoriesData;
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
            $objectCategory->setRoot($parent->getRootCategory());
        }

        $this->updatePositions($objectCategory, $this->objectCategoryTable, $data);

        $this->objectCategoryTable->save($objectCategory);

        return $objectCategory;
    }

    public function update(int $id, array $data): ObjectCategory
    {
        /** @var ObjectCategory $objectCategory */
        $objectCategory = $this->objectCategoryTable->findById($id);

        $objectCategory->setLabels($data)->setUpdater($this->connectedUser->getEmail());

        /*
         * Perform operations to update the category links with anrs (only root categories are linked to anr).
         * 1 condition. The case when the category's parent is changed. Before the category could be root or a child.
         * 2 condition. The case when the category becomes root (parent removed), and before it had a parent.
         */
        if (!empty($data['parent'])
            && (!$objectCategory->hasParent() || (int)$data['parent'] !== $objectCategory->getParent()->getId())
        ) {
            /** @var ObjectCategory $parentCategory */
            $parentCategory = $this->objectCategoryTable->findById((int)$data['parent']);

            $previousRootCategory = $objectCategory->getRootCategory();
            $isRootCategoryBeforeUpdated = $objectCategory->isCategoryRoot();
            $hasRootCategoryChanged = $objectCategory->hasParent()
                && $parentCategory->getRootCategory()->getId() !== $objectCategory->getRootCategory()->getId();

            $objectCategory->setParent($parentCategory)->setRoot($parentCategory->getRootCategory());

            /* Unlink the root from Anr in case if the category was root before or the category's root is changed
             * and there are no more objects left under the previous root. */
            if ($isRootCategoryBeforeUpdated
                || ($hasRootCategoryChanged && !$previousRootCategory->hasObjectsLinkedDirectlyOrToChildCategories())
            ) {
                $this->unlinkCategoryFromAnrs($previousRootCategory);
            }

            if ($isRootCategoryBeforeUpdated || $hasRootCategoryChanged) {
                /* Link the new root to Anrs, if not linked. */
                $this->linkTheCategoryRootToAnrs($objectCategory);
                /* Update the category children with the new root. */
                $this->updateRootOfChildrenTree($objectCategory);
            }
        } elseif (empty($data['parent']) && $objectCategory->hasParent()) {
            $previousRootCategory = $objectCategory->getRootCategory();
            $objectCategory->setParent(null)->setRoot(null);

            /* If in the previous category's root or its children no more objects, the root has to be unlinked. */
            if ($previousRootCategory !== null
                && !$previousRootCategory->hasObjectsLinkedDirectlyOrToChildCategories()
            ) {
                $this->unlinkCategoryFromAnrs($previousRootCategory);
            }

            /* The category become root now, before it had a parent and was not root. */
            $this->linkTheCategoryRootToAnrs($objectCategory);

            $this->updateRootOfChildrenTree($objectCategory);
        }

        $this->objectCategoryTable->save($objectCategory);

        return $objectCategory;
    }

    public function delete(int $id): void
    {
        /** @var ObjectCategory $objectCategory */
        $objectCategory = $this->objectCategoryTable->findById($id);

        $this->objectCategoryTable->beginTransaction();
        try {
            /* Remove all the relations with ANRs and adjust the overall positions. */
            $anrsWhereTheCategoryIsRoot = [];
            foreach ($objectCategory->getAnrObjectCategories() as $anrObjectCategory) {
                $this->shiftPositionsForRemovingEntity($anrObjectCategory, $this->anrObjectCategoryTable);
                $maxPosition = 1;
                foreach ($anrObjectCategory->getAnr()->getAnrObjectCategories() as $anrBasedObjectCategory) {
                    if ($anrBasedObjectCategory->getId() !== $anrObjectCategory->getId()) {
                        $maxPosition = $anrBasedObjectCategory->getPosition();
                    }
                }
                $anrsWhereTheCategoryIsRoot[] = [
                    'anr' => $anrObjectCategory->getAnr(),
                    'maxPosition' => $maxPosition,
                ];
                $this->anrObjectCategoryTable->remove($anrObjectCategory, true);
            }

            /* Set the removing category's parent for all its children. */
            foreach ($objectCategory->getChildren() as $childCategory) {
                $childCategory
                    ->setParent($objectCategory->getParent())
                    ->setUpdater($this->connectedUser->getEmail());

                /* If the removing category is root, then all its direct children become root. */
                if ($objectCategory->isCategoryRoot()) {
                    $childCategory->setRoot(null);
                    foreach ($anrsWhereTheCategoryIsRoot as $anrWhereTheCategoryIsRoot) {
                        $anrObjectCategory = (new AnrObjectCategory())
                            ->setCategory($childCategory)
                            ->setAnr($anrWhereTheCategoryIsRoot['anr'])
                            ->setPosition(++$anrWhereTheCategoryIsRoot['maxPosition'])
                            ->setCreator($this->connectedUser->getEmail());

                        $this->anrObjectCategoryTable->save($anrObjectCategory, false);
                    }
                }

                $this->objectCategoryTable->save($childCategory, false);
            }

            $this->objectCategoryTable->remove($objectCategory);
        } catch (\Throwable $e) {
            $this->objectCategoryTable->rollback();
        }

        $this->objectCategoryTable->commit();
    }

    private function updateRootOfChildrenTree(ObjectCategory $objectCategory): void
    {
        foreach ($objectCategory->getChildren() as $childCategory) {
            $childCategory->setRoot($objectCategory->getRootCategory());
            $this->objectCategoryTable->save($childCategory, false);

            $this->updateRootOfChildrenTree($childCategory);
        }
    }

    private function prepareCategoryFilter(FormattedInputParams $formattedInputParams): void
    {
        $lockFilter = $formattedInputParams->getFilterFor('lock');
        $parentIdFilter = $formattedInputParams->getFilterFor('parentId');
        $categoryIdFilter = $formattedInputParams->getFilterFor('catid');

        $isParentIdFilterEmpty = empty($parentIdFilter['value']);
        if (!empty($categoryIdFilter['value'])) {
            $excludeCategoriesIds = [$categoryIdFilter['value']];
            if (!$isParentIdFilterEmpty) {
                $excludeCategoriesIds[] = $parentIdFilter['value'];
                $formattedInputParams->setFilterValueFor('parent', $parentIdFilter['value']);
            }
            $formattedInputParams->setFilterFor('id', [
                'value' => $excludeCategoriesIds,
                'operator' => Comparison::NIN,
            ]);
        } elseif (!$isParentIdFilterEmpty) {
            $formattedInputParams->setFilterValueFor('parent', $parentIdFilter['value']);
        } elseif (empty($lockFilter['value'])) {
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

        if ($includeChildren) {
            foreach ($objectCategory->getChildren() as $childCategory) {
                $result['child'][] = $this->getPreparedObjectCategoryData($childCategory, true, $model);
            }
        }

        /*
         * If the $model parameter is passed we include the objects list linked to the categories.
         * This allows to link the new objects to the model.
         */
        if ($model !== null) {
            $result['objects'] = [];
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

        return $result;
    }

    private function unlinkCategoryFromAnrs(ObjectCategorySuperClass $objectCategory): void
    {
        foreach ($objectCategory->getAnrObjectCategories() as $anrObjectCategory) {
            $this->anrObjectCategoryTable->remove($anrObjectCategory, false);
        }
    }

    /**
     * Links every Anr of objects that are under the root category, or it's children.
     */
    private function linkTheCategoryRootToAnrs(ObjectCategorySuperClass $objectCategory): void
    {
        foreach ($objectCategory->getObjectsRecursively() as $object) {
            foreach ($object->getAnrs() as $anr) {
                if (!$objectCategory->getRootCategory()->hasAnrLink($anr)) {
                    $anrObjectCategory = (new AnrObjectCategory())
                        ->setAnr($anr)
                        ->setCategory($objectCategory->getRootCategory())
                        ->setCreator($this->connectedUser->getEmail());
                    /* The category supposed to positioned at the end. */
                    $this->updatePositions($anrObjectCategory, $this->anrObjectCategoryTable);

                    $this->anrObjectCategoryTable->save($anrObjectCategory, false);
                }
            }
        }
    }
}
