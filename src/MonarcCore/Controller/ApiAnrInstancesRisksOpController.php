<?php

namespace MonarcCore\Controller;
use Zend\View\Model\JsonModel;

class ApiAnrInstancesRisksOpController extends AbstractController
{
    protected $name = 'instances-oprisks';

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

    public function update($id, $data){
        $risk = $this->getService()->update($id, $data);
        unset($risk['anr']);
        unset($risk['instance']);
        unset($risk['object']);
        unset($risk['rolfRisk']);

        return new JsonModel(['riskOp' => $risk]);
    }
}

