<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\ObjectTable;
use MonarcCore\Model\Table\ScaleCommentTable;

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

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {
        //anr
        $entity = $this->get('entity');
        $entity->exchangeArray($data);
        $anrId = $this->get('table')->save($entity);

        //scales
        $scales = [
            ['anr' => $anrId, 'type' => 1, 'min' => 0, 'max' => 3],
            ['anr' => $anrId, 'type' => 2, 'min' => 0, 'max' => 4],
            ['anr' => $anrId, 'type' => 3, 'min' => 0, 'max' => 3],
        ];
        foreach ($scales as $scale) {
            /** @var ScaleService $scaleService */
            $scaleService = $this->get('scaleService');
            $scaleService->create($scale);
        }

        return $anrId;
    }

    /**
     * Duplicate Anr
     *
     * @param $anr
     */
    public function duplicate($anr) {

        //duplicate anr
        $newAnr = clone $anr;
        $newAnr->setId(null);
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('table');
        $anrTable->save($newAnr);

        //duplicate objects
        $i = 1;
        foreach ($newAnr->objects as $object) {
            $last = ($i == count($newAnr->objects)) ? true : false;

            //add anr to object
            $object->addAnr($newAnr);

            /** @var ObjectTable $objectTable */
            $objectTable = $this->get('objectTable');
            $objectTable->save($object, $last);

            $i++;
        }

        //duplicate object categories, instances, instances consequences, instances risks, instances risks op
        $array = ['anrObjectCategory', 'instance', 'instanceConsequence', 'instanceRisk', 'instanceRiskOp'];
        foreach ($array as $value) {
            $k = 1;
            $table = $this->get($value . 'Table');
            $entities = $table->getEntityByFields(['anr' => $anr->id]);
            foreach ($entities as $entity) {
                $last = ($k == count($entities)) ? true : false;

                $newEntity = clone $entity;
                $newEntity->setAnr($newAnr);

                $table->save($newEntity, $last);

                $k++;
            }
        }

        //duplicate scales
        $l = 1;
        $scaleTable = $this->get('scaleTable');
        $scales = $scaleTable->getEntityByFields(['anr' => $anr->id]);
        $scalesNewIds = [];
        foreach ($scales as $scale) {
            $last = ($l == count($scales)) ? true : false;

            $newScale = clone $scale;
            $newScale->setAnr($newAnr);

            $scaleTable->save($newScale, $last);

            $scalesNewIds[$scale->id] = $newScale;

            $l++;
        }

        //duplicate scales impact types
        $m = 1;
        $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
        $scalesImpactTypes = $scaleImpactTypeTable->getEntityByFields(['anr' => $anr->id]);
        $scalesImpactTypesNewIds = [];
        foreach ($scalesImpactTypes as $scaleImpactType) {
            $last = ($m == count($scalesImpactTypes)) ? true : false;

            $newScaleImpactType = clone $scaleImpactType;
            $newScaleImpactType->setAnr($newAnr);
            $newScaleImpactType->setScale($scalesNewIds[$scaleImpactType->scale->id]);

            $scaleImpactTypeTable->save($newScaleImpactType, $last);

            $scalesImpactTypesNewIds[$scaleImpactType->id] = $newScaleImpactType;

            $m++;
        }

        //duplicate scales comments
        $n = 1;
        /** @var ScaleCommentTable $scaleCommentTable */
        $scaleCommentTable = $this->get('scaleCommentTable');
        $scalesComments = $scaleCommentTable->getEntityByFields(['anr' => $anr->id]);
        foreach ($scalesComments as $scaleComment) {
            $last = ($n == count($scalesComments)) ? true : false;

            $newScaleComment = clone $scaleComment;
            $newScaleComment->setAnr($newAnr);
            $newScaleComment->setScale($scalesNewIds[$scaleComment->scale->id]);
            $newScaleComment->setScaleImpactType($scalesImpactTypesNewIds[$scaleComment->scaleImpactType->id]);

            $scaleCommentTable->save($newScaleComment, $last);

            $n++;
        }

        return $newAnr;
    }

    public function exportAnr(&$data){
        if (empty($data['id'])) {
            throw new \Exception('Anr to export is required',412);
        }
        if (empty($data['password'])) {
            $data['password'] = '';
        }
        $filename = "";

        $with_eval = isset($data['assessments']) && $data['assessments'];

        $return = $this->generateExportArray($data['id'],$filename,$with_eval);
        $data['filename'] = $filename;

        return base64_encode($this->encrypt(json_encode($return),$data['password']));
    }

    public function generateExportArray($id, &$filename = "", $with_eval = false){
        if (empty($id)) {
            throw new \Exception('Anr to export is required',412);
        }
        $entity = $this->get('table')->getEntity($id);

        if (!$entity) {
            throw new \Exception('Entity `id` not found.');
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('label'.$this->getLanguage()));

        $return = array(
            'type' => 'anr',
            'version' => $this->getVersion(),
            'instances' => array(),
            'with_eval' => $with_eval,
        );

        $instanceService = $this->get('instanceService');
        $table = $this->get('instanceTable');
        $instances = $table->getEntityByFields(['anr' => $entity->get('id')]);
        $f = '';
        $with_scale = false;
        foreach($instances as $i){
            $return['instances'][$i->id] = $instanceService->generateExportArray($i->id,$f,$with_eval,$with_scale);
        }

        if($with_eval){
            // scales
            $return['scales'] = array();
            $scaleTable = $this->get('scaleTable');
            $scales = $scaleTable->getEntityByFields(['anr' => $entity->get('id')]);
            $scalesArray = array(
                'min'=>'min',
                'max'=>'max',
                'type'=>'type',
            );
            foreach ($scales as $s) {
                $return['scales'][$s->type] = $s->getJsonArray($scalesArray);
            }
        }
        return $return;
    }
}