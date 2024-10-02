<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity;
use Monarc\Core\Service\Traits\ImpactVerificationTrait;
use Monarc\Core\Table;
use Monarc\Core\Service\Traits\RiskCalculationTrait;

class InstanceRiskService
{
    use RiskCalculationTrait;
    use ImpactVerificationTrait;

    private Table\InstanceRiskTable $instanceRiskTable;

    private Table\InstanceTable $instanceTable;

    private Table\ScaleTable $scaleTable;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        Table\InstanceRiskTable $instanceRiskTable,
        Table\InstanceTable $instanceTable,
        Table\ScaleTable $scaleTable,
        ConnectedUserService $connectedUserService
    ) {
        $this->instanceRiskTable = $instanceRiskTable;
        $this->instanceTable = $instanceTable;
        $this->scaleTable = $scaleTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getInstanceRisks(Entity\Anr $anr, ?int $instanceId, array $params = []): array
    {
        if ($instanceId !== null) {
            /** @var Entity\Instance $instance */
            $instance = $this->instanceTable->findByIdAndAnr($instanceId, $anr);
            $params['instanceIds'] = $instance->getSelfAndChildrenIds();
        }

        $languageIndex = $this->connectedUser->getLanguage();

        /** @var Entity\InstanceRisk[] $instanceRisks */
        $instanceRisks = $this->instanceRiskTable->findInstancesRisksByParams($anr, $languageIndex, $params);

        $result = [];
        foreach ($instanceRisks as $instanceRisk) {
            $object = $instanceRisk->getInstance()->getObject();
            $threat = $instanceRisk->getThreat();
            $vulnerability = $instanceRisk->getVulnerability();
            $key = $object->isScopeGlobal()
                ? 'o' . $object->getUuid() . '-' . $threat->getUuid() . '-' . $vulnerability->getUuid()
                : 'r' . $instanceRisk->getId();
            if (!isset($result[$key]) || $this->areInstanceRiskImpactsHigher($instanceRisk, $result[$key])) {
                $result[$key] = [
                    'id' => $instanceRisk->getId(),
                    'oid' => $object->getUuid(),
                    'instance' => $instanceRisk->getInstance()->getId(),
                    'amv' => $instanceRisk->getAmv()?->getUuid(),
                    'asset' => $instanceRisk->getAsset()->getUuid(),
                    'assetLabel' . $languageIndex => $instanceRisk->getAsset()->getLabel($languageIndex),
                    'assetDescription' . $languageIndex => $instanceRisk->getAsset()->getDescription($languageIndex),
                    'threat' => $threat->getUuid(),
                    'threatCode' => $threat->getCode(),
                    'threatLabel' . $languageIndex => $threat->getLabel($languageIndex),
                    'threatDescription' . $languageIndex => $threat->getDescription($languageIndex),
                    'threatRate' => $instanceRisk->getThreatRate(),
                    'vulnerability' => $vulnerability->getUuid(),
                    'vulnCode' => $vulnerability->getCode(),
                    'vulnLabel' . $languageIndex => $vulnerability->getLabel($languageIndex),
                    'vulnDescription' . $languageIndex => $vulnerability->getDescription($languageIndex),
                    'vulnerabilityRate' => $instanceRisk->getVulnerabilityRate(),
                    'specific' => $instanceRisk->getSpecific(),
                    'reductionAmount' => $instanceRisk->getReductionAmount(),
                    'c_impact' => $instanceRisk->getInstance()->getConfidentiality(),
                    'c_risk' => $instanceRisk->getRiskConfidentiality(),
                    'c_risk_enabled' => $threat->getConfidentiality(),
                    'i_impact' => $instanceRisk->getInstance()->getIntegrity(),
                    'i_risk' => $instanceRisk->getRiskIntegrity(),
                    'i_risk_enabled' => $threat->getIntegrity(),
                    'd_impact' => $instanceRisk->getInstance()->getAvailability(),
                    'd_risk' => $instanceRisk->getRiskAvailability(),
                    'd_risk_enabled' => $threat->getAvailability(),
                    'target_risk' => $instanceRisk->getCacheTargetedRisk(),
                    'max_risk' => $instanceRisk->getCacheMaxRisk(),
                    'comment' => $instanceRisk->getComment(),
                    'scope' => $object->getScope(),
                    'kindOfMeasure' => $instanceRisk->getKindOfMeasure(),
                    't' => $instanceRisk->isTreated(),
                    'tid' => $threat->getUuid(),
                    'vid' => $vulnerability->getUuid(),
                    'instanceName' . $languageIndex => $instanceRisk->getInstance()->getName($languageIndex),
                ];
            }
        }

        return array_values($result);
    }

    public function createInstanceRisk(
        Entity\Instance $instance,
        Entity\Amv $amv,
        ?Entity\InstanceRisk $fromInstanceRisk = null,
        bool $saveInDb = false
    ): Entity\InstanceRisk {
        $instanceRisk = $fromInstanceRisk !== null
            ? Entity\InstanceRisk::constructFromObject($fromInstanceRisk)
            : new Entity\InstanceRisk();

        /** @var Entity\InstanceRisk $instanceRisk */
        $instanceRisk
            ->setAnr($instance->getAnr())
            ->setInstance($instance)
            ->setAmv($amv)
            ->setAsset($amv->getAsset())
            ->setThreat($amv->getThreat())
            ->setVulnerability($amv->getVulnerability())
            ->setCreator($this->connectedUser->getEmail());

        if ($fromInstanceRisk !== null) {
            $this->recalculateRiskRates($instanceRisk);
        }

        $this->instanceRiskTable->save($instanceRisk, $saveInDb);

        return $instanceRisk;
    }

    /**
     * Is used when a new library object is instantiated to an ANR.
     */
    public function createInstanceRisks(
        Entity\Instance $instance,
        Entity\MonarcObject $monarcObject,
        bool $saveInDb = true
    ): void {
        $otherInstance = $this->instanceTable
            ->findOneByAnrAndObjectExcludeInstance($instance->getAnr(), $monarcObject, $instance);

        if ($otherInstance !== null && $monarcObject->isScopeGlobal()) {
            foreach ($otherInstance->getInstanceRisks() as $instanceRisk) {
                /** @var Entity\Amv $amv */
                $amv = $instanceRisk->getAmv();
                $this->createInstanceRisk($instance, $amv, $instanceRisk);
            }
        } else {
            foreach ($monarcObject->getAsset()->getAmvs() as $amv) {
                $this->createInstanceRisk($instance, $amv);
            }
        }

        if ($saveInDb) {
            $this->instanceRiskTable->flush();
        }
    }

    /**
     * Called only on BackOffice side from ScaleService.
     */
    public function updateInstanceRiskRates(Entity\InstanceRisk $instanceRisk, array $data): void
    {
        $this->verifyInstanceRiskRates($instanceRisk, $this->scaleTable, $data);

        if (isset($data['threatRate'])) {
            $instanceRisk->setReductionAmount((int)$data['threatRate']);
        }
        if (isset($data['vulnerabilityRate'])) {
            $instanceRisk->setVulnerabilityRate((int)$data['vulnerabilityRate']);
        }
        if (isset($data['reductionAmount'])) {
            $instanceRisk->setReductionAmount($data['reductionAmount']);
        }

        $instanceRisk->setUpdater($this->connectedUser->getEmail());

        $this->recalculateRiskRates($instanceRisk);

        $this->instanceRiskTable->save($instanceRisk);
    }

    public function update(Entity\Anr $anr, int $id, array $data, bool $manageGlobal = true): Entity\InstanceRisk
    {
        /** @var Entity\InstanceRisk $instanceRisk */
        $instanceRisk = $this->instanceRiskTable->findByIdAndAnr($id, $anr);

        $this->verifyInstanceRiskRates($instanceRisk, $this->scaleTable, $data);

        $this->updateInstanceRiskData($instanceRisk, $data);

        if ($manageGlobal) {
            /* The impact has to be updated for the siblings / other global instances and risks. */
            $object = $instanceRisk->getInstance()->getObject();
            if ($object->isScopeGlobal()) {
                $instances = $this->instanceTable->findByAnrAndObject($instanceRisk->getAnr(), $object);
                foreach ($instances as $instance) {
                    if ($instanceRisk->getInstance()->getId() === $instance->getId()) {
                        continue;
                    }

                    $siblingInstancesRisks = $this->instanceRiskTable->findByInstanceAndInstanceRiskRelations(
                        $instance,
                        $instanceRisk
                    );

                    foreach ($siblingInstancesRisks as $siblingInstanceRisk) {
                        $this->updateInstanceRiskData($siblingInstanceRisk, $data);
                    }
                }
            }
        }

        $this->instanceRiskTable->save($instanceRisk);

        return $instanceRisk;
    }

    private function updateInstanceRiskData(Entity\InstanceRisk $instanceRisk, array $data): void
    {
        if (isset($data['reductionAmount'])) {
            $instanceRisk->setReductionAmount((int)$data['reductionAmount']);
        }
        if (isset($data['threatRate']) && $instanceRisk->getThreatRate() !== $data['threatRate']) {
            $instanceRisk->setThreatRate((int)$data['threatRate'])
                ->setIsThreatRateNotSetOrModifiedExternally(false);
        }
        if (isset($data['vulnerabilityRate'])) {
            $instanceRisk->setVulnerabilityRate((int)$data['vulnerabilityRate']);
        }
        if (isset($data['comment'])) {
            $instanceRisk->setComment($data['comment']);
        }
        if (isset($data['kindOfMeasure'])) {
            $instanceRisk->setKindOfMeasure((int)$data['kindOfMeasure']);
        }

        $instanceRisk->setUpdater($this->connectedUser->getEmail());

        $this->recalculateRiskRates($instanceRisk);
    }
}
