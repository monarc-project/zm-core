<?php
/**
* @link      https://github.com/monarc-project for the canonical source repository
* @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
* @license   MONARC is licensed under GNU Affero General Public License version 3
*/

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Anr;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\MonarcObjectTable;

/**
* Anr Service
*
* Class AnrService
* @package MonarcCore\Service
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


    /**
    * @inheritdoc
    */
    public function create($data, $last = true)
    {
        //anr
        $entity = $this->get('entity');
        $entity->exchangeArray($data);
        $anrId = $this->get('table')->save($entity);

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
        for($i = 1; $i <= 3; $i++){
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
        $array = ['scale', 'scaleImpactType', 'scaleComment', 'anrObjectCategory', 'instance', 'instanceConsequence', 'instanceRisk', 'instanceRiskOp'];
        foreach ($array as $value) {
            $table = $this->get($value . 'Table');
            $order = [];
            switch ($value) {
                case 'instance':
                $order['level'] = 'ASC';
                break;
            }
            $entities = $table->getEntityByFields(['anr' => $anr->id], $order);
            foreach ($entities as $entity) {
                $newEntity = clone $entity;
                $newEntity->set('id', null);
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
                    if (!empty($entity->scaleImpactType->id) && !empty($clones['scaleImpactType'][$entity->scaleImpactType->id])) {
                        $newEntity->set('scaleImpactType', $clones['scaleImpactType'][$entity->scaleImpactType->id]);
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
                    if (!empty($entity->scaleImpactType->id) && !empty($clones['scaleImpactType'][$entity->scaleImpactType->id])) {
                        $newEntity->set('scaleImpactType', $clones['scaleImpactType'][$entity->scaleImpactType->id]);
                    } else {
                        $newEntity->set('scaleImpactType', null);
                    }
                    break;
                }

                $table->save($newEntity);

                $clones[$value][$entity->get('id')] = $newEntity;
            }
        }
        return $newAnr;
    }

    /**
    * Exports an ANR, optionaly encrypted, for later re-import
    * @param array $data An array with the ANR 'id' and 'password' for encryption
    * @return string JSON file, optionaly encrypted
    * @throws \MonarcCore\Exception\Exception If the ANR is invalid
    */
    public function exportAnr(&$data)
    {
        if (empty($data['id'])) {
            throw new \MonarcCore\Exception\Exception('Anr to export is required', 412);
        }

        $filename = "";

        $with_eval = isset($data['assessments']) && $data['assessments'];
        //$with_controls_reco = isset($data['controls_reco']) && $data['controls_reco'];
        $with_controls = isset($data['controls']) && $data['controls'];
        $with_recommendations = isset($data['recommendations']) && $data['recommendations'];
        $with_methodSteps = isset($data['methodSteps']) && $data['methodSteps'];
        $with_interviews = isset($data['interviews']) && $data['interviews'];
        $exportedAnr = json_encode($this->generateExportArray($data['id'], $filename, $with_eval, $with_controls, $with_recommendations, $with_methodSteps, $with_interviews));
        $data['filename'] = $filename;

        if (! empty($data['password'])) {
            $exportedAnr = $this->encrypt($exportedAnr, $data['password']);
        }

        return $exportedAnr;
    }

    /**
    * Generates the array to be exported into a file when calling {#exportAnr}
    * @see #exportAnr
    * @param int $id The ANR id
    * @param string $filename The output filename
    * @param bool $with_eval If true, exports evaluations as well
    * @return array The data array that should be saved
    * @throws \MonarcCore\Exception\Exception If the ANR or an entity is not found
    */
    public function generateExportArray($id, &$filename = "", $with_eval = false, $with_controls = false, $with_recommendations = false, $with_methodSteps = false, $with_interviews = false)
    {
        if (empty($id)) {
            throw new \MonarcCore\Exception\Exception('Anr to export is required', 412);
        }
        $entity = $this->get('table')->getEntity($id);

        if (!$entity) {
            throw new \MonarcCore\Exception\Exception('Entity `id` not found.');
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('label' . $this->getLanguage()));

        $return = [
            'type' => 'anr',
            'version' => $this->getVersion(),
            'instances' => [],
            'with_eval' => $with_eval,
            //'with_controls_reco' => $with_controls_reco,
        ];

        $instanceService = $this->get('instanceService');
        $table = $this->get('instanceTable');
        $instances = $table->getEntityByFields(['anr' => $entity->get('id'), 'parent' => null], ['position'=>'ASC']);
        $f = '';
        $with_scale = false;
        foreach ($instances as $i) {
            $return['instances'][$i->id] = $instanceService->generateExportArray($i->id, $f, $with_eval, $with_scale, $with_controls, $with_recommendations);
        }


        if ($with_eval) {
            // referentials
            $return['referentials'] = [];
            $referentialTable = $this->get('referentialTable');
            $referentials = $referentialTable->getEntityByFields(['anr' => $entity->get('id')]);
            $referentialsArray = [
                'uniqid' => 'uniqid',
                'label1' => 'label1',
                'label2' => 'label2',
                'label3' => 'label3',
                'label4' => 'label4'
            ];
            foreach ($referentials as $r) {
                $return['referentials'][$r->getUniqid()->toString()] = $r->getJsonArray($referentialsArray);
            }

            // measures
            $return['measures'] = [];
            $measureTable = $this->get('measureTable');
            $measures = $measureTable->getEntityByFields(['anr' => $entity->get('id')]);
            $measuresArray = [
                'uniqid' => 'uniqid',
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
                $newMeasure['referential'] = $m->getReferential()->getUniqid()->toString();
                $newMeasure['category'] = $m->getCategory()->getCode();
                $return['measures'][$m->getUniqid()->toString()] = $newMeasure;
            }

            // measures-measures
            $return['measuresMeasures'] = [];
            $measureMeasureTable = $this->get('measureMeasureTable');
            $measuresMeasures = $measureMeasureTable->getEntityByFields(['anr' => $entity->get('id')]);
            $measuresMeasuresArray = [
                'uniqid' => 'uniqid'
            ];
            foreach ($measuresMeasures as $mm) {
                $newMeasureMeasure = [];
                $newMeasureMeasure['father'] = $mm->getFather()->toString();
                $newMeasureMeasure['child'] = $mm->getChild()->toString();
                $return['measuresMeasures'][] = $newMeasureMeasure;
            }

            // soacategories
            $return['soacategories'] = [];
            $soaCategoryTable = $this->get('soaCategoryTable');
            $soaCategories = $soaCategoryTable->getEntityByFields(['anr' => $entity->get('id')]);
            $soaCategoriesArray = [
                'referential' => 'referential',
                'code' => 'code',
                'label1' => 'label1',
                'label2' => 'label2',
                'label3' => 'label3',
                'label4' => 'label4',
                'status' => 'status'
            ];
            foreach ($soaCategories as $c) {
                $newSoaCategory = $c->getJsonArray($soaCategoriesArray);
                $newSoaCategory['referential'] = $c->getReferential()->getUniqid()->toString();
                $return['soacategories'][] = $newSoaCategory;
            }

            // scales
            $return['scales'] = [];
            $scaleTable = $this->get('scaleTable');
            $scales = $scaleTable->getEntityByFields(['anr' => $entity->get('id')]);
            $scalesArray = [
                'id' => 'id',
                'min' => 'min',
                'max' => 'max',
                'type' => 'type',
            ];
            foreach ($scales as $s) {
                $return['scales'][$s->type] = $s->getJsonArray($scalesArray);
            }

            $scaleCommentTable = $this->get('scaleCommentTable');
            for ($s=1; $s <= 3; $s++) {
                for ($i=$return['scales'][$s]['min']; $i <=$return['scales'][$s]['max'] ; $i++) {
                    $scaleComment = $scaleCommentTable->getEntityByFields(['anr' => $entity->get('id') , 'val' => $i , 'scale' => $return['scales'][$s]['id']]);
                    $scalesCommentArray = [
                        'id' => 'id',
                        'scale' => [],
                        'scaleImpactType' => [],
                        'val' => 'val',
                        'comment1' => 'comment1',
                        'comment2' => 'comment2',
                        'comment3' => 'comment3',
                        'comment4' => 'comment4',
                    ];
                    foreach ($scaleComment as $sc) {
                        $return['scalesComments'][$sc->id] = $sc->getJsonArray($scalesCommentArray);
                        $return['scalesComments'][$sc->id]['scale']['id'] = $sc->scale->id;
                        $return['scalesComments'][$sc->id]['scale']['type'] = $sc->scale->type;
                        if (null !== $sc->scaleImpactType) {
                            $return['scalesComments'][$sc->id]['scaleImpactType']['id'] = $sc->scaleImpactType->id;
                            $return['scalesComments'][$sc->id]['scaleImpactType']['position'] = $sc->scaleImpactType->position;
                        }
                    }
                }
            }
            if($with_methodSteps)
            {
                //Risks analysis method data
                $return['method']['steps'] = [
                    'initAnrContext' => $entity->initAnrContext,
                    'initEvalContext' => $entity->initEvalContext,
                    'initRiskContext' => $entity->initRiskContext,
                    'initDefContext' => $entity->initDefContext,
                    'modelImpacts' => $entity->modelImpacts,
                    'modelSummary' => $entity->modelSummary,
                    'evalRisks' => $entity->evalRisks,
                    'evalPlanRisks' => $entity->evalPlanRisks,
                    'manageRisks' => $entity->manageRisks,
                ];

                $return['method']['data'] = [
                    'contextAnaRisk' => $entity->contextAnaRisk,
                    'contextGestRisk' => $entity->contextGestRisk,
                    'synthThreat' => $entity->synthThreat,
                    'synthAct' => $entity->synthAct,
                ];



                $deliveryTable = $this->get('deliveryTable');
                for ($i=0; $i <= 4; $i++) {
                    $deliveries = $deliveryTable->getEntityByFields(['anr' => $entity->get('id') , 'typedoc' => $i ], ['id'=>'ASC']);
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
                $questions = $questionTable->getEntityByFields(['anr' => $entity->get('id')], ['position'=>'ASC']);
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
                $questionsChoices = $questionChoiceTable->getEntityByFields(['anr' => $entity->get('id')]);
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
                'seuil1' => $entity->seuil1,
                'seuil2' => $entity->seuil2,
                'seuilRolf1' => $entity->seuilRolf1,
                'seuilRolf2' => $entity->seuilRolf2,
            ];
            // manage the interviews
            if($with_interviews)
            {
                $interviewTable = $this->get('interviewTable');
                $interviews = $interviewTable->getEntityByFields(['anr' => $entity->get('id')], ['id'=>'ASC']);
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
            $threats = $threatTable->getEntityByFields(['anr' => $entity->get('id')]);
            $threatArray = [
                'id' => 'id',
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
                'd' => 'd',
                'trend' => 'trend',
                'theme' => [],
                'comment' => 'comment',
                'qualification' => 'qualification',
            ];

            foreach ($threats as $t) {

                $return['method']['threats'][$t->id] = $t->getJsonArray($threatArray);
                if (isset($t->theme->id)) {
                    $return['method']['threats'][$t->id]['theme']['id'] = $t->theme->id;
                    $return['method']['threats'][$t->id]['theme']['label' . $this->getLanguage()] = $t->theme->get('label' . $this->getLanguage());
                }
            }


        }
        return $return;
    }
}
