<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity;
use Monarc\Core\Table;

class ScaleService
{
    private Table\ScaleTable $scaleTable;

    private Table\ScaleCommentTable $scaleCommentTable;

    private Table\InstanceTable $instanceTable;

    private Table\InstanceRiskTable $instanceRiskTable;

    private Table\InstanceConsequenceTable $instanceConsequenceTable;

    private ScaleImpactTypeService $scaleImpactTypeService;

    private InstanceRiskService $instanceRiskService;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        Table\ScaleTable $scaleTable,
        Table\ScaleCommentTable $scaleCommentTable,
        Table\InstanceTable $instanceTable,
        Table\InstanceRiskTable $instanceRiskTable,
        Table\InstanceConsequenceTable $instanceConsequenceTable,
        InstanceRiskService $instanceRiskService,
        ScaleImpactTypeService $scaleImpactTypeService,
        ConnectedUserService $connectedUserService
    ) {
        $this->scaleTable = $scaleTable;
        $this->scaleCommentTable = $scaleCommentTable;
        $this->instanceTable = $instanceTable;
        $this->instanceRiskTable = $instanceRiskTable;
        $this->instanceConsequenceTable = $instanceConsequenceTable;
        $this->scaleImpactTypeService = $scaleImpactTypeService;
        $this->instanceRiskService = $instanceRiskService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $formattedInputParams): array
    {
        $result = [];
        /** @var Entity\Scale[] $scales */
        $scales = $this->scaleTable->findByParams($formattedInputParams);
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
    public function create(Entity\Anr $anr, array $data): Entity\Scale
    {
        $scale = (new Entity\Scale($anr, $data))->setCreator($this->connectedUser->getEmail());

        if ($scale->getType() === Entity\ScaleSuperClass::TYPE_IMPACT) {
            $this->scaleImpactTypeService->createDefaultScaleImpactTypes($scale);
        }

        $this->scaleTable->save($scale);

        /** @var Entity\Scale $scale */
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
    protected function adjustInstancesAndConsequencesImpacts(Entity\Anr $anr, array $data): void
    {
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
     * @param object|Entity\Instance|Entity\InstanceConsequence $ciaScalesObject
     * @param array $data
     */
    private function validateAndAdjustImpacts(object $ciaScalesObject, array $data): bool
    {
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
