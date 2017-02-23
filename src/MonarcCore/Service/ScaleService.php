<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Scale;
use MonarcCore\Model\Entity\ScaleComment;
use MonarcCore\Model\Table\InstanceConsequenceTable;
use MonarcCore\Model\Table\InstanceRiskOpTable;
use MonarcCore\Model\Table\InstanceRiskTable;
use MonarcCore\Model\Table\ScaleCommentTable;
use MonarcCore\Model\Table\ScaleImpactTypeTable;

/**
 * Scale Service
 *
 * Class ScaleService
 * @package MonarcCore\Service
 */
class ScaleService extends AbstractService
{
    protected $filterColumns = [];
    protected $config;
    protected $anrTable;
    protected $instanceConsequenceService;
    protected $instanceConsequenceTable;
    protected $instanceRiskOpService;
    protected $instanceRiskOpTable;
    protected $instanceRiskService;
    protected $instanceRiskTable;
    protected $scaleImpactTypeService;
    protected $scaleImpactTypeTable;
    /** @var ScaleCommentTable */
    protected $commentTable;
    protected $dependencies = ['anr'];
    protected $forbiddenFields = ['anr'];
    protected $types = [
        Scale::TYPE_IMPACT => 'impact',
        Scale::TYPE_THREAT => 'threat',
        Scale::TYPE_VULNERABILITY => 'vulnerability',
    ];

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {

        $scales = parent::getList($page, $limit, $order, $filter, $filterAnd);

        $types = $this->getTypes();

        foreach ($scales as $key => $scale) {
            $scales[$key]['type'] = $types[$scale['type']];
        }

        return $scales;
    }

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {

        //scale
        //$entity = $this->get('entity');
        $class = $this->get('entity');
        $entity = new $class();

        $entity->exchangeArray($data);
        $entity->setId(null);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $scaleId = $this->get('table')->save($entity);

        //scale type
        if ($entity->type == Scale::TYPE_IMPACT) {
            $langs = $this->get('entity')->getImpactLangues();

            $configLangStruct = $this->config->getlanguage();
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
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 1, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 2, 'label1' => $langs[$outLang[1]]['C'], 'label2' => $langs[$outLang[2]]['C'], 'label3' => $langs[$outLang[3]]['C'], 'label4' => $langs[$outLang[4]]['C'],
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 2, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 2, 'label1' => $langs[$outLang[1]]['I'], 'label2' => $langs[$outLang[2]]['I'], 'label3' => $langs[$outLang[3]]['I'], 'label4' => $langs[$outLang[4]]['I'],
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 3, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 2, 'label1' => $langs[$outLang[1]]['D'], 'label2' => $langs[$outLang[2]]['D'], 'label3' => $langs[$outLang[3]]['D'], 'label4' => $langs[$outLang[4]]['D'],
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 4, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 2, 'label1' => $langs[$outLang[1]]['R'], 'label2' => $langs[$outLang[2]]['R'], 'label3' => $langs[$outLang[3]]['R'], 'label4' => $langs[$outLang[4]]['R'],
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 5, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 2, 'label1' => $langs[$outLang[1]]['O'], 'label2' => $langs[$outLang[2]]['O'], 'label3' => $langs[$outLang[3]]['O'], 'label4' => $langs[$outLang[4]]['O'],
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 6, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 2, 'label1' => $langs[$outLang[1]]['L'], 'label2' => $langs[$outLang[2]]['L'], 'label3' => $langs[$outLang[3]]['L'], 'label4' => $langs[$outLang[4]]['L'],
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 7, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 2, 'label1' => $langs[$outLang[1]]['F'], 'label2' => $langs[$outLang[2]]['F'], 'label3' => $langs[$outLang[3]]['F'], 'label4' => $langs[$outLang[4]]['F'],
                ],
                [
                    'anr' => $data['anr'], 'scale' => $scaleId, 'type' => 8, 'isSys' => 1, 'isHidden' => 0,
                    'implicitPosition' => 2, 'label1' => $langs[$outLang[1]]['P'], 'label2' => $langs[$outLang[2]]['P'], 'label3' => $langs[$outLang[3]]['P'], 'label4' => $langs[$outLang[4]]['P'],
                ]
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
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        return parent::patch($id, $data);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id, $data)
    {
        $anrId = false;
        if (isset($data['anr'])) {
            $anrId = $data['anr'];
            unset($data['anr']);
        }

        $scale = $this->get('table')->getEntity($id);

        $result = parent::patch($id, $data);

        //retrieve scales impact types
        $scalesImpactTypesIds = [];
        /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
        $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
        $scaleImpactTypes = $scaleImpactTypeTable->getEntityByFields(['scale' => $id]);
        foreach ($scaleImpactTypes as $scaleImpactType) {
            $scalesImpactTypesIds[] = $scaleImpactType->id;
        }

        if ($anrId) {

            if ($scale->type == Scale::TYPE_IMPACT) {

                //update instances consequences associated
                /** @var InstanceConsequenceTable $instanceConsequenceTable */
                $instanceConsequenceTable = $this->get('instanceConsequenceTable');
                $instancesConsequences = $instanceConsequenceTable->getInstancesConsequences($anrId, $scalesImpactTypesIds);
                foreach ($instancesConsequences as $instanceConsequence) {
                    if (
                        (($instanceConsequence->c != -1) && (($instanceConsequence->c < $data['min']) || ($instanceConsequence->c > $data['max'])))
                        ||
                        (($instanceConsequence->i != -1) && (($instanceConsequence->i < $data['min']) || ($instanceConsequence->i > $data['max'])))
                        ||
                        (($instanceConsequence->d != -1) && (($instanceConsequence->d < $data['min']) || ($instanceConsequence->d > $data['max'])))
                    ) {
                        $dataConsequences = [];

                        if (($instanceConsequence->c != -1) && ($instanceConsequence->c < $data['min'])) {
                            $dataConsequences['c'] = $data['min'];
                        } else if (($instanceConsequence->c != -1) && ($instanceConsequence->c > $data['max'])) {
                            $dataConsequences['c'] = $data['max'];
                        }

                        if (($instanceConsequence->i != -1) && ($instanceConsequence->i < $data['min'])) {
                            $dataConsequences['i'] = $data['min'];
                        } else if (($instanceConsequence->i != -1) && ($instanceConsequence->i > $data['max'])) {
                            $dataConsequences['i'] = $data['max'];
                        }

                        if (($instanceConsequence->d != -1) && ($instanceConsequence->d < $data['min'])) {
                            $dataConsequences['d'] = $data['min'];
                        } else if (($instanceConsequence->d != -1) && ($instanceConsequence->d > $data['max'])) {
                            $dataConsequences['d'] = $data['max'];
                        }

                        $dataConsequences['anr'] = $anrId;

                        /** @var InstanceConsequenceService $instanceConsequenceService */
                        $instanceConsequenceService = $this->get('instanceConsequenceService');
                        $instanceConsequenceService->patchConsequence($instanceConsequence->id, $dataConsequences);
                    }
                }

                //update instances risks op associated
                /** @var InstanceRiskOpTable $instanceRiskOpTable */
                $instanceRiskOpTable = $this->get('instanceRiskOpTable');
                $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['anr' => $anrId]);
                $fields = ['netR', 'netO', 'netL', 'netP', 'brutR', 'brutO', 'brutL', 'brutP'];
                foreach ($instancesRisksOp as $instanceRiskOp) {
                    $dataRisksOp = [];
                    foreach ($fields as $field) {
                        if (($instanceRiskOp->$field != -1) && ($instanceRiskOp->$field < $data['min'])) {
                            $dataRisksOp[$field] = $data['min'];
                        } else if (($instanceRiskOp->$field != -1) && ($instanceRiskOp->$field > $data['max'])) {
                            $dataRisksOp[$field] = $data['max'];
                        }
                    }

                    if (count($dataRisksOp)) {
                        /** @var InstanceRiskOpService $instanceRiskOpService */
                        $instanceRiskOpService = $this->get('instanceRiskOpService');
                        $instanceRiskOpService->update($instanceRiskOp->id, $dataRisksOp);
                    }
                }
            } else if ($scale->type == Scale::TYPE_THREAT) {

                //update instances risks associated
                /** @var InstanceRiskTable $instanceRiskTable */
                $instanceRiskTable = $this->get('instanceRiskTable');
                $instancesRisks = $instanceRiskTable->getEntityByFields(['anr' => $anrId]);
                foreach ($instancesRisks as $instanceRisk) {
                    $dataRisks = [];
                    if (($instanceRisk->threatRate != -1) && ($instanceRisk->threatRate < $data['min'])) {
                        $dataRisks['threatRate'] = $data['min'];
                    } else if (($instanceRisk->threatRate != -1) && ($instanceRisk->threatRate > $data['max'])) {
                        $dataRisks['threatRate'] = $data['max'];
                    }


                    if (count($dataRisks)) {
                        $dataRisks['anr'] = $anrId;

                        /** @var InstanceRiskService $instanceRiskService */
                        $instanceRiskService = $this->get('instanceRiskService');
                        $instanceRiskService->patch($instanceRisk->id, $dataRisks);
                    }
                }

                //update instances risks op associated
                /** @var InstanceRiskOpTable $instanceRiskOpTable */
                $instanceRiskOpTable = $this->get('instanceRiskOpTable');
                $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['anr' => $anrId]);
                $fields = ['brutProb', 'netProb', 'targetedProb'];
                foreach ($instancesRisksOp as $instanceRiskOp) {
                    $dataRisksOp = [];
                    foreach ($fields as $field) {
                        if (($instanceRiskOp->$field != -1) && ($instanceRiskOp->$field < $data['min'])) {
                            $dataRisksOp[$field] = $data['min'];
                        } else if (($instanceRiskOp->$field != -1) && ($instanceRiskOp->$field > $data['max'])) {
                            $dataRisksOp[$field] = $data['max'];
                        }
                    }

                    if (count($dataRisksOp)) {
                        /** @var InstanceRiskOpService $instanceRiskOpService */
                        $instanceRiskOpService = $this->get('instanceRiskOpService');
                        $instanceRiskOpService->update($instanceRiskOp->id, $dataRisksOp);
                    }
                }
            } else if ($scale->type == Scale::TYPE_VULNERABILITY) {

                //update instances risks associated
                /** @var InstanceRiskTable $instanceRiskTable */
                $instanceRiskTable = $this->get('instanceRiskTable');
                $instancesRisks = $instanceRiskTable->getEntityByFields(['anr' => $anrId]);
                $fields = ['vulnerabilityRate', 'reductionAmount'];
                foreach ($instancesRisks as $instanceRisk) {
                    $dataRisks = [];
                    foreach ($fields as $field) {
                        if (($instanceRisk->$field != -1) && ($instanceRisk->$field < $data['min'])) {
                            $dataRisks[$field] = $data['min'];
                        } else if (($instanceRisk->$field != -1) && ($instanceRisk->$field > $data['max'])) {
                            $dataRisks[$field] = $data['max'];
                        }
                    }

                    if (count($dataRisks)) {
                        $dataRisks['anr'] = $anrId;

                        /** @var InstanceRiskService $instanceRiskService */
                        $instanceRiskService = $this->get('instanceRiskService');
                        $instanceRiskService->patch($instanceRisk->id, $dataRisks);
                    }
                }
            }


            // Delete comments for this scale that are out of the range
            /** @var ScaleComment[] $comments */
            $comments = $this->commentTable->getByScaleAndOutOfRange($scale->id, $scale->min, $scale->max);

            $i = 1;
            $nbComments = count($comments);
            if ($nbComments > 0) {
                foreach ($comments as $c) {
                    $this->commentTable->delete($c['id'], ($i == $nbComments));
                    $i++;
                }
            }

        }

        return $result;
    }
}