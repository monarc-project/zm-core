<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\Common\Collections\Expr\Comparison;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Entity;
use Monarc\Core\Service\Traits;
use Monarc\Core\Table;
use Monarc\Core\Model\Table\RolfTagTable;

class ObjectService
{
    use Traits\PositionUpdateTrait;

    public const MODE_OBJECT_EDIT = 'edit';
    public const MODE_KNOWLEDGE_BASE = 'bdc';
    public const MODE_ANR = 'anr';

    private Table\MonarcObjectTable $monarcObjectTable;

    private Table\AssetTable $assetTable;

    private Table\ModelTable $modelTable;

    private Table\ObjectCategoryTable $objectCategoryTable;

    private Table\ObjectObjectTable $objectObjectTable;

    private Table\InstanceTable $instanceTable;

    private RolfTagTable $rolfTagTable;

    private Table\InstanceRiskOpTable $instanceRiskOpTable;

    private InstanceRiskOpService $instanceRiskOpService;

    private Entity\UserSuperClass $connectedUser;

    private bool $isObjectListFilterPrepared = false;

    public function __construct(
        Table\MonarcObjectTable $monarcObjectTable,
        Table\AssetTable $assetTable,
        Table\ModelTable $modelTable,
        Table\ObjectCategoryTable $objectCategoryTable,
        Table\ObjectObjectTable $objectObjectTable,
        Table\InstanceTable $instanceTable,
        RolfTagTable $rolfTagTable,
        Table\InstanceRiskOpTable $instanceRiskOpTable,
        InstanceRiskOpService $instanceRiskOpService,
        ConnectedUserService $connectedUserService
    ) {
        $this->monarcObjectTable = $monarcObjectTable;
        $this->assetTable = $assetTable;
        $this->modelTable = $modelTable;
        $this->objectCategoryTable = $objectCategoryTable;
        $this->objectObjectTable = $objectObjectTable;
        $this->instanceTable = $instanceTable;
        $this->rolfTagTable = $rolfTagTable;
        $this->instanceRiskOpTable = $instanceRiskOpTable;
        $this->instanceRiskOpService = $instanceRiskOpService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $formattedInputParams): array
    {
        if (!$this->areAnrObjectsValid($formattedInputParams)) {
            return [];
        }
        $this->prepareObjectsListFilter($formattedInputParams);

        /** @var Entity\MonarcObject[] $objects */
        $objects = $this->monarcObjectTable->findByParams($formattedInputParams);

        $result = [];
        foreach ($objects as $object) {
            $result[] = $this->getPreparedObjectData($object);
        }

        return $result;
    }

    public function getCount(FormattedInputParams $formattedInputParams): int
    {
        if (!$this->areAnrObjectsValid($formattedInputParams)) {
            return 0;
        }

        $this->prepareObjectsListFilter($formattedInputParams);

        return $this->monarcObjectTable->countByParams($formattedInputParams);
    }

    public function getObjectData(string $uuid, FormattedInputParams $formattedInputParams): array
    {
        /** @var Entity\MonarcObject $object */
        $object = $this->monarcObjectTable->findByUuid($uuid);

        $objectData = $this->getPreparedObjectData($object);

        $filteredData = $formattedInputParams->getFilter();

        /* Object edit dialog scenario. */
        if ($this->isEditObjectMode($filteredData)) {
            return $objectData;
        }

        $objectData['children'] = $this->getChildrenTreeList($object);
        $objectData['risks'] = $this->getRisks($object);
        $objectData['oprisks'] = $this->getRisksOp($object);
        $objectData['parents'] = $this->getDirectParents($object);

        if ($this->isAnrObjectMode($filteredData)) {
            /* Anr/model object in a library scenario.*/
            $anr = $this->getValidatedAnr($filteredData, $object);

            $instances = $this->instanceTable->findByAnrAndObject($anr, $object);

            $objectData['replicas'] = [];
            foreach ($instances as $instance) {
                $instanceHierarchy = $instance->getHierarchyArray();

                $names = [
                    'name1' => $anr->getLabel(1),
                    'name2' => $anr->getLabel(2) ? : $anr->getLabel(1),
                    'name3' => $anr->getLabel(3) ? : $anr->getLabel(1),
                    'name4' => $anr->getLabel(4) ? : $anr->getLabel(1),
                ];
                foreach ($instanceHierarchy as $instanceData) {
                    $names['name1'] .= ' > ' . $instanceData['name1'];
                    $names['name2'] .= ' > ' . ($instanceData['name2'] ? : $instanceData['name1']);
                    $names['name3'] .= ' > ' . ($instanceData['name3'] ? : $instanceData['name1']);
                    $names['name4'] .= ' > ' . ($instanceData['name4'] ? : $instanceData['name1']);
                }
                $names['id'] = $instance->getId();
                $objectData['replicas'][] = $names;
            }

            return $objectData;
        }

        /* Knowledge base scenario. */
        $anrIds = [];
        foreach ($object->getAnrs() as $item) {
            $anrIds[] = $item->getId();
        }

        $objectData['replicas'] = [];
        if (!empty($anrIds)) {
            $models = $this->modelTable->findByAnrIds($anrIds);

            $modelsData = [];
            foreach ($models as $model) {
                $modelsData[] = array_merge([
                    'id' => $model->getId(),
                ], $model->getLabels());
            }

            $objectData['replicas'] = $modelsData;
        }

        return $objectData;
    }

    public function getLibraryTreeStructure(Entity\Anr $anr): array
    {
        $result = [];
        foreach ($anr->getObjectCategories() as $objectCategory) {
            $result[] = $this->getCategoriesAndObjectsTreeList($objectCategory, $anr);
        }

        /* Places uncategorized objects. */
        $uncategorizedObjectsData = [];
        foreach ($anr->getObjects() as $object) {
            if (!$object->hasCategory()) {
                $uncategorizedObjectsData[] = $this->getPreparedObjectData($object, true);
            }
        }
        if (!empty($uncategorizedObjectsData)) {
            $result[] = Entity\ObjectCategorySuperClass::getUndefinedCategoryData($uncategorizedObjectsData);
        }

        return $result;
    }

    public function create(array $data, bool $saveInDb = true): Entity\MonarcObject
    {
        /** @var Entity\Asset $asset */
        $asset = $this->assetTable->findByUuid($data['asset']);

        $this->validateAssetAndDataOnCreate($asset, $data);

        $monarcObject = (new Entity\MonarcObject())
            ->setLabels($data)
            ->setNames($data)
            ->setAsset($asset)
            ->setScope((int)$data['scope'])
            ->setMode((int)$data['mode'])
            ->setCreator($this->connectedUser->getEmail());
        if (!empty($data['category'])) {
            $this->validateAndSetCategory($monarcObject, $data);
        }
        if (!empty($data['rolfTag']) && !$asset->isPrimary()) {
            $rolfTag = $this->rolfTagTable->findById($data['rolfTag']);
            $monarcObject->setRolfTag($rolfTag);
        }

        /*
         * The objects positioning inside of categories was dropped from the UI, only kept in the db and passed data.
         * New objects are always placed at the end.
         */
        $this->updatePositions($monarcObject, $this->monarcObjectTable);

        $this->monarcObjectTable->save($monarcObject, $saveInDb);

        /** @var Entity\MonarcObject $monarcObject */
        return $monarcObject;
    }

    public function update(string $uuid, array $data): Entity\MonarcObject
    {
        /** @var Entity\MonarcObject $monarcObject */
        $monarcObject = $this->monarcObjectTable->findByUuid($uuid);

        $this->validateObjectAndDataOnUpdate($monarcObject, $data);

        $monarcObject
            ->setLabels($data)
            ->setNames($data)
            ->setMode((int)$data['mode'])
            ->setUpdater($this->connectedUser->getEmail());

        $this->adjustInstancesValidateAndSetRolfTag($monarcObject, $data);
        $this->validateAndSetCategory($monarcObject, $data);

        $this->monarcObjectTable->save($monarcObject);

        return $monarcObject;
    }

    public function delete(string $uuid): void
    {
        /** @var Entity\MonarcObject $monarcObject */
        $monarcObject = $this->monarcObjectTable->findByUuid($uuid);

        /* If the object is linked to anrs, it has to be detached first. */
        foreach ($monarcObject->getAnrs() as $linkedAnr) {
            $this->detachObjectFromAnr($monarcObject->getUuid(), $linkedAnr);
        }

        $this->monarcObjectTable->remove($monarcObject);
    }

    public function duplicate(array $data, ?Entity\Anr $anr): Entity\MonarcObject
    {
        /** @var Entity\MonarcObject $monarcObjectToCopy */
        $monarcObjectToCopy = $this->monarcObjectTable->findByUuid($data['id']);

        $newMonarcObject = $this->getObjectCopy($monarcObjectToCopy, $anr);

        $this->duplicateObjectChildren($monarcObjectToCopy, $newMonarcObject, $anr);

        $this->monarcObjectTable->save($newMonarcObject);

        return $newMonarcObject;
    }

    public function attachObjectToAnr(string $objectUuid, Entity\Anr $anr): Entity\MonarcObject
    {
        /** @var Entity\MonarcObject $monarcObject */
        $monarcObject = $this->monarcObjectTable->findByUuid($objectUuid);

        $this->validateAndAttachObjectToAnr($monarcObject, $anr);

        return $monarcObject;
    }

    public function attachCategoryObjectsToAnr(int $categoryId, Entity\Anr $anr): void
    {
        /** @var Entity\ObjectCategory $objectCategory */
        $objectCategory = $this->objectCategoryTable->findById($categoryId);

        $this->attachCategoryAndItsChildrenObjectsToAnr($objectCategory, $anr);
    }

    public function detachObjectFromAnr(string $objectUuid, Entity\Anr $anr): void
    {
        /** @var Entity\MonarcObject $monarcObject */
        $monarcObject = $this->monarcObjectTable->findByUuid($objectUuid);

        $monarcObject->removeAnr($anr);

        /* Remove from the library object composition (affects all the linked analysis), shift positions. */
        foreach ($monarcObject->getParentsLinks() as $objectParentLink) {
            $this->shiftPositionsForRemovingEntity($objectParentLink, $this->objectObjectTable);
            $this->objectObjectTable->remove($objectParentLink, false);
        }

        /* If no more objects under its root category, the category need to be unlinked from the analysis. */
        if ($monarcObject->hasCategory()) {
            /** @var Entity\ObjectCategory $rootCategory */
            $rootCategory = $monarcObject->getCategory()->getRootCategory();
            if (!$this->monarcObjectTable->hasObjectsUnderRootCategoryExcludeObject($rootCategory, $monarcObject)) {
                $rootCategory->removeAnrLink($anr);
                $this->objectCategoryTable->save($rootCategory, false);
            }
        }

        /* Remove the directly linked instances and shift their positions. */
        foreach ($monarcObject->getInstances() as $instance) {
            $this->shiftPositionsForRemovingEntity($instance, $this->instanceTable);
            $instance->removeAllInstanceRisks()->removeAllOperationalInstanceRisks();
            $monarcObject->removeInstance($instance);
            $this->instanceTable->remove($instance, false);
        }

        $this->monarcObjectTable->save($monarcObject);
    }

    public function getParentsInAnr(Entity\Anr $anr, string $uuid)
    {
        /** @var Entity\MonarcObject $object */
        $object = $this->monarcObjectTable->findByUuid($uuid);

        if (!$object->hasAnrLink($anr)) {
            throw new Exception(sprintf('The object is not linked to the anr ID "%d"', $anr->getId()), 412);
        }

        $directParents = [];
        foreach ($object->getParentsLinks() as $parentLink) {
            /** @var Entity\MonarcObject $parentObject */
            $parentObject = $parentLink->getParent();
            if ($parentObject->hasAnrLink($anr)) {
                $directParents[] = array_merge([
                    'uuid' => $parentLink->getParent()->getUuid(),
                    'linkid' => $parentLink->getId(),
                ], $parentLink->getParent()->getLabels(), $parentLink->getParent()->getNames());
            }
        }

        return $directParents;
    }

    private function getPreparedObjectData(Entity\MonarcObject $object, bool $objectOnly = false): array
    {
        $result = array_merge([
            'uuid' => $object->getUuid(),
            'mode' => $object->getMode(),
            'scope' => $object->getScope(),
            'position' => $object->getPosition(),
        ], $object->getLabels(), $object->getNames());

        if (!$objectOnly) {
            $result['category'] = $object->getCategory() !== null
                ? array_merge([
                    'id' => $object->getCategory()->getId(),
                    'position' => $object->getCategory()->getPosition(),
                ], $object->getCategory()->getLabels())
                : Entity\ObjectCategorySuperClass::getUndefinedCategoryData([]);
            $result['asset'] = array_merge([
                'uuid' => $object->getAsset()->getUuid(),
                'code' => $object->getAsset()->getCode(),
                'type' => $object->getAsset()->getType(),
                'mode' => $object->getAsset()->getMode(),
            ], $object->getAsset()->getLabels());
            $result['rolfTag'] = $object->getRolfTag() === null ? null : array_merge([
                'id' => $object->getRolfTag()->getId(),
                'code' => $object->getRolfTag()->getCode(),
            ], $object->getRolfTag()->getLabels());
        }

        return $result;
    }

    private function attachCategoryAndItsChildrenObjectsToAnr(
        Entity\ObjectCategory $objectCategory,
        Entity\Anr $anr
    ): void {
        foreach ($objectCategory->getObjects() as $monarcObject) {
            $this->validateAndAttachObjectToAnr($monarcObject, $anr);
        }

        foreach ($objectCategory->getChildren() as $childCategory) {
            $this->attachCategoryAndItsChildrenObjectsToAnr($childCategory, $anr);
        }
    }

    private function validateAndAttachObjectToAnr(Entity\MonarcObject $monarcObject, Entity\Anr $anr): void
    {
        $anr->getModel()->validateObjectAcceptance($monarcObject);

        $this->linkObjectAndItsCategoryToAnr($monarcObject, $anr);

        $this->monarcObjectTable->save($monarcObject);
    }

    private function linkObjectAndItsCategoryToAnr(Entity\MonarcObject $monarcObject, Entity\Anr $anr): void
    {
        $monarcObject->addAnr($anr);

        /** Link root category to the anr, if not linked. */
        if ($monarcObject->hasCategory()) {
            /** @var Entity\ObjectCategory $rootCategory */
            $rootCategory = $monarcObject->getCategory()->getRootCategory();
            if (!$rootCategory->hasAnrLink($anr)) {
                $rootCategory->addAnrLink($anr);
                $this->objectCategoryTable->save($rootCategory, false);
            }
        }

        foreach ($monarcObject->getChildren() as $childObject) {
            $this->linkObjectAndItsCategoryToAnr($childObject, $anr);

            $this->monarcObjectTable->save($childObject, false);
        }
    }

    private function validateAssetAndDataOnCreate(Entity\Asset $asset, array $data): void
    {
        if ($data['mode'] === Entity\ObjectSuperClass::MODE_GENERIC && $asset->isModeSpecific()) {
            throw new Exception('It is forbidden to have a generic object linked to a specific asset', 412);
        }

        if ($data['scope'] === Entity\ObjectSuperClass::SCOPE_GLOBAL && $asset->isPrimary()) {
            throw new Exception('It is forbidden to create a global object linked to a primary asset', 412);
        }
    }

    private function validateObjectAndDataOnUpdate(Entity\MonarcObject $monarcObject, array $data): void
    {
        if (isset($data['scope']) && $data['scope'] !== $monarcObject->getScope()) {
            throw new Exception('The scope of an existing object can not be changed.', 412);
        }

        if (isset($data['asset']) && $data['asset'] !== $monarcObject->getAsset()->getUuid()) {
            throw new Exception('Asset type of an existing object can not be changed.', 412);
        }

        if (isset($data['mode'])
            && $data['mode'] !== $monarcObject->getMode()
            && !$this->checkModeIntegrity($monarcObject)
        ) {
            $message = $monarcObject->isModeGeneric()
                ? 'The object can not have specific mode because one of its parents is in generic mode.'
                : 'The object can not have generic mode because one of its children is in specific mode.';

            throw new Exception($message, 412);
        }
    }

    private function adjustInstancesValidateAndSetRolfTag(Entity\MonarcObject $monarcObject, array $data): void
    {
        /* Set operational risks to specific only when RolfTag was set before, and another RolfTag or null is set. */
        $isRolfTagUpdated = false;
        if (!empty($data['rolfTag']) && (
            $monarcObject->getRolfTag() === null || (int)$data['rolfTag'] !== $monarcObject->getRolfTag()->getId()
        )) {
            /** @var Entity\RolfTag $rolfTag */
            $rolfTag = $this->rolfTagTable->findById((int)$data['rolfTag']);
            $monarcObject->setRolfTag($rolfTag);

            /* A new RolfTag is linked, set all linked operational risks to specific, new risks should be created. */
            $isRolfTagUpdated = true;
        } elseif (empty($data['rolfTag']) && $monarcObject->getRolfTag() !== null) {
            $monarcObject->setRolfTag(null);

            /* Set all linked operational risks to specific, no new risks to create. */
            $isRolfTagUpdated = true;
        }

        $this->updateInstancesAndOperationalRisks($monarcObject, $isRolfTagUpdated);
    }

    private function updateInstancesAndOperationalRisks(Entity\MonarcObject $monarcObject, bool $isRolfTagUpdated): void
    {
        foreach ($monarcObject->getInstances() as $instance) {
            $instance->setNames($monarcObject->getNames())
                ->setLabels($monarcObject->getLabels());
            $this->instanceTable->save($instance, false);

            if (!$monarcObject->getAsset()->isPrimary()) {
                continue;
            }

            $rolfRisksIdsToOperationalInstanceRisks = [];
            foreach ($instance->getOperationalInstanceRisks() as $operationalInstanceRisk) {
                $rolfRiskId = $operationalInstanceRisk->getRolfRisk()->getId();
                $rolfRisksIdsToOperationalInstanceRisks[$rolfRiskId] = $operationalInstanceRisk;
                if ($isRolfTagUpdated) {
                    $operationalInstanceRisk->setIsSpecific(true);
                    $this->instanceRiskOpTable->save($operationalInstanceRisk, false);
                }
            }

            if ($isRolfTagUpdated && $monarcObject->getRolfTag() !== null) {
                foreach ($monarcObject->getRolfTag()->getRisks() as $rolfRisk) {
                    if (isset($rolfRisksIdsToOperationalInstanceRisks[$rolfRisk->getId()])) {
                        /* Restore the specific to false if the operational risk is linked to another risk. */
                        $rolfRisksIdsToOperationalInstanceRisks[$rolfRisk->getId()]->setIsSpecific(false);
                        $this->instanceRiskOpTable->save(
                            $rolfRisksIdsToOperationalInstanceRisks[$rolfRisk->getId()],
                            false
                        );
                    } else {
                        /* Recreate the risk with scales if it did not exist before. */
                        $this->instanceRiskOpService->createInstanceRiskOpWithScales(
                            $instance,
                            $monarcObject,
                            $rolfRisk
                        );
                    }
                }
            }
        }
    }

    private function validateAndSetCategory(Entity\MonarcObject $monarcObject, array $data): void
    {
        $hasCategory = $monarcObject->hasCategory();
        if (!$hasCategory || (int)$data['category'] !== $monarcObject->getCategory()->getId()) {
            /** @var Entity\ObjectCategory $category */
            $category = $this->objectCategoryTable->findById((int)$data['category']);

            /* If the root category is changed and no more objects linked, the link with anrs should be dropped. */
            if ($hasCategory && !$category->areRootCategoriesEqual($monarcObject->getCategory())) {
                $this->validateAndRemoveRootCategoryLinkIfNoObjectsLinked($monarcObject);
            }
            /* Create the links with anrs for the new root category if they do not exist. */
            if (!$hasCategory
                || $category->getRootCategory()->getId() !== $monarcObject->getCategory()->getRootCategory()->getId()
            ) {
                foreach ($monarcObject->getAnrs() as $anr) {
                    $this->objectCategoryTable->save($category->getRootCategory()->addAnrLink($anr), false);
                }
            }

            $monarcObject->setCategory($category);
        }
    }

    private function checkModeIntegrity(Entity\MonarcObject $monarcObject): bool
    {
        if ($monarcObject->isModeGeneric()) {
            $objectsList = $this->getParentsTreeList($monarcObject);
            $field = 'parents';
        } else {
            $objectsList = $this->getChildrenTreeList($monarcObject);
            $field = 'children';
        }

        return $this->checkModeIntegrityRecursive($objectsList, $monarcObject->getMode(), $field);
    }

    private function checkModeIntegrityRecursive(array $objectsList, int $mode, string $field): bool
    {
        foreach ($objectsList as $objectData) {
            if ($objectData['mode'] === $mode || (
                !empty($objectData[$field]) && !$this->checkModeIntegrityRecursive($objectData[$field], $mode, $field)
            )) {
                return false;
            }
        }

        return true;
    }

    private function getObjectCopy(Entity\MonarcObject $monarcObjectToCopy, ?Entity\Anr $anr): Entity\MonarcObject
    {
        $labelsNamesSuffix = ' copy #' . time();
        /** @var Entity\MonarcObject $newMonarcObject */
        $newMonarcObject = (new Entity\MonarcObject())
            ->setCategory($monarcObjectToCopy->getCategory())
            ->setAsset($monarcObjectToCopy->getAsset())
            ->setLabels([
                'label1' => $monarcObjectToCopy->getLabelCleanedFromCopy(1) . $labelsNamesSuffix,
                'label2' => $monarcObjectToCopy->getLabelCleanedFromCopy(2) . $labelsNamesSuffix,
                'label3' => $monarcObjectToCopy->getLabelCleanedFromCopy(3) . $labelsNamesSuffix,
                'label4' => $monarcObjectToCopy->getLabelCleanedFromCopy(4) . $labelsNamesSuffix,
            ])
            ->setNames([
                'name1' => $monarcObjectToCopy->getNameCleanedFromCopy(1) . $labelsNamesSuffix,
                'name2' => $monarcObjectToCopy->getNameCleanedFromCopy(2) . $labelsNamesSuffix,
                'name3' => $monarcObjectToCopy->getNameCleanedFromCopy(3) . $labelsNamesSuffix,
                'name4' => $monarcObjectToCopy->getNameCleanedFromCopy(4) . $labelsNamesSuffix,
            ])
            ->setScope($monarcObjectToCopy->getScope())
            ->setMode($monarcObjectToCopy->getMode())
            ->setCreator($this->connectedUser->getEmail());
        if ($anr !== null) {
            $newMonarcObject->addAnr($anr);
        }
        if ($monarcObjectToCopy->hasRolfTag()) {
            $newMonarcObject->setRolfTag($monarcObjectToCopy->getRolfTag());
        }
        /*
         * The objects positioning inside of categories was dropped from the UI, only kept in the db and passed data.
         * The position is always set at the end.
         */
        $this->updatePositions($newMonarcObject, $this->monarcObjectTable);

        return $newMonarcObject;
    }

    private function getChildrenTreeList(Entity\MonarcObject $object): array
    {
        $result = [];
        foreach ($object->getChildrenLinks() as $childLinkObject) {
            /** @var Entity\MonarcObject $childMonarcObject */
            $childMonarcObject = $childLinkObject->getChild();
            $result[] = array_merge([
                'uuid' => $childMonarcObject->getUuid(),
                'component_link_id' => $childLinkObject->getId(),
                'mode' => $childMonarcObject->getMode(),
                'scope' => $childMonarcObject->getScope(),
                'children' => !$childMonarcObject->hasChildren() ? [] : $this->getChildrenTreeList($childMonarcObject),
            ], $childMonarcObject->getLabels(), $childMonarcObject->getNames());
        }

        return $result;
    }

    private function getParentsTreeList(Entity\MonarcObject $object): array
    {
        $result = [];
        foreach ($object->getParentsLinks() as $parentLinkObject) {
            /** @var Entity\MonarcObject $parentMonarcObject */
            $parentMonarcObject = $parentLinkObject->getParent();
            $result[] = array_merge([
                'uuid' => $parentMonarcObject->getUuid(),
                'component_link_id' => $parentLinkObject->getId(),
                'mode' => $parentMonarcObject->getMode(),
                'scope' => $parentMonarcObject->getScope(),
                'parents' => $parentMonarcObject->getParents()->isEmpty()
                    ? []
                    : $this->getParentsTreeList($parentMonarcObject),
            ], $parentMonarcObject->getLabels(), $parentMonarcObject->getNames());
        }

        return $result;
    }

    private function getRisks(Entity\MonarcObject $object): array
    {
        $risks = [];
        foreach ($object->getAsset()->getAmvs() as $amv) {
            $risks[] = [
                'id' => $amv->getUuid(),
                'threatLabel1' => $amv->getThreat()->getLabel(1),
                'threatLabel2' => $amv->getThreat()->getLabel(2),
                'threatLabel3' => $amv->getThreat()->getLabel(3),
                'threatLabel4' => $amv->getThreat()->getLabel(4),
                'threatDescription1' => $amv->getThreat()->getDescription(1),
                'threatDescription2' => $amv->getThreat()->getDescription(2),
                'threatDescription3' => $amv->getThreat()->getDescription(3),
                'threatDescription4' => $amv->getThreat()->getDescription(4),
                'threatRate' => '-',
                'vulnLabel1' => $amv->getVulnerability()->getLabel(1),
                'vulnLabel2' => $amv->getVulnerability()->getLabel(2),
                'vulnLabel3' => $amv->getVulnerability()->getLabel(3),
                'vulnLabel4' => $amv->getVulnerability()->getLabel(4),
                'vulnDescription1' => $amv->getVulnerability()->getDescription(1),
                'vulnDescription2' => $amv->getVulnerability()->getDescription(2),
                'vulnDescription3' => $amv->getVulnerability()->getDescription(3),
                'vulnDescription4' => $amv->getVulnerability()->getDescription(4),
                'vulnerabilityRate' => '-',
                'c_risk' => '-',
                'c_risk_enabled' => $amv->getThreat()->getConfidentiality(),
                'i_risk' => '-',
                'i_risk_enabled' => $amv->getThreat()->getIntegrity(),
                'd_risk' => '-',
                'd_risk_enabled' => $amv->getThreat()->getAvailability(),
                'comment' => '',
            ];
        }

        return $risks;
    }

    private function getRisksOp(Entity\MonarcObject $object): array
    {
        $riskOps = [];
        if ($object->getRolfTag() !== null && $object->getAsset()->isPrimary()) {
            foreach ($object->getRolfTag()->getRisks() as $rolfRisk) {
                $riskOps[] = array_merge($rolfRisk->getLabels(), $rolfRisk->getDescriptions());
            }
        }

        return $riskOps;
    }

    private function validateAndRemoveRootCategoryLinkIfNoObjectsLinked(Entity\MonarcObject $monarcObject): void
    {
        if (!$monarcObject->hasCategory()) {
            return;
        }

        /** @var Entity\ObjectCategory $rootCategory */
        $rootCategory = $monarcObject->getCategory()->getRootCategory();
        /* Check if there are no more objects left under the root category or its children ones. */
        if (!$this->monarcObjectTable->hasObjectsUnderRootCategoryExcludeObject($rootCategory, $monarcObject)) {
            $rootCategory->removeAllAnrLinks();
            $this->objectCategoryTable->save($rootCategory, false);
        }
    }

    private function getDirectParents(Entity\MonarcObject $object): array
    {
        $parents = [];
        foreach ($object->getParents() as $parentObject) {
            $parents[] = array_merge($parentObject->getNames(), $parentObject->getLabels());
        }

        return $parents;
    }

    private function isEditObjectMode(array $filteredData): bool
    {
        return isset($filteredData['mode']['value']) && $filteredData['mode']['value'] === self::MODE_OBJECT_EDIT;
    }

    private function isAnrObjectMode(array $filteredData): bool
    {
        return isset($filteredData['mode']['value']) && $filteredData['mode']['value'] === self::MODE_ANR;
    }

    private function getValidatedAnr(array $filteredData, Entity\MonarcObject $object): Entity\Anr
    {
        $anr = $filteredData['anr']['value'] ?? null;
        if (!$anr instanceof Entity\Anr) {
            throw new \Exception('Anr parameter has to be passed missing for the mode "anr".', 412);
        }
        if (!$object->hasAnrLink($anr)) {
            throw new Exception(sprintf('The object is not linked to the anr ID "%d"', $anr->getId()), 412);
        }

        return $anr;
    }

    private function areAnrObjectsValid(FormattedInputParams $formattedInputParams): bool
    {
        $anrFilter = $formattedInputParams->getFilterFor('anr');
        if (empty($anrFilter['value'])) {
            return true;
        }

        /** @var Entity\Anr $anr */
        $anr = $anrFilter['value'];

        return !$anr->getObjects()->isEmpty();
    }

    private function prepareObjectsListFilter(FormattedInputParams $formattedInputParams): void
    {
        if (!$this->isObjectListFilterPrepared) {
            $this->prepareCategoryFilter($formattedInputParams);
            $this->prepareModelFilter($formattedInputParams);
            $this->prepareAnrFilter($formattedInputParams);
            $this->isObjectListFilterPrepared = true;
        }
    }

    private function prepareModelFilter(FormattedInputParams $formattedInputParams): void
    {
        $modelFilter = $formattedInputParams->getFilterFor('model');
        if (!empty($modelFilter['value'])) {
            /** @var Entity\Model $model */
            $model = $this->modelTable->findById($modelFilter['value']);
            if ($model->isGeneric()) {
                $formattedInputParams->setFilterValueFor('mode', Entity\ObjectSuperClass::MODE_GENERIC);
            } else {
                $assetsFilter = [];
                foreach ($model->getAssets() as $asset) {
                    if ($asset->isModeSpecific()) {
                        $assetsFilter[$asset->getUuid()] = $asset->getUuid();
                    }
                }
                $assets = $this->assetTable->findByMode(Entity\AssetSuperClass::MODE_GENERIC);
                foreach ($assets as $asset) {
                    $assetsFilter[$asset->getUuid()] = $asset->getUuid();
                }
                $formattedInputParams->setFilterValueFor('asset', array_values($assetsFilter));
            }

            $objectsToFilterOut = [];
            if ($model->getAnr() !== null) {
                foreach ($model->getAnr()->getObjects() as $object) {
                    $objectsToFilterOut[$object->getUuid()] = $object->getUuid();
                }
            }
            if (!empty($objectsToFilterOut)) {
                $formattedInputParams->setFilterFor('uuid', [
                    'value' => array_values($objectsToFilterOut),
                    'operator' => Comparison::NIN,
                ]);
            }
        }
    }

    private function prepareAnrFilter(FormattedInputParams $formattedInputParams): void
    {
        $anrFilter = $formattedInputParams->getFilterFor('anr');
        if (!empty($anrFilter['value'])) {
            /** @var Entity\Anr $anr */
            $anr = $anrFilter['value'];
            $objectsUuids = [];
            foreach ($anr->getObjects() as $object) {
                $objectsUuids[] = $object->getUuid();
            }

            $formattedInputParams->setFilterValueFor('uuid', $objectsUuids);
        }
    }

    private function prepareCategoryFilter(FormattedInputParams $formattedInputParams): void
    {
        $lockFilter = $formattedInputParams->getFilterFor('lock');
        $categoryFilter = $formattedInputParams->getFilterFor('category.id');
        if (!empty($categoryFilter['value'])) {
            if ($categoryFilter['value'] === -1) {
                $formattedInputParams->unsetFilterFor('category.id')->setFilterValueFor('category', null);
            } elseif (!empty($lockFilter['value'])) {
                /** @var Entity\ObjectCategory $objectCategory */
                $objectCategory = $this->objectCategoryTable->findById($categoryFilter['value']);
                $formattedInputParams->setFilterFor('category.id', [
                    'value' => array_merge([$categoryFilter['value']], $objectCategory->getRecursiveChildrenIds()),
                    'operator' => Comparison::IN,
                ]);
            }
        }
    }

    private function duplicateObjectChildren(
        Entity\MonarcObject $objectToCopy,
        Entity\MonarcObject $parentObject,
        ?Entity\Anr $anr
    ): void {
        foreach ($objectToCopy->getChildren() as $childObject) {
            $newChildObject = $this->getObjectCopy($childObject, $anr);

            /* Only to keep the same positions in the duplicated object composition. */
            foreach ($childObject->getParentsLinks() as $parentLink) {
                /* The child object could be presented in different compositions, so validate if the parent is right. */
                if ($parentLink->getParent()->isEqualTo($objectToCopy)) {
                    $newParentLink = (new Entity\ObjectObject())
                        ->setParent($parentObject)
                        ->setChild($newChildObject)
                        ->setPosition($parentLink->getPosition())
                        ->setCreator($this->connectedUser->getEmail());
                    $this->objectObjectTable->save($newParentLink, false);

                    $newChildObject->addParentLink($newParentLink);
                }
            }

            $this->monarcObjectTable->save($newChildObject, false);

            if ($childObject->hasChildren()) {
                $this->duplicateObjectChildren($childObject, $newChildObject, $anr);
            }
        }
    }

    private function getPreparedObjectCategoryData(
        Entity\ObjectCategory $category,
        array $objectsData,
        Entity\Anr $anr
    ): array {
        $result = array_merge($category->getLabels(), [
            'id' => $category->getId(),
            'position' => $category->getPosition(),
            'child' => !$category->hasChildren()
                ? []
                : $this->getCategoriesWithObjectsChildrenTreeList($category, $anr),
            'objects' => $objectsData,
        ]);
        if (empty($objectsData) && empty($result['child'])) {
            return [];
        }

        return $result;
    }

    private function getCategoriesAndObjectsTreeList(Entity\ObjectCategory $objectCategory, Entity\Anr $anr): array
    {
        $result = [];
        $objectsData = $this->getObjectsDataOfCategoryAndAnr($objectCategory, $anr);
        if (!empty($objectsData) || $objectCategory->hasChildren()) {
            $objectCategoryData = $this->getPreparedObjectCategoryData($objectCategory, $objectsData, $anr);
            if (!empty($objectsData) || !empty($objectCategoryData)) {
                $result = $objectCategoryData;
            }
        }

        return $result;
    }

    private function getCategoriesWithObjectsChildrenTreeList(
        Entity\ObjectCategory $objectCategory,
        Entity\Anr $anr
    ): array {
        $result = [];
        foreach ($objectCategory->getChildren() as $childCategory) {
            $categoryData = $this->getCategoriesAndObjectsTreeList($childCategory, $anr);
            if (!empty($categoryData)) {
                $result[] = $categoryData;
            }
        }

        return $result;
    }

    private function getObjectsDataOfCategoryAndAnr(Entity\ObjectCategory $objectCategory, Entity\Anr $anr): array
    {
        $objectsData = [];
        /** @var Entity\MonarcObject $object */
        foreach ($objectCategory->getObjects() as $object) {
            if ($object->hasAnrLink($anr)) {
                $objectsData[] = $this->getPreparedObjectData($object, true);
            }
        }

        return $objectsData;
    }
}
