<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity;
use Monarc\Core\Table;

class ScaleService
{
    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        private Table\ScaleTable $scaleTable,
        private Table\ScaleCommentTable $scaleCommentTable,
        private Table\InstanceTable $instanceTable,
        private Table\InstanceRiskTable $instanceRiskTable,
        private Table\InstanceConsequenceTable $instanceConsequenceTable,
        private InstanceRiskService $instanceRiskService,
        private ScaleImpactTypeService $scaleImpactTypeService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(Entity\Anr $anr): array
    {
        $result = [];
        /** @var Entity\Scale[] $scales */
        $scales = $this->scaleTable->findByAnr($anr);
        $availableTypes = Entity\ScaleSuperClass::getAvailableTypes();

        foreach ($scales as $scale) {
            $result[] = [
                'id' => $scale->getId(),
                'type' => $availableTypes[$scale->getType()],
                'min' => $scale->getMin(),
                'max' => $scale->getMax(),
            ];
        }

        return $result;
    }

    /**
     * The creation of a new scale is only initiated when we create a model.
     * In all the other cases the scales are ether duplicated (duplication of anr/model) nether updated (import).
     */
    public function create(Entity\Anr $anr, int $type, int $min, int $max): Entity\Scale
    {
        /** @var Entity\Scale $scale */
        $scale = (new Entity\Scale($anr, compact('type', 'min', 'max')))->setCreator($this->connectedUser->getEmail());

        if ($scale->getType() === Entity\ScaleSuperClass::TYPE_IMPACT) {
            $this->scaleImpactTypeService->createDefaultScaleImpactTypes($scale);
        }

        $this->scaleTable->save($scale);

        return $scale;
    }

    /**
     * This method is called only from BackOffice controller's action when min/max values are changed.
     */
    public function update(Entity\Anr $anr, int $id, array $data): Entity\Scale
    {
        /** @var Entity\Scale $scale */
        $scale = $this->scaleTable->findByIdAndAnr($id, $anr);

        if ($data['max'] < $scale->getMax() || $data['min'] > $scale->getMin()) {
            if ($scale->getType() === Entity\ScaleSuperClass::TYPE_IMPACT) {
                $this->adjustInstancesAndConsequencesImpacts($anr, $data);
            } else {
                $this->adjustInstanceRisksValues($anr, $data, $scale->getType());
            }
        }

        /* Delete comments of the scale that are out of the range. */
        foreach ($scale->getScaleComments() as $comment) {
            if ($comment->getScaleValue() < $data['min'] || $comment->getScaleValue() > $data['max']) {
                $this->scaleCommentTable->remove($comment, false);
            }
        }

        $scale->setMin((int)$data['min'])
            ->setMax((int)$data['max'])
            ->setUpdater($this->connectedUser->getEmail());

        $this->scaleTable->save($scale);

        return $scale;
    }

    private function adjustInstanceRisksValues(Entity\Anr $anr, array $data, int $scaleType): void
    {
        /** @var Entity\InstanceRisk[] $instancesRisks */
        $instancesRisks = $this->instanceRiskTable->findByAnr($anr);

        foreach ($instancesRisks as $instanceRisk) {
            $ratesValuesToUpdate = [];
            if ($scaleType === Entity\ScaleSuperClass::TYPE_THREAT) {
                if ($instanceRisk->getThreatRate() === -1) {
                    continue;
                }

                if ($instanceRisk->getThreatRate() < $data['min']) {
                    $ratesValuesToUpdate['threatRate'] = $data['min'];
                } elseif ($instanceRisk->getThreatRate() > $data['max']) {
                    $ratesValuesToUpdate['threatRate'] = $data['max'];
                }
            } elseif ($scaleType === Entity\ScaleSuperClass::TYPE_VULNERABILITY) {
                if ($instanceRisk->getVulnerabilityRate() !== -1
                    && $instanceRisk->getVulnerabilityRate() < $data['min']
                ) {
                    $ratesValuesToUpdate['vulnerabilityRate'] = $data['min'];
                } elseif ($instanceRisk->getVulnerabilityRate() !== -1
                    && $instanceRisk->getVulnerabilityRate() > $data['max']
                ) {
                    $ratesValuesToUpdate['vulnerabilityRate'] = $data['max'];
                }
                if ($instanceRisk->getReductionAmount() !== -1
                    && $instanceRisk->getReductionAmount() < $data['min']
                ) {
                    $ratesValuesToUpdate['reductionAmount'] = $data['min'];
                } elseif ($instanceRisk->getReductionAmount() !== -1
                    && $instanceRisk->getReductionAmount() > $data['max']
                ) {
                    $ratesValuesToUpdate['reductionAmount'] = $data['max'];
                }
            }

            if (!empty($ratesValuesToUpdate)) {
                $this->instanceRiskService->updateInstanceRiskRates($instanceRisk, $ratesValuesToUpdate);
            }
        }
    }

    /**
     * Adjusts all the instances' and their consequences' impacts to align with min / max scales values in case if
     * they are out of bounds.
     */
    private function adjustInstancesAndConsequencesImpacts(Entity\Anr $anr, array $data): void
    {
        /** @var Entity\Instance[] $rootInstances */
        $rootInstances = $this->instanceTable->findRootsByAnr($anr);
        foreach ($rootInstances as $rootInstance) {
            $this->performAdjustmentForInstanceAndItsConsequences($rootInstance, $data);
            $this->adjustInstancesAndConsequencesImpactsOfChildren($rootInstance, $data);
        }
        $this->instanceTable->flush();
    }

    private function adjustInstancesAndConsequencesImpactsOfChildren(Entity\Instance $instance, array $data): void
    {
        foreach ($instance->getChildren() as $childInstance) {
            $this->performAdjustmentForInstanceAndItsConsequences($childInstance, $data);
            $this->adjustInstancesAndConsequencesImpactsOfChildren($childInstance, $data);
        }
    }

    private function performAdjustmentForInstanceAndItsConsequences(Entity\Instance $instance, array $data): void
    {
        if ($this->validateAndAdjustImpacts($instance, $data)) {
            foreach ($instance->getInstanceConsequences() as $instanceConsequence) {
                if ($this->validateAndAdjustImpacts($instanceConsequence, $data)) {
                    $this->instanceConsequenceTable->save($instanceConsequence, false);
                }
            }
            foreach ($instance->getInstanceRisks() as $instanceRisk) {
                $this->instanceRiskService->recalculateRiskRates($instanceRisk);
                $this->instanceRiskTable->save($instanceRisk, false);
            }

            $this->instanceTable->save($instance, false);
        }
    }

    /**
     * @return bool True if any adjustments were made.
     */
    private function validateAndAdjustImpacts(
        Entity\Instance|Entity\InstanceConsequence $ciaScalesObject,
        array $data
    ): bool {
        $areImpactsAdjusted = false;
        if ($ciaScalesObject->getConfidentiality() !== -1 && $ciaScalesObject->getConfidentiality() < $data['min']) {
            $ciaScalesObject->setConfidentiality($data['min']);
            $areImpactsAdjusted = true;
        } elseif ($ciaScalesObject->getConfidentiality() > $data['max']) {
            $ciaScalesObject->setConfidentiality($data['max']);
            $areImpactsAdjusted = true;
        }

        if ($ciaScalesObject->getIntegrity() !== -1 && $ciaScalesObject->getIntegrity() < $data['min']) {
            $ciaScalesObject->setIntegrity($data['min']);
            $areImpactsAdjusted = true;
        } elseif ($ciaScalesObject->getIntegrity() > $data['max']) {
            $ciaScalesObject->setIntegrity($data['max']);
            $areImpactsAdjusted = true;
        }

        if ($ciaScalesObject->getAvailability() !== -1 && $ciaScalesObject->getAvailability() < $data['min']) {
            $ciaScalesObject->setAvailability($data['min']);
            $areImpactsAdjusted = true;
        } elseif ($ciaScalesObject->getAvailability() > $data['max']) {
            $ciaScalesObject->setAvailability($data['max']);
            $areImpactsAdjusted = true;
        }

        return $areImpactsAdjusted;
    }
}
