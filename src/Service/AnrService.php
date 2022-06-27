<?php
/**
* @link      https://github.com/monarc-project for the canonical source repository
* @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
* @license   MONARC is licensed under GNU Affero General Public License version 3
*/

namespace Monarc\Core\Service;

use DateTime;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\OperationalRiskScale;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\MonarcObjectTable;
use Monarc\Core\Model\Table\ScaleCommentTable;
use Monarc\Core\Model\Table\ScaleTable;

/**
* Anr Service
*
* Class AnrService
* @package Monarc\Core\Service
*/
class AnrService extends AbstractService
{
    protected $scaleService;
    protected $anrObjectCategoryTable;
    protected $MonarcObjectTable;
    protected $instanceTable;
    protected $instanceConsequenceTable;
    protected $instanceRiskTable;
    protected $instanceRiskOpTable;
    protected $scaleTable;
    protected $scaleImpactTypeTable;
    protected $scaleCommentTable;
    protected $operationalRiskScaleTable;
    protected $operationalRiskScaleTypeTable;
    protected $operationalRiskScaleCommentTable;
    protected $translationTable;
    /** @var OperationalRiskScaleService */
    protected $operationalRiskScaleService;
    protected $instanceService;
    protected $questionTable;
    protected $questionChoiceTable;
    protected $threatTable;
    protected $interviewTable;
    protected $deliveryTable;
    protected $referentialTable;
    protected $measureTable;
    protected $measureMeasureTable;
    protected $soaCategoryTable;
    protected $soaTable;
    protected $recordTable;
    protected $recordService;
    protected $configService;
    protected $operationalRiskScalesExportService;
    protected $anrMetadatasOnInstancesExportService;
    protected $soaScaleCommentExportService;

    /**
    * @inheritdoc
    */
    public function create($data, $last = true)
    {
        /** @var Anr $anr */
        $anr = $this->get('entity');
        $anr->exchangeArray($data);

        $anr->setCreator($this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname());

        $anrId = $this->get('table')->save($anr);

        //scales
        for ($i = 1; $i <= 3; $i++) {
            if (isset($data['scales'][$i]['min'])) {
                if (isset($data['scales'][$i]['max']) && $data['scales'][$i]['min'] > $data['scales'][$i]['max']) {
                    $data['scales'][$i]['min'] = 0;
                }
            } else {
                $data['scales'][$i]['min'] = 0;
            }
        }
        $scales = [];
        for ($i = 1; $i <= 3; $i++) {
            $scales[] = [
                'anr' => $anrId,
                'type' => $i,
                'min' => $data['scales'][$i]['min'],
                'max' => (isset($data['scales'][$i]['max']) ? $data['scales'][$i]['max'] : ($i == 2?4:3)),
            ];
        }
        $i = 1;
        $nbScales = count($scales);
        foreach ($scales as $scale) {
            /** @var ScaleService $scaleService */
            $scaleService = $this->get('scaleService');
            $scaleService->create($scale, ($i == $nbScales));
            $i++;
        }

        foreach ([OperationalRiskScale::TYPE_IMPACT, OperationalRiskScale::TYPE_LIKELIHOOD] as $type) {
            $this->operationalRiskScaleService->createScale(
                $anr,
                $type,
                0,
                4
            );
        }

        return $anrId;
    }

    /**
    * Duplicates an anr
    * @param Anr $anr The source ANR object
    * @return Anr The new ANR object
    */
    public function duplicate($anr)
    {
        //duplicate anr
        $newAnr = clone $anr;
        $newAnr->setId(null);
        $suffix = ' (copié le ' . date('m/d/Y à H:i') . ')';
        for ($i = 1; $i <= 4; $i++) {
            $newAnr->set('label' . $i, $newAnr->get('label' . $i) . $suffix);
        }

        /** @var AnrTable $anrTable */
        $anrTable = $this->get('table');
        $anrTable->save($newAnr);

        //duplicate objects
        $i = 1;
        $nbObjects = count($newAnr->objects);
        foreach ($newAnr->objects as $object) {
            //add anr to object
            $object->addAnr($newAnr);

            /** @var MonarcObjectTable $MonarcObjectTable */
            $MonarcObjectTable = $this->get('MonarcObjectTable');
            $MonarcObjectTable->save($object, ($i == $nbObjects));
            $i++;
        }

        //duplicate object categories, instances, instances consequences, instances risks, instances risks op
        $clones = [];
        $array = [
            'scale',
            'scaleImpactType',
            'scaleComment',
            'operationalRiskScale',
            'operationalRiskScaleType',
            'operationalRiskScaleComment',
            'translation',
            'anrObjectCategory',
            'instance',
            'instanceConsequence',
            'instanceRisk',
            'instanceRiskOp'
        ];
        foreach ($array as $value) {
            $table = $this->get($value . 'Table');
            $order = [];
            switch ($value) {
                case 'instance':
                    $order['level'] = 'ASC';
                    break;
            }

            $entities = method_exists($table, 'getEntityByFields') ?
                $table->getEntityByFields(['anr' => $anr->id], $order) :
                $table->findByAnr($anr);
            foreach ($entities as $entity) {
                $newEntity = clone $entity;
                if (method_exists($newEntity, 'set')) {
                    $newEntity->set('id', null);
                }
                $newEntity->setAnr($newAnr);

                switch ($value) {
                    case 'instance':
                        if (!empty($entity->root->id) && !empty($clones['instance'][$entity->root->id])) {
                            $newEntity->set('root', $clones['instance'][$entity->root->id]);
                        } else {
                            $newEntity->set('root', null);
                        }
                        if (!empty($entity->parent->id) && !empty($clones['instance'][$entity->parent->id])) {
                            $newEntity->set('parent', $clones['instance'][$entity->parent->id]);
                        } else {
                            $newEntity->set('parent', null);
                        }
                        break;
                    case 'instanceConsequence':
                        if (!empty($entity->instance->id) && !empty($clones['instance'][$entity->instance->id])) {
                            $newEntity->set('instance', $clones['instance'][$entity->instance->id]);
                        } else {
                            $newEntity->set('instance', null);
                        }
                        if (!empty($entity->scaleImpactType->id) &&
                                !empty($clones['scaleImpactType'][$entity->scaleImpactType->id])) {
                            $newEntity->set(
                                'scaleImpactType',
                                $clones['scaleImpactType'][$entity->scaleImpactType->id]
                            );
                        } else {
                            $newEntity->set('scaleImpactType', null);
                        }
                        break;
                    case 'instanceRisk':
                    case 'instanceRiskOp':
                        if (!empty($entity->instance->id) && !empty($clones['instance'][$entity->instance->id])) {
                            $newEntity->set('instance', $clones['instance'][$entity->instance->id]);
                        } else {
                            $newEntity->set('instance', null);
                        }
                        break;
                    case 'scaleImpactType':
                        if (!empty($entity->scale->id) && !empty($clones['scale'][$entity->scale->id])) {
                            $newEntity->set('scale', $clones['scale'][$entity->scale->id]);
                        } else {
                            $newEntity->set('scale', null);
                        }
                        break;
                    case 'scaleComment':
                        if (!empty($entity->scale->id) && !empty($clones['scale'][$entity->scale->id])) {
                            $newEntity->set('scale', $clones['scale'][$entity->scale->id]);
                        } else {
                            $newEntity->set('scale', null);
                        }
                        if (!empty($entity->scaleImpactType->id) &&
                            !empty($clones['scaleImpactType'][$entity->scaleImpactType->id])) {
                            $newEntity->set(
                                'scaleImpactType',
                                $clones['scaleImpactType'][$entity->scaleImpactType->id]
                            );
                        } else {
                            $newEntity->set('scaleImpactType', null);
                        }
                        break;
                    case 'operationalRiskScaleType':
                        if (!empty($entity->getOperationalRiskScale()->getId()) &&
                                !empty($clones['operationalRiskScale'][$entity->getOperationalRiskScale()->getId()])) {
                            $newEntity->setOperationalRiskScale(
                                $clones['operationalRiskScale'][$entity->getOperationalRiskScale()->getId()]
                            );
                        }
                        break;
                    case 'operationalRiskScaleComment':
                        if (!empty($entity->getOperationalRiskScale()->getId()) &&
                            !empty($clones['operationalRiskScale'][$entity->getOperationalRiskScale()->getId()])) {
                            $newEntity->setOperationalRiskScale(
                                $clones['operationalRiskScale'][$entity->getOperationalRiskScale()->getId()]
                            );
                        }
                        if (!empty($entity->getOperationalRiskScaleType()) &&
                            !empty(
                                $clones['operationalRiskScaleType'][$entity->getOperationalRiskScaleType()->getId()]
                            )) {
                            $newEntity->setOperationalRiskScaleType(
                                $clones['operationalRiskScaleType'][$entity->getOperationalRiskScaleType()->getId()]
                            );
                        }
                        break;
                }

                $table->save($newEntity);

                if (method_exists($newEntity, 'get')) {
                    $clones[$value][$entity->get('id')] = $newEntity;
                } else {
                    $clones[$value][$entity->getId()] = $newEntity;
                }
            }
        }
        return $newAnr;
    }

    /**
    * Exports an ANR, optionaly encrypted, for later re-import
    * @param array $data An array with the ANR 'id' and 'password' for encryption
    * @return string JSON file, optionaly encrypted
    * @throws Exception If the ANR is invalid
    */
    public function exportAnr(&$data)
    {
        if (empty($data['id'])) {
            throw new Exception('Anr to export is required', 412);
        }

        $filename = '';

        $withEval = isset($data['assessments']) && $data['assessments'];
        $withControls = isset($data['controls']) && $data['controls'];
        $withRecommendations = isset($data['recommendations']) && $data['recommendations'];
        $withMethodSteps = isset($data['methodSteps']) && $data['methodSteps'];
        $withInterviews = isset($data['interviews']) && $data['interviews'];
        $withSoas = isset($data['soas']) && $data['soas'];
        $withRecords = isset($data['records']) && $data['records'];
        $exportedAnr = json_encode(
            $this->generateExportArray(
                $data['id'],
                $filename,
                $withEval,
                $withControls,
                $withRecommendations,
                $withMethodSteps,
                $withInterviews,
                $withSoas,
                $withRecords
            )
        );
        $data['filename'] = $filename;

        if (!empty($data['password'])) {
            $exportedAnr = $this->encrypt($exportedAnr, $data['password']);
        }

        return $exportedAnr;
    }

    /**
    * Generates the array to be exported into a file when calling {#exportAnr}
    * @see #exportAnr
    *
    * @param int $id The ANR id
    * @param string $filename The output filename
    * @param bool $withEval If true, exports evaluations as well
    *
    * @return array The data array that should be saved
    * @throws Exception If the ANR or an entity is not found
    */
    public function generateExportArray(
        $id,
        &$filename = "",
        $withEval = false,
        $withControls = false,
        $withRecommendations = false,
        $withMethodSteps = false,
        $withInterviews = false,
        $withSoas = false,
        $withRecords = false
    ) {
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('table');
        $anr = $anrTable->findById($id);

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $anr->get('label' . $this->getLanguage()));

        $return = [
            'type' => 'anr',
            'monarc_version' => $this->get('configService')->getAppVersion()['appVersion'],
            'export_datetime' => (new DateTime())->format('Y-m-d H:i:s'),
            'instances' => [],
            'with_eval' => $withEval,
        ];

        /** @var InstanceService $instanceService */
        $instanceService = $this->get('instanceService');
        $table = $this->get('instanceTable');
        $instances = $table->getEntityByFields(['anr' => $anr->getId(), 'parent' => null], ['position'=>'ASC']);
        $f = '';
        foreach ($instances as $instance) {
            $return['instances'][$instance->getId()] = $instanceService->generateExportArray(
                $instance->getId(),
                $f,
                $withEval,
                false,
                $withControls,
                $withRecommendations
            );
        }

        /** @var AnrMetadatasOnInstancesExportService $anrMetadatasOnInstancesExportService */
        $anrMetadatasOnInstancesExportService = $this->get('anrMetadatasOnInstancesExportService');
        $return['anrMetadatasOnInstances'] = $anrMetadatasOnInstancesExportService->generateExportArray($anr);

        if ($withEval) {
            // TODO: Soa functionality is related only to FrontOffice.
            if ($withSoas) {
                // soaScaleComment
                $soaScaleCommentExportService = $this->get('soaScaleCommentExportService');
                $return['soaScaleComment'] = $soaScaleCommentExportService->generateExportArray(
                    $anr
                );

                // referentials
                $return['referentials'] = [];
                $referentialTable = $this->get('referentialTable');
                $referentials = $referentialTable->getEntityByFields(['anr' => $anr->getId()]);
                $referentialsArray = [
                    'uuid' => 'uuid',
                    'label1' => 'label1',
                    'label2' => 'label2',
                    'label3' => 'label3',
                    'label4' => 'label4'
                ];
                foreach ($referentials as $r) {
                    $return['referentials'][$r->getUuid()] = $r->getJsonArray($referentialsArray);
                }

                // measures
                $return['measures'] = [];
                $measureTable = $this->get('measureTable');
                $measures = $measureTable->getEntityByFields(['anr' => $anr->getId()]);
                $measuresArray = [
                    'uuid' => 'uuid',
                    'referential' => 'referential',
                    'category' => 'category',
                    'code' => 'code',
                    'label1' => 'label1',
                    'label2' => 'label2',
                    'label3' => 'label3',
                    'label4' => 'label4',
                    'status' => 'status'
                ];
                foreach ($measures as $m) {
                    $newMeasure = $m->getJsonArray($measuresArray);
                    $newMeasure['referential'] = $m->getReferential()->getUuid();
                    $newMeasure['category'] = $m->getCategory() ?
                        $m->getCategory()->get('label' . $this->getLanguage()) : '';
                    $return['measures'][$m->getUuid()] = $newMeasure;
                }

                // measures-measures
                $return['measuresMeasures'] = [];
                $measureMeasureTable = $this->get('measureMeasureTable');
                $measuresMeasures = $measureMeasureTable->getEntityByFields(['anr' => $anr->getId()]);
                foreach ($measuresMeasures as $mm) {
                    $newMeasureMeasure = [];
                    $newMeasureMeasure['father'] = $mm->getFather();
                    $newMeasureMeasure['child'] = $mm->getChild();
                    $return['measuresMeasures'][] = $newMeasureMeasure;
                }

                // soacategories
                $return['soacategories'] = [];
                $soaCategoryTable = $this->get('soaCategoryTable');
                $soaCategories = $soaCategoryTable->getEntityByFields(['anr' => $anr->getId()]);
                $soaCategoriesArray = [
                    'referential' => 'referential',
                    'label1' => 'label1',
                    'label2' => 'label2',
                    'label3' => 'label3',
                    'label4' => 'label4',
                    'status' => 'status'
                ];
                foreach ($soaCategories as $c) {
                    $newSoaCategory = $c->getJsonArray($soaCategoriesArray);
                    $newSoaCategory['referential'] = $c->getReferential()->getUuid();
                    $return['soacategories'][] = $newSoaCategory;
                }

                // soas
                $return['soas'] = [];
                $soaTable = $this->get('soaTable');
                $soas = $soaTable->getEntityByFields(['anr' => $anr->getId()]);
                $soasArray = [
                    'remarks' => 'remarks',
                    'evidences' => 'evidences',
                    'actions' => 'actions',
                    'EX' => 'EX',
                    'LR' => 'LR',
                    'CO' => 'CO',
                    'BR' => 'BR',
                    'BP' => 'BP',
                    'RRA' => 'RRA',
                ];
                foreach ($soas as $s) {
                    $newSoas = $s->getJsonArray($soasArray);
                    if ($s->getSoaScaleComment() !== null) {
                        $newSoas['soaScaleComment'] = $s->getSoaScaleComment()->getId();
                    }
                    $newSoas['measure_id'] = $s->getMeasure()->getUuid();
                    $return['soas'][] = $newSoas;
                }
            }

            // operational risk scales
            /** @var OperationalRiskScalesExportService $operationalRiskScalesExportService */
            $operationalRiskScalesExportService = $this->get('operationalRiskScalesExportService');
            $return['operationalRiskScales'] = $operationalRiskScalesExportService->generateExportArray($anr);

            // scales
            $return['scales'] = [];
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scales = $scaleTable->findByAnr($anr);
            foreach ($scales as $scale) {
                $return['scales'][$scale->getType()] = [
                    'id' => $scale->getId(),
                    'min' => $scale->getMin(),
                    'max' => $scale->getMax(),
                    'type' => $scale->getType(),
                ];
            }

            /** @var ScaleCommentTable $scaleCommentTable */
            $scaleCommentTable = $this->get('scaleCommentTable');
            $scaleComments = $scaleCommentTable->findByAnr($anr);
            foreach ($scaleComments as $scaleComment) {
                $scaleCommentId = $scaleComment->getId();
                $return['scalesComments'][$scaleCommentId] = [
                    'id' => $scaleCommentId,
                    'scaleIndex' => $scaleComment->getScaleIndex(),
                    'scaleValue' => $scaleComment->getScaleValue(),
                    'comment1' => $scaleComment->getComment(1),
                    'comment2' => $scaleComment->getComment(2),
                    'comment3' => $scaleComment->getComment(3),
                    'comment4' => $scaleComment->getComment(4),
                    'scale' => [
                        'id' => $scaleComment->getScale()->getId(),
                        'type' => $scaleComment->getScale()->getType()
                    ],
                ];
                if ($scaleComment->getScaleImpactType() !== null) {
                    $return['scalesComments'][$scaleCommentId]['scaleImpactType'] = [
                        'id' => $scaleComment->getScaleImpactType()->getId(),
                        'type' => $scaleComment->getScaleImpactType()->getType(),
                        'position' => $scaleComment->getScaleImpactType()->getPosition(),
                        'labels' => [
                            'label1' => $scaleComment->getScaleImpactType()->getLabel(1),
                            'label2' => $scaleComment->getScaleImpactType()->getLabel(2),
                            'label3' => $scaleComment->getScaleImpactType()->getLabel(3),
                            'label4' => $scaleComment->getScaleImpactType()->getLabel(4),
                        ],
                        'isSys' => $scaleComment->getScaleImpactType()->isSys(),
                        'isHidden' => $scaleComment->getScaleImpactType()->isHidden(),
                    ];
                }
            }

            if ($withMethodSteps) {
                //Risks analysis method data
                $return['method']['steps'] = [
                    'initAnrContext' => $anr->initAnrContext,
                    'initEvalContext' => $anr->initEvalContext,
                    'initRiskContext' => $anr->initRiskContext,
                    'initDefContext' => $anr->initDefContext,
                    'modelImpacts' => $anr->modelImpacts,
                    'modelSummary' => $anr->modelSummary,
                    'evalRisks' => $anr->evalRisks,
                    'evalPlanRisks' => $anr->evalPlanRisks,
                    'manageRisks' => $anr->manageRisks,
                ];

                $return['method']['data'] = [
                    'contextAnaRisk' => $anr->contextAnaRisk,
                    'contextGestRisk' => $anr->contextGestRisk,
                    'synthThreat' => $anr->synthThreat,
                    'synthAct' => $anr->synthAct,
                ];



                $deliveryTable = $this->get('deliveryTable');
                for ($i = 0; $i <= 5; $i++) {
                    $deliveries = $deliveryTable->getEntityByFields(
                        ['anr' => $anr->getId(),
                        'typedoc' => $i ],
                        ['id'=>'ASC']
                    );
                    $deliveryArray = [
                        'id' => 'id',
                        'typedoc' => 'typedoc',
                        'name' => 'name',
                        'status' => 'status',
                        'version' => 'version',
                        'classification' => 'classification',
                        'respCustomer' => 'respCustomer',
                        'respSmile' => 'respSmile',
                        'summaryEvalRisk' => 'summaryEvalRisk',
                    ];
                    foreach ($deliveries as $d) {
                        $return['method']['deliveries'][$d->typedoc] = $d->getJsonArray($deliveryArray);
                    }
                }
                $questionTable = $this->get('questionTable');
                $questions = $questionTable->getEntityByFields(['anr' => $anr->getId()], ['position'=>'ASC']);
                $questionArray = [
                    'id' => 'id',
                    'mode' => 'mode',
                    'multichoice' => 'multichoice',
                    'label1' => 'label1',
                    'label2' => 'label2',
                    'label3' => 'label3',
                    'label4' => 'label4',
                    'response' => 'response',
                    'type' => 'type',
                    'position' => 'position',

                ];

                foreach ($questions as $q) {
                    $return['method']['questions'][$q->position] = $q->getJsonArray($questionArray);
                }

                $questionChoiceTable = $this->get('questionChoiceTable');
                $questionsChoices = $questionChoiceTable->getEntityByFields(['anr' => $anr->getId()]);
                $questionChoiceArray = [
                    'question' => 'question',
                    'position' => 'position',
                    'label1' => 'label1',
                    'label2' => 'label2',
                    'label3' => 'label3',
                    'label4' => 'label4',
                ];
                foreach ($questionsChoices as $qc) {
                    $return['method']['questionChoice'][$qc->id] = $qc->getJsonArray($questionChoiceArray);
                    $return['method']['questionChoice'][$qc->id]['question'] = $qc->question->id;
                }
            }
            //import thresholds
            $return['method']['thresholds'] = [
                'seuil1' => $anr->seuil1,
                'seuil2' => $anr->seuil2,
                'seuilRolf1' => $anr->seuilRolf1,
                'seuilRolf2' => $anr->seuilRolf2,
            ];
            // manage the interviews
            if ($withInterviews) {
                $interviewTable = $this->get('interviewTable');
                $interviews = $interviewTable->getEntityByFields(['anr' => $anr->getId()], ['id'=>'ASC']);
                $interviewArray = [
                    'id' => 'id',
                    'date' => 'date',
                    'service' => 'service',
                    'content' => 'content',
                ];

                foreach ($interviews as $i) {
                    $return['method']['interviews'][$i->id] = $i->getJsonArray($interviewArray);
                }
            }

            $threatTable = $this->get('threatTable');
            $threats = $threatTable->getEntityByFields(['anr' => $anr->getId()]);
            $threatArray = [
                'uuid' => 'uuid',
                'code' => 'code',
                'label1' => 'label1',
                'label2' => 'label2',
                'label3' => 'label3',
                'label4' => 'label4',
                'description1' => 'description1',
                'description2' => 'description2',
                'description3' => 'description3',
                'description4' => 'description4',
                'c' => 'c',
                'i' => 'i',
                'a' => 'a',
                'trend' => 'trend',
                'comment' => 'comment',
                'qualification' => 'qualification',
            ];


            foreach ($threats as $t) {
                $threatUuid = $t->getUuid();
                $return['method']['threats'][$threatUuid] = $t->getJsonArray($threatArray);
                if (isset($t->theme->id)) {
                    $return['method']['threats'][$threatUuid]['theme']['id'] = $t->theme->id;
                    $return['method']['threats'][$threatUuid]['theme']['label' . $this->getLanguage()] =
                        $t->theme->get('label' . $this->getLanguage());
                    $return['method']['threats'][$threatUuid]['theme']['label1'] = $t->theme->label1;
                    $return['method']['threats'][$threatUuid]['theme']['label2'] = $t->theme->label2;
                    $return['method']['threats'][$threatUuid]['theme']['label3'] = $t->theme->label3;
                    $return['method']['threats'][$threatUuid]['theme']['label4'] = $t->theme->label4;
                }
            }

            // manage the GDPR records
            if ($withRecords) {
                $recordService = $this->get('recordService');
                $table = $this->get('recordTable');
                $records = $table->getEntityByFields(['anr' => $anr->getId()], ['id'=>'ASC']);
                $f = '';
                foreach ($records as $r) {
                    $return['records'][$r->id] = $recordService->generateExportArray($r->id, $f);
                }
            }
        }
        return $return;
    }
}
