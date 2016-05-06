<?php
namespace MonarcCore\Service;

abstract class AbstractService extends AbstractServiceFactory
{
    use \MonarcCore\Model\GetAndSet;

    protected $serviceFactory;

    protected $connectedUser;

    protected $repository;

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

    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {

        $order = $this->parseFrontendOrder($order);
        $filter = $this->parseFrontendFilter($filter, $this->filterColumns);

        $qb = $this->buildFilteredQuery($page, $limit, $order, $filter);
        $qb->select('count(t.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

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

    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    protected function parseFrontendOrder($order) {
        if ($order == null) {
            return null;
        } else if (substr($order, 0, 1) == '-') {
            return array(substr($order, 1), 'ASC');
        } else {
            return array($order, 'DESC');
        }
    }

    protected function parseFrontOrder($order) {
        if ($order == null) {
            return null;
        } else if (substr($order, 0, 1) == '-') {
            return array(substr($order, 1), 'ASC');
        } else {
            return array($order, 'DESC');
        }
    }

    protected function parseFrontendFilter($filter, $columns = array()) {
        $output = array();

        foreach ($columns as $c) {
            $output[$c] = $filter;
        }

        return $output;
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

    /**
     * @return EntityRepository
     */
    public function getRepository()
    {
        if(!$this->repository) {
            $this->repository = $this->objectManager->getRepository(Threat::class);
        }
        return $this->repository;
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
        $order = $this->parseFrontOrder($order);

        if (is_null($page)) {
            $page = 1;
        }

        return $this->getRepository()->findBy(
            $filter,
            $order,
            $limit,
            ($page - 1) * $limit
        );
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
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        $entity = $this->getEntity($id);

        $this->objectManager->remove($entity);
        $this->objectManager->flush();
    }
}
