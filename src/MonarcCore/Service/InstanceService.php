<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\ModelTable;
use MonarcCore\Model\Entity\Model;

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
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );

    protected $anrTable;
    protected $assetTable;
    protected $objectTable;

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

        $object = $this->get('objectTable')->getEntity($objectId);

        $commonProperties = [
            'name1', 'name2', 'name3', 'name4',
            'label1', 'label2', 'label3', 'label4',
            'description1', 'description2', 'description3', 'description4',
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
        $instance = $this->get('entity');
        $instance->exchangeArray($data);

        //entity dependencies
        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        //parent and root
        $parentValue = $instance->get('parent');
        if (!empty($parentValue)) {
            $parentEntity = $this->get('table')->getEntity($parentValue);
            $instance->setParent($parentEntity);

            $rootEntity = $this->getRoot($instance);
            $instance->setRoot($rootEntity);
        } else {
            $instance->setParent(null);
            $instance->setRoot(null);
        }

        $this->get('table')->createInstanceToAnr($instance, $anrId, $parentId, $position);
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