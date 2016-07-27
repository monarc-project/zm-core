<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Instance;
use MonarcCore\Model\Table\InstanceTable;


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
    protected $objectObjectService;

    const LEVEL_ROOT    = 1; //instance de racine d'un objet
    const LEVEL_LEAF    = 2; //instance d'une feuille d'un objet
    const LEVEL_INTER   = 3; //instance d'une noeud intermédiaire d'un objet

    /**
     * Instantiate Object To Anr
     *
     * @param $anrId
     * @param $objectId
     * @param $parentId
     * @param $position
     */
    public function instantiateObjectToAnr($anrId, $objectId, $parentId, $position) {

        if ($position == 0) {
            $position = 1;
        }

        /** @var InstanceTable $table */
        $table = $this->get('table');

        $object = $this->get('objectTable')->getEntity($objectId);

        $commonProperties = [
            'name1', 'name2', 'name3', 'name4',
            'label1', 'label2', 'label3', 'label4',
            'c', 'i', 'd'
        ];

        $data = [
            'object' => $objectId,
            'parent' => ($parentId) ? $parentId : null,
            'position' => $position,
            'anr' => $anrId,
        ];

        foreach($commonProperties as $commonProperty) {
            $data[$commonProperty] = $object->$commonProperty;
        }

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
        if (!$parentId) {
            $instance->setLevel(self::LEVEL_ROOT);
        } else if (!count($children)) {
            $instance->setLevel(self::LEVEL_LEAF);
        } else {
            $instance->setLevel(self::LEVEL_INTER);
        }

        $id = $table->createInstanceToAnr($instance, $anrId, $parentId, $position);

        foreach($children as $child) {
            $this->instantiateObjectToAnr($anrId, $child->child->id, $id, $child->position);
        }
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
}