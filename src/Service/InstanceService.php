<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Table;

class InstanceService
{
    use PositionUpdateTrait;

    private Table\InstanceTable $instanceTable;

    private Table\MonarcObjectTable $monarcObjectTable;

    private InstanceRiskService $instanceRiskService;

    private InstanceRiskOpService $instanceRiskOpService;

    private InstanceConsequenceService $instanceConsequenceService;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        Table\InstanceTable $instanceTable,
        Table\MonarcObjectTable $monarcObjectTable,
        InstanceRiskService $instanceRiskService,
        InstanceRiskOpService $instanceRiskOpService,
        InstanceConsequenceService $instanceConsequenceService,
        ConnectedUserService $connectedUserService
    ) {
        $this->instanceTable = $instanceTable;
        $this->monarcObjectTable = $monarcObjectTable;
        $this->instanceRiskService = $instanceRiskService;
        $this->instanceRiskOpService = $instanceRiskOpService;
        $this->instanceConsequenceService = $instanceConsequenceService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getInstancesData(Entity\AnrSuperClass $anr): array
    {
        $rootInstances = $this->instanceTable->findRootInstancesByAnrAndOrderByPosition($anr);
        $instanceData = [];
        foreach ($rootInstances as $rootInstance) {
            $instanceData[] = array_merge(
                $this->getPreparedInstanceData($rootInstance),
                ['child' => $this->getChildrenTreeList($rootInstance)]
            );
        }

        return $instanceData;
    }

    public function getInstanceData(Entity\AnrSuperClass $anr, int $id): array
    {
        /** @var Entity\InstanceSuperClass $instance */
        $instance = $this->instanceTable->findById($id);
        if ($instance->getAnr()->getId() !== $anr->getId()) {
            throw new Exception(sprintf('The instance ID "%d" belongs to a different analysis.', $id));
        }

        $instanceData = $this->getPreparedInstanceData($instance);

        $instanceData['consequences'] = $this->instanceConsequenceService->getConsequencesData($instance);
        $instanceData['instances'] = $this->getOtherInstances($instance);

        return $instanceData;
    }

    public function instantiateObjectToAnr(
        Entity\AnrSuperClass $anr,
        array $data,
        bool $isRootLevel = false
    ): Entity\InstanceSuperClass {
        $object = $data['object'] instanceof Entity\ObjectSuperClass
            ? $data['object']
            : $this->monarcObjectTable->findByUuid($data['object']);

        if (!$object->hasAnrLink($anr)) {
            throw new Exception('The object is not linked to the anr', 412);
        }

        $instanceClassName = $this->instanceTable->getEntityName();
        /** @var Entity\InstanceSuperClass|Entity\Instance $instance */
        $instance = (new $instanceClassName)
            ->setAnr($anr)
            ->setObject($object)
            ->setAsset($object->getAsset())
            ->setNames($object->getNames())
            ->setLabels($object->getLabels())
            ->setCreator($this->connectedUser->getEmail());

        if (!empty($data['parent'])) {
            /** @var Entity\InstanceSuperClass $parentInstance */
            $parentInstance = $data['parent'] instanceof Entity\InstanceSuperClass
                ? $data['parent']
                : $this->instanceTable->findById($data['parent']);

            $instance->setParent($parentInstance)->setRoot($parentInstance->getRootInstance());
        }

        $this->updateInstanceLevels($isRootLevel, $instance);

        $this->updatePositions($instance, $this->instanceTable, $this->getPreparedPositionData($instance, $data));

        $this->instanceTable->save($instance);

        /* Used only on FO side. */
        $this->updateAnrInstanceMetadataFieldFromBrothers($instance);

        $this->instanceConsequenceService->createInstanceConsequences($instance, $anr, $object);
        $instance->updateImpactBasedOnConsequences()->refreshInheritedImpact();

        $this->instanceTable->save($instance, false);

        $this->instanceRiskService->createInstanceRisks($instance, $object);

        $this->instanceRiskOpService->createInstanceRisksOp($instance, $object);

        /* Check if the root element is not the same as current child element to avoid a circular dependency. */
        if ($instance->isRoot()
            || !$instance->hasParent()
            || $instance->getParent()->isRoot()
            || $instance->getParent()->getRoot()->getObject()->getUuid() !== $instance->getObject()->getUuid()
        ) {
            $this->createChildren($instance);
        }

        return $instance;
    }

    public function updateInstance(Entity\AnrSuperClass $anr, int $id, array $data): Entity\InstanceSuperClass
    {
        /** @var Entity\InstanceSuperClass $instance */
        $instance = $this->instanceTable->findById($id);

        $this->updateConsequences($anr, $data['consequences']);

        $this->refreshInstanceImpactAndUpdateRisks($instance);

        $this->updateOtherGlobalInstancesConsequences($instance, $data);

        $this->instanceTable->save($instance);

        return $instance;
    }

    public function patchInstance(Entity\AnrSuperClass $anr, int $id, array $data): Entity\InstanceSuperClass
    {
        if (isset($data['parent']) && $id === $data['parent']) {
            throw new Exception('Instance can not be a parent of itself.', 412);
        }

        /** @var Entity\InstanceSuperClass $instance */
        $instance = $this->instanceTable->findById($id);

        $this->updateInstanceParent($instance, $data);

        $this->updatePositions($instance, $this->instanceTable, $this->getPreparedPositionData($instance, $data));

        $instance->refreshInheritedImpact();

        $this->updateRisks($instance);

        $this->updateChildrenImpactsAndRisks($instance);

        $this->instanceTable->save($instance->setUpdater($this->connectedUser->getEmail()));

        $this->updateOtherGlobalInstancesConsequences($instance, $data);

        return $instance;
    }

    public function delete(int $id): void
    {
        /** @var Entity\InstanceSuperClass $instance */
        $instance = $this->instanceTable->findById($id);

        /* Only a root instance can be deleted. */
        if (!$instance->isLevelRoot()) {
            throw new Exception('Only a root instance can be deleted.', 412);
        }

        $instance->removeAllInstanceRisks()->removeAllOperationalInstanceRisks();

        $this->shiftPositionsForRemovingEntity($instance, $this->instanceTable);

        $this->instanceTable->remove($instance);
    }

    public function updateChildrenImpactsAndRisks(Entity\InstanceSuperClass $instance): void
    {
        foreach ($instance->getChildren() as $childInstance) {
            $childInstance->refreshInheritedImpact();

            $this->instanceTable->save($childInstance, false);

            $this->updateRisks($childInstance);

            $this->updateChildrenImpactsAndRisks($childInstance);
        }
    }

    public function refreshInstanceImpactAndUpdateRisks(Entity\InstanceSuperClass $instance): void
    {
        $instance->updateImpactBasedOnConsequences();
        $this->updateRisks($instance);
        $this->updateChildrenImpactsAndRisks($instance);
        $instance->setUpdater($this->connectedUser->getEmail());

        $this->instanceTable->save($instance, false);
    }

    /**
     * Is called when a ScaleImpactTypeService when a scale type visibility is changed.
     */
    public function refreshAllTheInstancesImpactAndUpdateRisks(Entity\AnrSuperClass $anr): void
    {
        $rootInstances = $this->instanceTable->findRootsByAnr($anr);
        foreach ($rootInstances as $rootInstance) {
            $this->refreshInstanceImpactAndUpdateRisks($rootInstance);
            $this->updateChildrenImpactsAndRisks($rootInstance);
        }

        $this->instanceTable->flush();
    }

    protected function updateAnrInstanceMetadataFieldFromBrothers(Entity\InstanceSuperClass $instance): void
    {
    }

    /**
     * Creates instances for each child.
     */
    private function createChildren(Entity\InstanceSuperClass $parentInstance): void
    {
        foreach ($parentInstance->getObject()->getChildrenLinks() as $childObjectLink) {
            $this->instantiateObjectToAnr($parentInstance->getAnr(), [
                'object' => $childObjectLink->getChild(),
                'parent' => $parentInstance,
                'position' => $childObjectLink->getChild()->getPosition(),
            ]);
        }
    }

    /**
     * The level is used to determine if the related object has a composition and if not root (doesn't have it),
     * then the instance can be removed or moved independently.
     */
    private function updateInstanceLevels(bool $rootLevel, Entity\InstanceSuperClass $instance): void
    {
        if ($rootLevel) {
            $instance->setLevel(Entity\InstanceSuperClass::LEVEL_ROOT);
        } elseif ($instance->getObject()->hasChildren()) {
            $instance->setLevel(Entity\InstanceSuperClass::LEVEL_INTER);
        } else {
            $instance->setLevel(Entity\InstanceSuperClass::LEVEL_LEAF);
        }
    }

    private function updateOtherGlobalInstancesConsequences(Entity\InstanceSuperClass $instance, array $data): void
    {
        if ($instance->getObject()->isScopeGlobal()) {
            /* Retrieve instances linked to the same global object to update impacts based on the passed instance. */
            foreach ($instance->getObject()->getInstances() as $otherGlobalInstance) {
                if ($otherGlobalInstance->getId() !== $instance->getId()) {
                    /* Consequences of this instance supposed to be already updated before ::updateInstance, where
                    called ::updateConsequences, InstanceConsequenceService::patchConsequence and finally
                    InstanceConsequenceService::updateSiblingsConsequences. */
                    $otherGlobalInstance->updateImpactBasedOnConsequences();
                    $this->updateRisks($otherGlobalInstance);
                    $this->updateChildrenImpactsAndRisks($otherGlobalInstance);
                    $otherGlobalInstance->setUpdater($this->connectedUser->getEmail());

                    $this->instanceTable->save($otherGlobalInstance, false);
                }
            }
        }
    }

    private function updateConsequences(Entity\AnrSuperClass $anr, array $consequencesData)
    {
        foreach ($consequencesData as $consequenceData) {
            $this->instanceConsequenceService->patchConsequence($anr, $consequenceData['id'], [
                'confidentiality' => (int)$consequenceData['c_risk'],
                'integrity' => (int)$consequenceData['i_risk'],
                'availability' => (int)$consequenceData['d_risk'],
                'isHidden' => (int)$consequenceData['isHidden'],
            ]);
        }
    }

    private function getChildrenTreeList(Entity\InstanceSuperClass $instance): array
    {
        $result = [];
        foreach ($instance->getChildren() as $childInstance) {
            $result[] = array_merge(
                $this->getPreparedInstanceData($childInstance),
                ['child' => $this->getChildrenTreeList($childInstance)]
            );
        }

        return $result;
    }

    private function getPreparedInstanceData(Entity\InstanceSuperClass $instance): array
    {
        return array_merge([
            'id' => $instance->getId(),
            'anr' => [
                'id' => $instance->getAnr()->getId(),
            ],
            'asset' => array_merge([
                'uuid' => $instance->getAsset()->getUuid(),
                'type' => $instance->getAsset()->getType(),
            ], $instance->getAsset()->getLabels()),
            'object' => array_merge([
                'uuid' => $instance->getObject()->getUuid(),
                'scope' => $instance->getObject()->getScope(),
            ], $instance->getObject()->getLabels(), $instance->getObject()->getNames()),
            'root' => $instance->isRoot() ? null : $instance->getRootInstance(),
            'parent' => $instance->hasParent() ? $instance->getParent() : null,
            'level' => $instance->getLevel(),
            'assetType' => $instance->getAssetType(),
            'exportable' => $instance->getExportable(),
            'c' => $instance->getConfidentiality(),
            'i' => $instance->getIntegrity(),
            'd' => $instance->getAvailability(),
            'ch' => (int)$instance->isConfidentialityInherited(),
            'ih' => (int)$instance->isIntegrityInherited(),
            'dh' => (int)$instance->isAvailabilityInherited(),
            'position' => $instance->getPosition(),
            'scope' => $instance->getObject()->getScope(),
        ], $instance->getLabels(), $instance->getNames());
    }

    private function getOtherInstances(Entity\InstanceSuperClass $instance): array
    {
        $otherInstances = $this->instanceTable->findByAnrAndObject($instance->getAnr(), $instance->getObject());
        $names = [
            'name1' => $instance->getAnr()->getLabel(1),
            'name2' => $instance->getAnr()->getLabel(2),
            'name3' => $instance->getAnr()->getLabel(3),
            'name4' => $instance->getAnr()->getLabel(4),
        ];
        $otherInstancesData = [];
        foreach ($otherInstances as $otherInstance) {
            $names['id'] = $otherInstance->getId();
            foreach ($otherInstance->getHierarchyArray() as $instanceFromTheTree) {
                $names['name1'] .= ' > ' . $instanceFromTheTree['name1'];
                $names['name2'] .= ' > ' . $instanceFromTheTree['name2'];
                $names['name3'] .= ' > ' . $instanceFromTheTree['name3'];
                $names['name4'] .= ' > ' . $instanceFromTheTree['name4'];
            }

            $otherInstancesData[] = $names;
        }

        return $otherInstancesData;
    }

    private function updateRisks(Entity\InstanceSuperClass $instance): void
    {
        foreach ($instance->getInstanceRisks() as $instanceRisk) {
            $this->instanceRiskService->recalculateRiskRates($instanceRisk, false);
        }
    }

    private function updateInstanceParent(Entity\InstanceSuperClass $instance, array $data): void
    {
        if (!empty($data['parent'])
            && (!$instance->hasParent() || $instance->getParent()->getId() !== $data['parent'])
        ) {
            /** @var Entity\InstanceSuperClass|null $parentInstance */
            $parentInstance = $this->instanceTable->findById((int)$data['parent'], false);
            if ($parentInstance !== null) {
                $instance->setParent($parentInstance)->setRoot($parentInstance->getRoot() ?? $parentInstance);
            }
        } elseif (empty($data['parent']) && $instance->hasParent()) {
            $instance->setParent(null)->setRoot(null);
        }
    }

    private function getPreparedPositionData(Entity\InstanceSuperClass $instance, array $data): array
    {
        $positionData = [];
        if (isset($data['position'])) {
            $positionData = [
                'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_START,
                'forcePositionUpdate' => true,
            ];
            if ((int)$data['position'] > 0) {
                $previousInstancePosition = $data['position'];
                /* If the instance is moved inside the same parent or root and its position <= then expected one,
                 * the previous element position is increased to 1. */
                if ($this->instanceTable->isEntityPersisted($instance)
                    && $previousInstancePosition >= $instance->getPosition()
                    && !$instance->arePropertiesStatesChanged($instance->getImplicitPositionRelationsValues())
                ) {
                    $previousInstancePosition++;
                }
                $previousInstance = $this->instanceTable->findOneByAnrParentAndPosition(
                    $instance->getAnr(),
                    $instance->getParent(),
                    $previousInstancePosition
                );
                if ($previousInstance !== null) {
                    $positionData = [
                        'implicitPosition' => PositionUpdatableServiceInterface::IMPLICIT_POSITION_AFTER,
                        'previous' => $previousInstance,
                        'forcePositionUpdate' => true,
                    ];
                }
            }
        }

        return $positionData;
    }
}
