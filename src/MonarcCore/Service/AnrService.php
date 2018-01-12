<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Anr;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\ObjectTable;

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
    protected $objectTable;
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

            /** @var ObjectTable $objectTable */
            $objectTable = $this->get('objectTable');
            $objectTable->save($object, ($i == $nbObjects));
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

        $exportedAnr = json_encode($this->generateExportArray($data['id'], $filename, $with_eval));
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
    public function generateExportArray($id, &$filename = "", $with_eval = false)
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
        ];

        $instanceService = $this->get('instanceService');
        $table = $this->get('instanceTable');
        $instances = $table->getEntityByFields(['anr' => $entity->get('id'), 'parent' => null], ['position'=>'ASC']);
        $f = '';
        $with_scale = false;
        foreach ($instances as $i) {
            $return['instances'][$i->id] = $instanceService->generateExportArray($i->id, $f, $with_eval, $with_scale);
        }

        if ($with_eval) {
            // scales
            $return['scales'] = [];
            $scaleTable = $this->get('scaleTable');
            $scales = $scaleTable->getEntityByFields(['anr' => $entity->get('id')]);
            $scalesArray = [
                'min' => 'min',
                'max' => 'max',
                'type' => 'type',
            ];
            foreach ($scales as $s) {
                $return['scales'][$s->type] = $s->getJsonArray($scalesArray);
            }

            $scaleCommentTable = $this->get('scaleCommentTable');
            $scaleComment = $scaleCommentTable->getEntityByFields(['anr' => $entity->get('id')]);
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
                $return['scalesComments'][$sc->id]['scaleImpactType']['id'] = $sc->scaleImpactType->id;
                $return['scalesComments'][$sc->id]['scaleImpactType']['position'] = $sc->scaleImpactType->position;

            }

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

            $return['method']['thresholds'] = [
            'seuil1' => $entity->seuil1,
            'seuil2' => $entity->seuil2,
            'seuilRolf1' => $entity->seuilRolf1,
            'seuilRolf2' => $entity->seuilRolf2,
            ];

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
                  'label1' => 'label1',
                  'label2' => 'label2',
                  'label3' => 'label3',
                  'label4' => 'label4',
                ];
                foreach ($questionsChoices as $qc) {
                    $return['method']['questionChoice'][$qc->id] = $qc->getJsonArray($questionChoiceArray);
                    $return['method']['questionChoice'][$qc->id]['question'] = $qc->question->id;
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
                'theme' => 'theme',
                'comment' => 'comment',
                'qualification' => 'qualification',
            ];

            foreach ($threats as $t) {

                $return['method']['threats'][$t->id] = $t->getJsonArray($threatArray);
                $return['method']['threats'][$t->id]['theme'] = $t->theme->id;
            }


        }
        return $return;
    }
}
