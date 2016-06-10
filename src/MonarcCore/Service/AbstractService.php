<?php
namespace MonarcCore\Service;

abstract class AbstractService extends AbstractServiceFactory
{
    use \MonarcCore\Model\GetAndSet;

    protected $serviceFactory;
    protected $table;
    protected $entity;
    protected $label;

    /**
     * @return null
     */
    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    /**
     * Construct
     *
     * AbstractService constructor.
     * @param null $serviceFactory
     */
    public function __construct($serviceFactory = null)
    {
        if (is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                $this->set($k,$v);
            }
        } else {
            $this->serviceFactory = $serviceFactory;
        }
    }

    /**
     * Parse Frontend Filter
     *
     * @param $filter
     * @param array $columns
     * @return array
     */
    protected function parseFrontendFilter($filter, $columns = array()) {
        $output = array();

        if ($columns) {
            foreach ($columns as $c) {
                $output[$c] = $filter;
            }
        }

        return $output;
    }

    /**
     * Parse Frontend Order
     *
     * @param $order
     * @return array|null
     */
    protected function parseFrontendOrder($order) {
        if(strpos($order, '_') !== false){
            $o = explode('_', $order);
            $order = "";
            foreach($o as $n => $oo){
                if($n <= 0){
                    $order = $oo;
                }else{
                    $order .= ucfirst($oo);
                }
            }
        }

        if ($order == null) {
            return null;
        } else if (substr($order, 0, 1) == '-') {
            return array(substr($order, 1), 'ASC');
        } else {
            return array($order, 'DESC');
        }
    }


    /**
     * Get Filtered Count
     *
     * @param null $filter
     * @return int
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {

        return $this->get('table')->countFiltered($page, $limit, $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns));
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return array
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null){

        return $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns)
        );
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id){

        return $this->get('table')->get($id);
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        return $this->get('table')->save($entity);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id,$data){

        $entity = $this->get('table')->getEntity($id);
        $entity->exchangeArray($data);

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        $this->get('table')->delete($id);
    }

    /**
     * Compare Entities
     *
     * @param $newEntity
     * @param $oldEntity
     * @return array
     */
    public function compareEntities($newEntity, $oldEntity){

        $exceptions = ['creator', 'created_at', 'updater', 'updated_at', 'inputFilter'];

        $diff = [];
        foreach ($newEntity->getArrayCopy() as $key => $value) {
            if (!in_array($key, $exceptions)) {
                if (in_array($key, $this->dependencies)) {
                    if ($oldEntity->$key->id != $value) {
                        $diff[] = $key . ': ' . $oldEntity->$key->id . ' => ' . $value;
                    }
                } else {
                    if ($oldEntity->$key != $value) {
                        $diff[] = $key . ': ' . $oldEntity->$key . ' => ' . $value;
                    }
                }
            }
        }

        return $diff;
    }

    /**
     * Historize update
     *
     * @param $type
     * @param $entity
     * @param $oldEntity
     */
    public function historizeUpdate($type, $entity, $oldEntity) {

        $diff = $this->compareEntities($entity, $oldEntity);

        if (count($diff)) {
            $this->historize($entity, $type, 'update', implode(', ', $diff));
        }
    }

    /**
     * Historize create
     *
     * @param $type
     * @param $entity
     */
    public function historizeCreate($type, $entity) {
        $this->historize($entity, $type, 'create', '');
    }

    /**
     * Historize delete
     *
     * @param $type
     * @param $entity
     */
    public function historizeDelete($type, $entity) {
        $this->historize($entity, $type, 'delete', '');
    }

    /**
     * Historize
     *
     * @param $entity
     * @param $type
     * @param $verb
     * @param $details
     */
    public function historize($entity, $type, $verb, $details) {

        $entityId = null;
        if (is_object($entity) && (property_exists($entity, 'id'))) {
            $entityId = $entity->id;
        } else if (is_array($entity) && (array_key_exists('id', $entity))) {
            $entityId = $entity['id'];
        }
        $data = [
            'type' => $type,
            'sourceId' => $entityId,
            'action' => $verb,
            'label1' => (is_object($entity) && property_exists($entity, 'label1')) ? $entity->label1 : $this->label[0],
            'label2' => (is_object($entity) && property_exists($entity, 'label2')) ? $entity->label2 : $this->label[1],
            'label3' => (is_object($entity) && property_exists($entity, 'label3')) ? $entity->label3 : $this->label[2],
            'label4' => (is_object($entity) && property_exists($entity, 'label4')) ? $entity->label4 : $this->label[3],
            'details' => $details,
        ];

        $historicalService = $this->get('historicalService');
        $historicalService->create($data);
    }

    /**
     * Format dependencies
     * 
     * @param $entity
     * @param $dependencies
     */
    public function formatDependencies(&$entity, $dependencies) {

        foreach($dependencies as $dependency) {

            if (!empty($entity[$dependency])) {
                $entity[$dependency] = $entity[$dependency]->getJsonArray();
                unset($entity[$dependency]['__initializer__']);
                unset($entity[$dependency]['__cloner__']);
                unset($entity[$dependency]['__isInitialized__']);
            }
        }
    }
}
