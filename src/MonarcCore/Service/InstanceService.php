<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Instance;
use MonarcCore\Model\Table\AmvTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ScaleTable;


/**
 * Instance Service
 *
 * Class InstanceService
 * @package MonarcCore\Service
 */
class InstanceService extends AbstractService
{
    protected $dependencies = ['anr', 'asset', 'object'];
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];

    protected $amvTable;
    protected $anrTable;
    protected $assetTable;
    protected $objectTable;
    protected $scaleTable;
    protected $instanceRiskService;
    protected $objectObjectService;

    const LEVEL_ROOT    = 1; //instance de racine d'un objet
    const LEVEL_LEAF    = 2; //instance d'une feuille d'un objet
    const LEVEL_INTER   = 3; //instance d'une noeud intermédiaire d'un objet

    /**
     * Instantiate object to anr
     *
     * @param $anrId
     * @param $objectId
     * @param $parentId
     * @param $position
     * @param $impacts
     * @throws \Exception
     */
    public function instantiateObjectToAnr($anrId, $objectId, $parentId, $position, $impacts) {

        //retrieve object properties
        $object = $this->get('objectTable')->getEntity($objectId);
        $data = [
            'object' => $objectId,
            'parent' => ($parentId) ? $parentId : null,
            'position' => $position,
            'anr' => $anrId,
        ];
        $commonProperties = ['name1', 'name2', 'name3', 'name4', 'label1', 'label2', 'label3', 'label4'];
        foreach($commonProperties as $commonProperty) {
            $data[$commonProperty] = $object->$commonProperty;
        }

        //set impacts
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $parent = ($parentId) ? $table->getEntity($parentId) : null;
        $this->updateImpacts($anrId, $impacts, $parent, $data);
        
        //asset
        if (isset($object->asset)) {
            $data['asset'] = $object->asset->id;
        }

        //create object
        $class = $this->get('entity');
        $instance = new $class();
        $instance->setLanguage($this->getLanguage());
        $instance->exchangeArray($data);

        //entity dependencies
        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        //parent and root
        $parent = ($parentId) ? $table->getEntity($parentId) : null;
        $instance->setParent($parent);
        $root = ($parentId) ? $this->getRoot($instance) : null;
        $instance->setRoot($root);

        //retrieve children
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($objectId);

        //level
        $this->updateLevels($parent, $children, $instance);

        $id = $table->createInstanceToAnr($instance, $anrId, $parentId, $position);

        //instances risks
        /** @var AmvTable $amvTable */
        $amvTable = $this->get('amvTable');

        $anrAmvs = $amvTable->findByAnrAndAsset($anrId, $object->asset->id);

        foreach ($anrAmvs as $anrAmv) {

            $data = [
                'anr' => $anrId,
                'amv' => $anrAmv->id,
                'asset' => $anrAmv->asset->id,
                'instance' => $id,
                'threat' => $anrAmv->threat->id,
                'vulnerability' => $anrAmv->vulnerability->id,
            ];

            /** @var InstanceRiskService $instanceRiskService */
            $instanceRiskService = $this->get('instanceRiskService');
            $instanceRiskService->create($data);
        }

        foreach($children as $child) {
            $impacts = [
                'c' => '-1',
                'i' => '-1',
                'd' => '-1'
            ];
            $this->instantiateObjectToAnr($anrId, $child->child->id, $id, $child->position, $impacts);
        }
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function updateInstance($anrId, $id, $data, $historic = []){

        $historic[] = $id;

        /** @var InstanceTable $table */
        $table = $this->get('table');

        $entity = $table->getEntity($id);

        if (!$entity) {
            throw new \Exception('Instance not exist', 412);
        }

        $entity->setDbAdapter($table->getDb());
        $entity->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        //impacts
        $impacts = [
            'c' => $data['c'],
            'i' => $data['i'],
            'd' => $data['d'],
        ];
        $this->updateImpacts($anrId, $impacts, $entity->parent, $data);

        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        //retrieve children
        $children = $table->getEntityByFields(['parent' => $id]);

        $id = $this->get('table')->save($entity);

        foreach($children as $child) {
            $fields = [
                'id', 'asset', 'object',
                'name1', 'name2', 'name3', 'name4',
                'label1', 'label2', 'label3', 'label4',
                'c', 'i', 'd', 'ch', 'ih', 'dh'
            ];

            $child = $this->get('table')->get($child->id, $fields);

            foreach ($this->dependencies as $dependency){
                $child[$dependency] = $child[$dependency]->id;
            }

            if ($child['ch']) {
                $child['c'] = -1;
            }
            if ($child['ih']) {
                $child['i'] = -1;
            }
            if ($child['dh']) {
                $child['d'] = -1;
            }

            $this->updateInstance($anrId, $child['id'], $child, $historic);
        }

        //if source object is global, reverberate to other instance with the same source object
        if ($entity->object->scope == ObjectService::SCOPE_GLOBAL) {
            //retrieve instance with same object source
            $brothers = $table->getEntityByFields(['object' => $entity->object->id]);
            foreach($brothers as $brother) {
                if (($brother->id != $id) && (!in_array($brother->id, $historic))) {
                    $this->updateInstance($anrId, $brother->id, $data, $historic);
                }
            }
        }

        return $id;
    }

    /**
     * Find By Anr
     *
     * @param $anrId
     * @return mixed
     */
    public function findByAnr($anrId) {

        return $this->get('table')->findByAnr($anrId);
    }


    /**
     * Update impacts
     *
     * @param $anrId
     * @param $newImpacts
     * @param $parent
     * @param $data
     * @throws \Exception
     */
    public function updateImpacts($anrId, $newImpacts, $parent, &$data) {
        /** @var ScaleTable $scaleTable */
        $scaleTable = $this->get('scaleTable');
        $scale = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => ScaleService::TYPE_IMPACT])[0];
        foreach($newImpacts as $key => $impact) {
            $data[$key] = $impact;
            $data[$key . 'h'] = ($impact < 0) ? true : false;

            if ($impact < 0) { //retrieve parent value
                if ($parent) {
                    $data[$key] = $parent->$key;
                } else {
                    $data[$key] = -1;
                }
            } else { //verify min and max
                if (($impact < $scale->min) || ($impact > $scale->max)) {
                    throw new \Exception('Impact must be between ' . $scale->min . ' and ' . $scale->max , 412);
                }
            }
        }
    }

    /**
     * Update level
     *
     * @param $parent
     * @param $children
     * @param $instance
     */
    public function updateLevels($parent, $children, &$instance) {
        if (!$parent) {
            $instance->setLevel(self::LEVEL_ROOT);
        } else if (!count($children)) {
            $instance->setLevel(self::LEVEL_LEAF);
        } else {
            $instance->setLevel(self::LEVEL_INTER);
        }
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data){

        /** @var InstanceTable $table */
        $table = $this->get('table');

        $instance = $table->getEntity($id);
        $instance->setLanguage($this->getLanguage());
        $instance->exchangeArray($data, true);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        if (array_key_exists('parent', $data)) {

            $parent = ($data['parent']) ? $table->getEntity($data['parent']) : null;
            $root = ($data['parent']) ? $this->getRoot($instance) : null;

            $instance->setParent($parent);
            $instance->setRoot($root);

            $table->save($instance);

            $this->changeRootForChildren($id, $root);
        }


    }

    /**
     * Change root for children
     *
     * @param $instanceId
     * @param $root
     */
    public function changeRootForChildren($instanceId, $root) {

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $children = $table->getEntityByFields(['parent' => $instanceId]);

        foreach($children as $child) {

            $child->setRoot($root);

            $table->save($child);

            $this->changeRootForChildren($child->id, $root);
        }
    }
}