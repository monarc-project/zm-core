<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Instance;
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
    protected $dependencies = array('asset', 'object');

    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4'
    );

    protected $anrTable;
    protected $assetTable;
    protected $objectTable;
    protected $scaleTable;
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

        if ($position == 0) {
            $position = 1;
        }

        //retrieve object proprerties
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
        if ($parentId) {
            $parentEntity = $table->getEntity($parentId);
            $instance->setParent($parentEntity);

            $rootEntity = $this->getRoot($instance);
            $instance->setRoot($rootEntity);
        } else {
            $instance->setParent(null);
            $instance->setRoot(null);
        }

        //retrieve children
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($objectId);

        //level
        $this->updateLevels($parent, $children, $instance);

        $id = $table->createInstanceToAnr($instance, $anrId, $parentId, $position);

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
    public function updateInstance($anrId, $id, $data){

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

            $this->updateInstance($anrId, $child['id'], $child);
        }

        //if source object is global, reverberate to other instance with the same source object
        /*if ($entity->object->scope == ObjectService::SCOPE_GLOBAL) {
            //retrieve instance with same object source
            $brothers = $table->getEntityByFields(['object' => $entity->object->id]);
            foreach($brothers as $brother) {
                if ($brother->id != $id) {
                    $this->updateInstance($anrId, $brother->id, $data);
                }
            }
        }*/

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
}