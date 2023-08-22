<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity;
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

    public function createInstanceRisk(
        Entity\Instance $instance,
        Entity\Amv $amv,
        ?Entity\InstanceRisk $fromInstanceRisk = null,
        bool $saveInDb = false
    ): Entity\InstanceRisk {
        $instanceRisk = $fromInstanceRisk !== null
            ? $this->getConstructedFromObjectInstanceRisk($fromInstanceRisk)
            : $this->createInstanceRiskObject();

        /** @var Entity\InstanceRisk $instanceRisk */
        $instanceRisk
            ->setAnr($instance->getAnr())
            ->setInstance($instance)
            ->setAmv($amv)
            ->setAsset($amv->getAsset())
            ->setThreat($amv->getThreat())
            ->setVulnerability($amv->getVulnerability())
            ->setCreator($this->connectedUser->getEmail());

        $this->recalculateRiskRates($instanceRisk, $saveInDb);

        $this->instanceRiskTable->save($instanceRisk, $saveInDb);

        return $instanceRisk;
    }

    public function getInstanceRisks(Entity\AnrSuperClass $anr, ?int $instanceId, array $params = []): array
    {
        if ($instanceId !== null) {
            /** @var Entity\InstanceSuperClass $instance */
            $instance = $this->instanceTable->findByIdAndAnr($instanceId, $anr);
            $params['instanceIds'] = $this->getInstanceAndItsChildrenIds($instance);
        }

        $languageIndex = $this->getLanguageIndex($anr);

        $instanceRisks = $this->instanceRiskTable->findInstancesRisksByParams($anr, $languageIndex, $params);

        $result = [];
        foreach ($instanceRisks as $instanceRisk) {
            $object = $instanceRisk->getInstance()->getObject();
            $threat = $instanceRisk->getThreat();
            $vulnerability = $instanceRisk->getVulnerability();
            $key = 'r' . $instanceRisk->getId();
            $shouldInstanceRiskBeAddedToResults = true;
            if ($object->isScopeGlobal()) {
                $key = 'o' . $object->getUuid() . '-' . $threat->getUuid() . '-' . $vulnerability->getUuid();
                if (isset($result[$key])) {
                    $shouldInstanceRiskBeAddedToResults = $this->shouldInstanceRiskBeAddedToResults(
                        $instanceRisk,
                        $result[$key]
                    );
                }
            }
            if (!$object->isScopeGlobal() || $shouldInstanceRiskBeAddedToResults) {
                $result[$key] = $this->addCustomFieldsToInstanceRiskResult($instanceRisk, [
                    'id' => $instanceRisk->getId(),
                    'oid' => $object->getUuid(),
                    'instance' => $instanceRisk->getInstance()->getId(),
                    'amv' => $instanceRisk->getAmv() ? $instanceRisk->getAmv()->getUuid() : null,
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
                ]);
            }
        }

        return array_values($result);
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

        $this->recalculateRiskRates($instanceRisk, false);

        $this->instanceRiskTable->save($instanceRisk);
    }

    public function update(
        Entity\AnrSuperClass $anr,
        int $id,
        array $data,
        bool $manageGlobal = true
    ): Entity\InstanceRiskSuperClass {
        /** @var Entity\InstanceRiskSuperClass $instanceRisk */
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

    public function recalculateRiskRates(Entity\InstanceRiskSuperClass $instanceRisk, bool $saveInDb = true)
    {
        $instance = $instanceRisk->getInstance();

        $riskConfidentiality = $this->calculateRiskConfidentiality(
            $instance->getConfidentiality(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );
        $riskIntegrity = $this->calculateRiskIntegrity(
            $instance->getIntegrity(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );
        $riskAvailability = $this->calculateRiskAvailability(
            $instance->getAvailability(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );

        $instanceRisk
            ->setRiskConfidentiality($riskConfidentiality)
            ->setRiskIntegrity($riskIntegrity)
            ->setRiskAvailability($riskAvailability);

        $risks = [];
        $impacts = [];

        if ($instanceRisk->getThreat()->getConfidentiality()) {
            $risks[] = $riskConfidentiality;
            $impacts[] = $instance->getConfidentiality();
        }
        if ($instanceRisk->getThreat()->getIntegrity()) {
            $risks[] = $riskIntegrity;
            $impacts[] = $instance->getIntegrity();
        }
        if ($instanceRisk->getThreat()->getAvailability()) {
            $risks[] = $riskAvailability;
            $impacts[] = $instance->getAvailability();
        }

        $instanceRisk->setCacheMaxRisk(!empty($risks) ? max($risks) : -1);
        $instanceRisk->setCacheTargetedRisk(
            $this->calculateTargetRisk(
                $impacts,
                $instanceRisk->getThreatRate(),
                $instanceRisk->getVulnerabilityRate(),
                $instanceRisk->getReductionAmount()
            )
        );

        $this->instanceRiskTable->save($instanceRisk, $saveInDb);
    }

    protected function getConstructedFromObjectInstanceRisk(
        Entity\InstanceRiskSuperClass $instanceRisk
    ): Entity\InstanceRiskSuperClass {
        return Entity\InstanceRisk::constructFromObject($instanceRisk);
    }

    protected function createInstanceRiskObject(): Entity\InstanceRiskSuperClass
    {
        return new Entity\InstanceRisk();
    }

    protected function getLanguageIndex(Entity\AnrSuperClass $anr): int
    {
        return $this->connectedUser->getLanguage();
    }

    protected function addCustomFieldsToInstanceRiskResult(
        Entity\InstanceRiskSuperClass $instanceRisk,
        array $instanceRiskResult
    ): array {
        return $instanceRiskResult;
    }

    private function getInstanceAndItsChildrenIds(Entity\InstanceSuperClass $instance): array
    {
        $childrenIds = [];
        foreach ($instance->getChildren() as $childInstance) {
            $childrenIds = array_merge($childrenIds, $this->getInstanceAndItsChildrenIds($childInstance));
        }

        return array_merge([$instance->getId()], $childrenIds);
    }

    /**
     * Determines whether the instance risk should be added to the list result in case. Only for global objects.
     *
     * @param Entity\InstanceRiskSuperClass $instanceRisk
     * @param array $valuesToCompare
     *
     * @return bool
     */
    private function shouldInstanceRiskBeAddedToResults(
        Entity\InstanceRiskSuperClass $instanceRisk,
        array $valuesToCompare
    ): bool {
        $instance = $instanceRisk->getInstance();
        $isMaxRiskSet = false;
        foreach ($instance->getInstanceRisks() as $instanceRiskToValidate) {
            if ($instanceRiskToValidate->getCacheMaxRisk() !== -1) {
                $isMaxRiskSet = true;
                break;
            }
        }
        if ($isMaxRiskSet) {
            return $valuesToCompare['max_risk'] < $instanceRisk->getCacheMaxRisk();
        }

        /* We compare CIA criteria in case if max risk value is not set. */
        $maxExistedCia = max($valuesToCompare['c_impact'], $valuesToCompare['i_impact'], $valuesToCompare['d_impact']);
        $maxCurrentCia = max($instance->getConfidentiality(), $instance->getIntegrity(), $instance->getAvailability());
        if ($maxExistedCia === $maxCurrentCia) {
            $sumExistedCia = $valuesToCompare['c_impact'] + $valuesToCompare['i_impact'] + $valuesToCompare['d_impact'];
            $sumCurrentCia = $instance->getConfidentiality() + $instance->getIntegrity() + $instance->getAvailability();

            return $sumExistedCia < $sumCurrentCia;
        }

        return $maxExistedCia < $maxCurrentCia;
    }

    private function updateInstanceRiskData(Entity\InstanceRiskSuperClass $instanceRisk, array $data): void
    {
        if (isset($data['reductionAmount'])) {
            $instanceRisk->setReductionAmount((int)$data['reductionAmount']);
        }
        if (isset($data['threatRate'])) {
            $instanceRisk->setThreatRate((int)$data['threatRate']);
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

        $this->recalculateRiskRates($instanceRisk, false);
    }
}
