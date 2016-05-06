<?php
namespace MonarcCore\Service;

abstract class AbstractService extends AbstractServiceFactory
{
    use \MonarcCore\Model\GetAndSet;

    protected $serviceFactory;

    protected $connectedUser;

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
        /*if($serviceFactory instanceof \MonarcCore\Model\Table\AbstractEntityTable || $serviceFactory instanceof \MonarcCore\Model\Entity\AbstractEntity){
            $this->serviceFactory = $serviceFactory;
        }elseif(is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                if($v instanceof \MonarcCore\Model\Table\AbstractEntityTable || $v instanceof \MonarcCore\Model\Entity\AbstractEntity){
                    $this->set($k,$v);
                }
            }
        }*/


        if (is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                $this->set($k,$v);
            }
        } else {
            $this->serviceFactory = $serviceFactory;
        }
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
            return array(substr($order, 1) => 'ASC');
        } else {
            return array($order => 'DESC');
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
}
