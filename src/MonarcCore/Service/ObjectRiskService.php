<?php
namespace MonarcCore\Service;

/**
 * Object Risk Service
 *
 * Class ObjectRiskService
 * @package MonarcCore\Service
 */
class ObjectRiskService extends AbstractService
{
    protected $objectTable;
    protected $amvTable;
    protected $assetTable;
    protected $threatTable;
    protected $vulnerabilityTable;

    protected $dependencies = ['object', 'amv', 'asset', 'threat', 'vulnerability'];

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        foreach($this->dependencies as $dependency) {
            $value = $entity->get($dependency);
            if (!empty($value)) {
                $tableName = preg_replace("/[0-9]/", "", $dependency)  . 'Table';
                $method = 'set' . ucfirst($dependency);
                $dependencyEntity = $this->get($tableName)->getEntity($value);
                $entity->$method($dependencyEntity);
            }
        }

        return $this->get('table')->save($entity);

    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id, $data){
        $entity = $this->get('table')->getEntity($id);
        $entity->exchangeArray($data);

        foreach($this->dependencies as $dependency) {
            $value = $entity->get($dependency);
            if (!empty($value)) {
                $tableName = preg_replace("/[0-9]/", "", $dependency)  . 'Table';
                $method = 'set' . ucfirst($dependency);
                $dependencyEntity = $this->get($tableName)->getEntity($value);
                $entity->$method($dependencyEntity);
            }
        }

        return $this->get('table')->save($entity);
    }
}