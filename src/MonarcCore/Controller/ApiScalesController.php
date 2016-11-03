<?php

namespace MonarcCore\Controller;

use MonarcCore\Service\ScaleService;
use Zend\View\Model\JsonModel;

class ApiScalesController extends AbstractController
{
    protected $dependencies = ['anr'];
    protected $name = 'scales';

    /**
     * Get List
     *
     * @return JsonModel
     */
    public function getList()
    {
        $page = $this->params()->fromQuery('page');
        $limit = $this->params()->fromQuery('limit');
        $order = $this->params()->fromQuery('order');
        $filter = $this->params()->fromQuery('filter');
        $anrId = (int) $this->params()->fromRoute('anrId');

        $scales = $this->getService()->getList($page, $limit, $order, $filter, ['anr' => $anrId]);
        foreach($scales as $key => $scale){
            $this->formatDependencies($scales[$key], $this->dependencies);
        }

        return new JsonModel(array(
            'count' => $this->getService()->getFilteredCount($page, $limit, $order, $filter, ['anr' => $anrId]),
            $this->name => $scales
        ));
    }

    public function get($id)
    {
        return $this->methodNotAllowed();
    }

    public function create($data)
    {
        return $this->methodNotAllowed();
    }

    public function delete($id)
    {
        return $this->methodNotAllowed();
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

        $anrId = (int) $this->params()->fromRoute('anrId');

        if ($anrId) {
            $data['anr'] = $anrId;
        }
        $this->getService()->update($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }
}

