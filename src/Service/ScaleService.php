<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\InstanceConsequence;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\Scale;
use Monarc\Core\Model\Entity\ScaleComment;
use Monarc\Core\Model\Entity\ScaleSuperClass;
use Monarc\Core\Model\Table\ScaleTable;
use Monarc\Core\Table\InstanceConsequenceTable;
use Monarc\Core\Model\Table\InstanceRiskTable;
use Monarc\Core\Model\Table\ScaleCommentTable;
use Monarc\Core\Table\InstanceTable;

/**
 * Scale Service
 *
 * Class ScaleService
 * @package Monarc\Core\Service
 */
class ScaleService extends AbstractService
{
    protected $filterColumns = [];
    protected $config;
    protected $anrTable;
    /** @var InstanceTable */
    protected $instanceTable;
    /** @var InstanceConsequenceTable */
    protected $instanceConsequenceTable;
    protected $instanceRiskService;
    protected $instanceRiskTable;
    protected $scaleImpactTypeService;
    /** @var ScaleCommentTable */
    protected $commentTable;
    protected $dependencies = ['anr'];
    protected $forbiddenFields = ['anr'];

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $scales = parent::getList($page, $limit, $order, $filter, $filterAnd);

        $types = ScaleSuperClass::getAvailableTypes();

        foreach ($scales as $key => $scale) {
            $scales[$key]['type'] = $types[$scale['type']];
        }

        return $scales;
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        //scale
        $class = $this->get('entity');
        /** @var Scale $scale */
        $scale = new $class();
        $scale->exchangeArray($data);
        $scale->setId(null);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($scale, $dependencies);

        $scale->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $scaleId = $this->get('table')->save($scale);

        //scale type
        if ($scale->type == Scale::TYPE_IMPACT) {
            $langs = $this->get('entity')->getImpactLangues();

            $configLangStruct = $this->config->getLanguage();
            $configLang = $configLangStruct['languages'];
            $outLang = [];

            foreach ($configLang as $index => $lang) {
                $outLang[$index] = strtolower(substr($lang, 0, 2));
            }

            for ($i = 0; $i <= 4; ++$i) {
                if (!isset($outLang[$i])) {
                    $outLang[$i] = '0';
                }
            }

            $scaleImpactTypes = [
                [
                    'anr' => $data['anr'],
                    'scale' => $scaleId,
                    'type' => 1,
                    'isSys' => 1,
                    'isHidden' => 0,
                    'implicitPosition' => 2,
                    'label1' => $langs[$outLang[1]]['C'],
                    'label2' => $langs[$outLang[2]]['C'],
                    'label3' => $langs[$outLang[3]]['C'],
                    'label4' => $langs[$outLang[4]]['C'],
                ],
                [
                    'anr' => $data['anr'],
                    'scale' => $scaleId,
                    'type' => 2,
                    'isSys' => 1,
                    'isHidden' => 0,
                    'implicitPosition' => 2,
                    'label1' => $langs[$outLang[1]]['I'],
                    'label2' => $langs[$outLang[2]]['I'],
                    'label3' => $langs[$outLang[3]]['I'],
                    'label4' => $langs[$outLang[4]]['I'],
                ],
                [
                    'anr' => $data['anr'],
                    'scale' => $scaleId,
                    'type' => 3,
                    'isSys' => 1,
                    'isHidden' => 0,
                    'implicitPosition' => 2,
                    'label1' => $langs[$outLang[1]]['D'],
                    'label2' => $langs[$outLang[2]]['D'],
                    'label3' => $langs[$outLang[3]]['D'],
                    'label4' => $langs[$outLang[4]]['D'],
                ],
                [
                    'anr' => $data['anr'],
                    'scale' => $scaleId,
                    'type' => 4,
                    'isSys' => 1,
                    'isHidden' => 0,
                    'implicitPosition' => 2,
                    'label1' => $langs[$outLang[1]]['R'],
                    'label2' => $langs[$outLang[2]]['R'],
                    'label3' => $langs[$outLang[3]]['R'],
                    'label4' => $langs[$outLang[4]]['R'],
                ],
                [
                    'anr' => $data['anr'],
                    'scale' => $scaleId,
                    'type' => 5,
                    'isSys' => 1,
                    'isHidden' => 0,
                    'implicitPosition' => 2,
                    'label1' => $langs[$outLang[1]]['O'],
                    'label2' => $langs[$outLang[2]]['O'],
                    'label3' => $langs[$outLang[3]]['O'],
                    'label4' => $langs[$outLang[4]]['O'],
                ],
                [
                    'anr' => $data['anr'],
                    'scale' => $scaleId,
                    'type' => 6,
                    'isSys' => 1,
                    'isHidden' => 0,
                    'implicitPosition' => 2,
                    'label1' => $langs[$outLang[1]]['L'],
                    'label2' => $langs[$outLang[2]]['L'],
                    'label3' => $langs[$outLang[3]]['L'],
                    'label4' => $langs[$outLang[4]]['L'],
                ],
                [
                    'anr' => $data['anr'],
                    'scale' => $scaleId,
                    'type' => 7,
                    'isSys' => 1,
                    'isHidden' => 0,
                    'implicitPosition' => 2,
                    'label1' => $langs[$outLang[1]]['F'],
                    'label2' => $langs[$outLang[2]]['F'],
                    'label3' => $langs[$outLang[3]]['F'],
                    'label4' => $langs[$outLang[4]]['F'],
                ],
                [
                    'anr' => $data['anr'],
                    'scale' => $scaleId,
                    'type' => 8,
                    'isSys' => 1,
                    'isHidden' => 0,
                    'implicitPosition' => 2,
                    'label1' => $langs[$outLang[1]]['P'],
                    'label2' => $langs[$outLang[2]]['P'],
                    'label3' => $langs[$outLang[3]]['P'],
                    'label4' => $langs[$outLang[4]]['P'],
                ],
            ];
            $i = 1;
            $nbScaleImpactTypes = count($scaleImpactTypes);
            foreach ($scaleImpactTypes as $scaleImpactType) {
                /** @var ScaleImpactTypeService $scaleImpactTypeService */
                $scaleImpactTypeService = $this->get('scaleImpactTypeService');
                $scaleImpactTypeService->create($scaleImpactType, ($i == $nbScaleImpactTypes));
                $i++;
            }
        }

        return $scaleId;
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        return parent::patch($id, $data);
    }

    /**
     * TODO: This method is never called on FO.
     */
    public function update($id, $data)
    {
        $anrId = false;
        if (isset($data['anr'])) {
            $anrId = $data['anr'];
            unset($data['anr']);
        }

        /** @var ScaleTable $scaleTable */
        $scaleTable = $this->get('table');
        $scale = $scaleTable->findById($id);
        $result = parent::patch($id, $data);

        if ($anrId) {
            switch ($scale->getType()) {
                case ScaleSuperClass::TYPE_IMPACT:
                    $this->adjustInstancesAndConsequencesImpacts($scale->getAnr(), $data);
                    break;
                case ScaleSuperClass::TYPE_THREAT:
                    //update instances risks associated
                    /** @var InstanceRiskTable $instanceRiskTable */
                    $instanceRiskTable = $this->get('instanceRiskTable');
                    $instancesRisks = $instanceRiskTable->findByAnr($scale->getAnr());
                    foreach ($instancesRisks as $instanceRisk) {
                        $dataRisks = [];
                        if ($instanceRisk->getThreatRate() !== -1 && $instanceRisk->getThreatRate() < $data['min']) {
                            $dataRisks['threatRate'] = $data['min'];
                        } elseif ($instanceRisk->getThreatRate() !== -1
                            && $instanceRisk->getThreatRate() > $data['max']
                        ) {
                            $dataRisks['threatRate'] = $data['max'];
                        }

                        if (!empty($dataRisks)) {
                            $dataRisks['anr'] = $anrId;

                            /** @var InstanceRiskService $instanceRiskService */
                            $instanceRiskService = $this->get('instanceRiskService');
                            $instanceRiskService->patch($instanceRisk->getId(), $dataRisks);
                        }
                    }
                    break;
                case ScaleSuperClass::TYPE_VULNERABILITY:
                    //update instances risks associated
                    /** @var InstanceRiskTable $instanceRiskTable */
                    $instanceRiskTable = $this->get('instanceRiskTable');
                    $instancesRisks = $instanceRiskTable->findByAnr($scale->getAnr());
                    $fields = ['vulnerabilityRate', 'reductionAmount'];
                    foreach ($instancesRisks as $instanceRisk) {
                        $dataRisks = [];
                        foreach ($fields as $field) {
                            if ($instanceRisk->$field !== -1 && $instanceRisk->$field < $data['min']) {
                                $dataRisks[$field] = $data['min'];
                            } elseif ($instanceRisk->$field !== -1 && $instanceRisk->$field > $data['max']) {
                                $dataRisks[$field] = $data['max'];
                            }
                        }

                        if (!empty($dataRisks)) {
                            $dataRisks['anr'] = $anrId;

                            /** @var InstanceRiskService $instanceRiskService */
                            $instanceRiskService = $this->get('instanceRiskService');
                            $instanceRiskService->patch($instanceRisk->getId(), $dataRisks);
                        }
                    }
                    break;
            }

            // Delete comments for this scale that are out of the range
            /** @var ScaleComment[] $comments */
            $comments = $this->commentTable->findByScaleAndOutOfRange($scale, $scale->getMin(), $scale->getMax());
            $i = 1;
            $nbComments = count($comments);
            foreach ($comments as $comment) {
                $this->commentTable->delete($comment['id'], $i == $nbComments);
                $i++;
            }
        }

        return $result;
    }

    /**
     * Adjusts all the instances' and their consequences' impacts to align with min / max scales values in case if
     * they are out of bounds.
     *
     * TODO: this is only applicable to the BO side as we don't allow any scales change if impacts are set on FO.
     */
    private function adjustInstancesAndConsequencesImpacts(AnrSuperClass $anr, array $data): void
    {
        $rootInstances = $this->instanceTable->findRootsByAnr($anr);
        foreach ($rootInstances as $rootInstance) {
            $this->performAdjustmentForInstanceAndItsConsequences($rootInstance, $data);
            $this->adjustInstancesAndConsequencesImpactsOfChildren($rootInstance, $data);
        }
        $this->instanceTable->flush();
    }

    private function adjustInstancesAndConsequencesImpactsOfChildren(InstanceSuperClass $instance, array $data): void
    {
        foreach ($instance->getChildren() as $childInstance) {
            $this->performAdjustmentForInstanceAndItsConsequences($childInstance, $data);
            $this->adjustInstancesAndConsequencesImpactsOfChildren($childInstance, $data);
        }
    }

    private function performAdjustmentForInstanceAndItsConsequences(InstanceSuperClass $instance, array $data): void
    {
        if ($this->validateAndAdjustImpacts($instance, $data)) {
            foreach ($instance->getInstanceConsequences() as $instanceConsequence) {
                if ($this->validateAndAdjustImpacts($instanceConsequence, $data)) {
                    $this->instanceConsequenceTable->save($instanceConsequence, false);
                }
            }
            $this->instanceTable->save($instance, false);
        }
    }

    /**
     * @param Instance|InstanceConsequence $ciaScalesObject
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
