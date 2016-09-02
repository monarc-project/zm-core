<?php
namespace MonarcCore\Controller;

use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

abstract class AbstractController extends AbstractRestfulController
{
    protected $service;

    protected $dependencies = [];

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

        $service = $this->getService();

        $entities = $service->getList($page, $limit, $order, $filter);
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
        $entity = $this->getService()->getEntity($id);

        if (count($this->dependencies)) {
            $this->formatDependencies($entity, $this->dependencies);
        }

        return new JsonModel($entity);
    }

    /**
     * Create
     *
     * @param mixed $data
     * @return JsonModel
     */
    public function create($data)
    {
        $id = $this->getService()->create($data);

        return new JsonModel(
            array(
                'status' => 'ok',
                'id' => $id,
            )
        );
    }

    /**
     * Delete
     *
     * @param mixed $id
     * @return JsonModel
     */
    public function delete($id)
    {
        $this->getService()->delete($id);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * Delete list
     *
     * @param mixed $data
     * @return JsonModel
     */
    public function deleteList($data)
    {
        $this->getService()->deleteList($data);

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
        $this->getService()->update($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * Format Dependencies
     *
     * @param $entity
     * @param $dependencies
     */
    public function formatDependencies(&$entity, $dependencies) {

        foreach($dependencies as $dependency) {
            if (!empty($entity[$dependency])) {
                if (is_object($entity[$dependency])) {
                    $entity[$dependency] = $entity[$dependency]->getJsonArray();
                    unset($entity[$dependency]['__initializer__']);
                    unset($entity[$dependency]['__cloner__']);
                    unset($entity[$dependency]['__isInitialized__']);
                } else if (is_array($entity[$dependency])) {
                    foreach($entity[$dependency] as $key => $value) {
                        if (is_a($entity[$dependency][$key], '\MonarcCore\Model\Model')) {
                            $entity[$dependency][$key] = $entity[$dependency][$key]->getJsonArray();
                            unset($entity[$dependency][$key]['__initializer__']);
                            unset($entity[$dependency][$key]['__cloner__']);
                            unset($entity[$dependency][$key]['__isInitialized__']);
                        } else {
                            $entity[$dependency][$key] = null;
                        }
                    }
                }
            }
        }
    }

    /**
     * recursive array
     *
     * @param $array
     * @param $parent
     * @param $level
     * @param $fields
     * @return array
     * @throws \Exception
     */
    public function recursiveArray($array, $parent, $level, $fields)
    {
        $recursiveArray = [];
        foreach ($array AS $node) {

            $parentId = null;
            if (array_key_exists('parent', $node)) {
                if (!is_null($node['parent'])) {
                    $parentId = $node['parent']->id;
                }
            } else if (array_key_exists('parentId', $node)) {
                if (!is_null($node['parentId'])) {
                    $parentId = $node['parentId'];
                }
            } else {
                throw new \Exception('Parent missing', 412);
            }

            $nodeArray = [];

            if ($parent == $parentId) {
                foreach($fields as $field) {
                    if (array_key_exists($field, $node)) {
                        $nodeArray[$field] = $node[$field];
                    }
                }
                $child = $this->recursiveArray($array, $node['id'], ($level + 1), $fields);
                if ($child) {
                    $nodeArray['child'] = $child;
                }

            }
            if (!empty($nodeArray)) {
                $recursiveArray[] = $nodeArray;
            }
        }

        return $recursiveArray;
    }
}