<?php
namespace MonarcCore\Service;

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
        for ($i = 1; $i <= 3; $i++) {
            if (isset($data['scales'][$i]['min'])) {
                if (isset($data['scales'][$i]['max']) && $data['scales'][$i]['min'] > $data['scales'][$i]['max']) {
                    $data['scales'][$i]['min'] = 0;
                }
            } else {
                $data['scales'][$i]['min'] = 0;
            }
        }
        $scales = [
            [
                'anr' => $anrId,
                'type' => 1,
                'min' => $data['scales'][1]['min'],
                'max' => (isset($data['scales'][1]['max']) ? $data['scales'][1]['max'] : 3),
            ],
            [
                'anr' => $anrId,
                'type' => 2,
                'min' => $data['scales'][2]['min'],
                'max' => (isset($data['scales'][2]['max']) ? $data['scales'][2]['max'] : 4),
            ],
            [
                'anr' => $anrId,
                'type' => 3,
                'min' => $data['scales'][3]['min'],
                'max' => (isset($data['scales'][3]['max']) ? $data['scales'][3]['max'] : 3),
            ],
        ];
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
     * Duplicate Anr
     *
     * @param $anr
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
                        if (!empty($entity->instance->id) && !empty($clones['instance'][$entity->instance->id])) {
                            $newEntity->set('instance', $clones['instance'][$entity->instance->id]);
                        } else {
                            $newEntity->set('instance', null);
                        }
                        break;
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
     * Export Anr
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function exportAnr(&$data)
    {
        if (empty($data['id'])) {
            throw new \Exception('Anr to export is required', 412);
        }
        if (empty($data['password'])) {
            $data['password'] = '';
        }
        $filename = "";

        $with_eval = isset($data['assessments']) && $data['assessments'];

        $return = $this->generateExportArray($data['id'], $filename, $with_eval);
        $data['filename'] = $filename;

        return base64_encode($this->encrypt(json_encode($return), $data['password']));
    }

    /**
     * Generate Export Array
     *
     * @param $id
     * @param string $filename
     * @param bool $with_eval
     * @return array
     * @throws \Exception
     */
    public function generateExportArray($id, &$filename = "", $with_eval = false)
    {
        if (empty($id)) {
            throw new \Exception('Anr to export is required', 412);
        }
        $entity = $this->get('table')->getEntity($id);

        if (!$entity) {
            throw new \Exception('Entity `id` not found.');
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('label' . $this->getLanguage()));

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
        foreach ($instances as $i) {
            $return['instances'][$i->id] = $instanceService->generateExportArray($i->id, $f, $with_eval, $with_scale);
        }

        if ($with_eval) {
            // scales
            $return['scales'] = array();
            $scaleTable = $this->get('scaleTable');
            $scales = $scaleTable->getEntityByFields(['anr' => $entity->get('id')]);
            $scalesArray = array(
                'min' => 'min',
                'max' => 'max',
                'type' => 'type',
            );
            foreach ($scales as $s) {
                $return['scales'][$s->type] = $s->getJsonArray($scalesArray);
            }
        }
        return $return;
    }
}