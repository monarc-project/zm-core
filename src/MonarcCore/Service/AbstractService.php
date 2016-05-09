<?php
namespace MonarcCore\Service;

abstract class AbstractService extends AbstractServiceFactory
{
    use \MonarcCore\Model\GetAndSet;

    protected $serviceFactory;

    protected $connectedUser;

    /**
     * @return null
     */
    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    /**
     * @return mixed
     */
    public function getConnectedUser()
    {
        return $this->connectedUser;
    }

    /**
     * @param mixed $connectedUser
     * @return AbstractService
     */
    public function setConnectedUser($connectedUser)
    {
        $this->connectedUser = $connectedUser;
        return $this;
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
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return array
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null){

        $filter = $this->parseFrontendFilter($filter, $this->filterColumns);
        $order = $this->parseFrontendOrder($order);

        $qb = $this->buildFilteredQuery($page, $limit, $order, $filter);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id){

        return $this->getRepository()->find($id);
    }

    /**
     * Update Entity
     *
     * @param $id
     * @param $data
     */
    public function update($id, $data) {

        $entity = $this->getEntity($id);
        $entity->exchangeArray($data);

        $connectedUser = trim($this->getConnectedUser()['firstname'] . " " . $this->getConnectedUser()['lastname']);

        $entity->set('updater', $connectedUser);
        $entity->set('updatedAt',new \DateTime());

        $this->objectManager->persist($entity);
        $this->objectManager->flush();
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        $entity = $this->getEntity($id);

        $this->objectManager->remove($entity);
        $this->objectManager->flush();
    }

    /**
     * Get Filtered Count
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {

        $order = $this->parseFrontendOrder($order);
        $filter = $this->parseFrontendFilter($filter, $this->filterColumns);

        $qb = $this->buildFilteredQuery($page, $limit, $order, $filter);
        $qb->select('count(t.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Build Filtered Query
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    protected function buildFilteredQuery($page = 1, $limit = 25, $order = null, $filter = null) {

        $qb = $this->getRepository()->createQueryBuilder('t');

        // Add filter in WHERE xx LIKE %y% OR zz LIKE %y% ...
        if ($filter != null && is_array($filter)) {
            $isFirst = true;

            $searchIndex = 1;

            foreach ($filter as $colName => $value) {
                if ($isFirst) {
                    $qb->where("t.$colName LIKE :filter_$searchIndex");
                    $qb->setParameter(":filter_$searchIndex",  '%' . $value . '%');
                    $isFirst = false;
                } else {
                    $qb->orWhere("t.$colName LIKE :filter_$searchIndex");
                    $qb->setParameter(":filter_$searchIndex",  '%' . $value . '%');
                }

                ++$searchIndex;
            }
        }

        // Add order
        if ($order != null) {
            $qb->orderBy('t.' . $order[0], $order[1]);
        }

        // Add limit and offset
        $qb->setFirstResult(($page - 1) * $limit);
        $qb->setMaxResults($limit);

        return $qb;
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

        foreach ($columns as $c) {
            $output[$c] = $filter;
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
     * Save Entity
     *
     * @param $entity
     */
    protected function save($entity) {

        $connectedUser = trim($this->getConnectedUser()['firstname'] . " " . $this->getConnectedUser()['lastname']);

        $entity->set('creator', $connectedUser);
        $entity->set('createdAt',new \DateTime());

        $this->objectManager->persist($entity);
        $this->objectManager->flush();

        return $entity->getId();
    }

    /**
     * Add Model
     * @param $entity
     * @param $data
     */
    public function addModel($entity, &$data){
        if (array_key_exists('models', $data)) {
            if (!is_array($data['models'])) {
                $data['models'] = [$data['models']];
            }

            $modelService = $this->getModelService();
            foreach ($data['models'] as $model) {
                $modelEntity = $modelService->getEntity($model);

                $entity->addModel($modelEntity);
            }

            unset($data['models']);
        }

        return $entity;
    }






}
