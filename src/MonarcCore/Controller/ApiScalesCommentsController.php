<?php

namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

class ApiScalesCommentsController extends AbstractController
{
    protected $dependencies = ['scale', 'scaleTypeImpact'];
    protected $name = 'comments';

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
        $scale = (int) $this->params()->fromRoute('scaleId');

        $comments = $this->getService()->getList($page, $limit, $order, $filter, ['anr' => $anrId, 'scale' => $scale]);
        foreach($comments as $key => $type){
            $this->formatDependencies($comments[$key], $this->dependencies);
        }

        return new JsonModel(array(
            'count' => count($comments),
            'anr' => $anrId,
            'scale' => $scale,
            $this->name => $comments
        ));
    }

    public function get($id)
    {
        return $this->methodNotAllowed();
    }

    public function create($data)
    {
        $anrId = (int) $this->params()->fromRoute('anrId');
        $scaleId = (int) $this->params()->fromRoute('scaleId');

        $data['anr'] = $anrId;
        $data['scale'] = $scaleId;

        $id = $this->getService()->create($data);

        return new JsonModel(
            array(
                'status' => 'ok',
                'id' => $id,
            )
        );
    }

    public function update($id, $data)
    {
        $anrId = (int) $this->params()->fromRoute('anrId');
        $scaleId = (int) $this->params()->fromRoute('scaleId');

        $data['anr'] = $anrId;
        $data['scale'] = $scaleId;

        $id = $this->getService()->update($id,$data);

        return new JsonModel(
            array(
                'status' => 'ok',
                'id' => $id,
            )
        );
    }

    public function patch($id, $data)
    {
        return $this->methodNotAllowed();
    }

    public function delete($id)
    {
        return $this->methodNotAllowed();
    }
}

