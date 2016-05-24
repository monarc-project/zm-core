<?php
namespace MonarcCore\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;
use Zend\Http\Response;

abstract class AbstractController extends AbstractRestfulController
{
    protected $service;

    public function __construct(\MonarcCore\Service\AbstractService $service)
    {
        $this->service = $service;
    }

    protected function getService()
    {
        return $this->service;
    }
    protected function methodNotAllowed()
    {
        $this->response->setStatusCode(405);
        throw new \Exception('Method Not Allowed');
    }

    public function getList()
    {
        return $this->methodNotAllowed();
    }
 
    public function get($id)
    {
        return $this->methodNotAllowed();
    }
 
    public function create($data)
    {
        return $this->methodNotAllowed();
    }
 
    public function update($id, $data)
    {
        return $this->methodNotAllowed();
    }
 
    public function delete($id)
    {
        return $this->methodNotAllowed();
    }

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