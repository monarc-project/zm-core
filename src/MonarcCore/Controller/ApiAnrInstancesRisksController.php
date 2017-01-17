<?php

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

class ApiAnrInstancesRisksController extends AbstractController
{
    protected $dependencies = ['anr','amv', 'asset', 'threat', 'vulnerability', 'instance'];
    protected $name = 'instances-risks';

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

    /**
     * Patch
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function patch($id, $data)
    {
        $data['anr'] = (int) $this->params()->fromRoute('anrid');

        $this->getService()->patch($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }


    /**
     * Update
     *
     * @param mixed $id
     * @param mixed $data
     * @return JsonModel
     */
    public function update($id, $data)
    {
        $data['anr'] = (int) $this->params()->fromRoute('anrid');

        $id = $this->getService()->update($id, $data);

        $entity = $this->getService()->getEntity($id);

        if (count($this->dependencies)) {
            foreach($this->dependencies as $d){
                unset($entity[$d]);
            }
        }

        return new JsonModel($entity);
    }
}

