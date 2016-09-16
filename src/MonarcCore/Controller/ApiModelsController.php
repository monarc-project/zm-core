<?php

namespace MonarcCore\Controller;
use MonarcCore\Service\ModelService;
use Zend\View\Model\JsonModel;

/**
 * Api Models Controller
 *
 * Class ApiModelsController
 * @package MonarcCore\Controller
 */
class ApiModelsController extends AbstractController
{
    protected $dependencies = ['anr'];
    protected $name = 'models';

    /**
     * Get list
     *
     * @return JsonModel
     */
    public function getList()
    {
        $page = $this->params()->fromQuery('page');
        $limit = $this->params()->fromQuery('limit');
        $order = $this->params()->fromQuery('order');
        $filter = $this->params()->fromQuery('filter');
        $isGeneric = $this->params()->fromQuery('isGeneric');

        $service = $this->getService();

        if (is_null($isGeneric)) {
            $entities = $service->getList($page, $limit, $order, $filter);
        }
        else {
            $entities = $service->getList($page, $limit, $order, $filter, array('isGeneric' => $isGeneric));
        }

        if (count($this->dependencies)) {
            foreach ($entities as $key => $entity) {
                $this->formatDependencies($entities[$key], $this->dependencies);
            }
        }

        return new JsonModel(array(
            'count' => $service->getFilteredCount($page, $limit, $order, $filter),
            $this->name => $entities
        ));
    }


    /**
     * Get
     *
     * @param mixed $id
     * @return JsonModel
     */
    public function get($id)
    {
        /** @var ModelService $modelService */
        $modelService = $this->getService();
        $entity = $modelService->getModelWithAnr($id);

        return new JsonModel($entity);
    }
}

