<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Controller;

use MonarcCore\Model\Entity\AbstractEntity;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\JsonModel;

/**
 * Abstract Controller used on every REST API controllers
 * @package MonarcCore\Controller
 */
abstract class AbstractController extends AbstractRestfulController
{
    /**
     * The service used by the controller.
     * @var \MonarcCore\Service\AbstractService
     */
    protected $service;

    /**
     * The lists of the dependencies of the entity
     * @var array
     */
    protected $dependencies = [];

    /**
     * The name of the key corresponding to the list of elements returned in the function getList.
     * @var string
     */
    protected $name = 'datas';

    /**
     * AbstractController constructor.
     * @param \MonarcCore\Service\AbstractServiceFactory $service The service factory to use
     */
    public function __construct(\MonarcCore\Service\AbstractServiceFactory $service)
    {
        $this->service = $service;
    }

    /**
     * @return \MonarcCore\Service\AbstractServiceFactory The attached service factory
     */
    protected function getService()
    {
        return $this->service;
    }

    /**
     * Default method to prevent access to a non-authorized verb
     * @throws \MonarcCore\Exception\Exception HTTP 405 "Method Not Allowed"
     */
    protected function methodNotAllowed()
    {
        $this->response->setStatusCode(405);
        throw new \MonarcCore\Exception\Exception('Method Not Allowed');
    }

    /**
     * Default action called for a GET without an id, generally to get a list of entities
     * @return JsonModel JSON data of the entities list
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
            'count' => $service->getFilteredCount($filter),
            $this->name => $entities
        ));
    }

    /**
     * Default action called for a GET with an id, to get the specifics of an entity
     * @param int $id The entity's ID
     * @return JsonModel JSON data of the entity fields
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
     * Default action called for a POST (without an ID), to create a new entity
     * @param array $data The posted JSON data
     * @return JsonModel JSON data of the status and the created entity ID
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
     * Default action called for a DELETE with an ID, to delete an entity
     * @param int $id The entity ID to delete
     * @return JsonModel JSON status confirmation
     */
    public function delete($id)
    {
        if($this->getService()->delete($id)){
            return new JsonModel(array('status' => 'ok'));
        }else{
            return new JsonModel(array('status' => 'ko')); // Todo: peux être retourner un message d'erreur
        }
    }

    /**
     * Default action called for a DELETE without an ID, to delete multiple entities at once
     * @param array $data An array of IDs to delete
     * @return JsonModel JSON status confirmation
     */
    public function deleteList($data)
    {
        if($this->getService()->deleteList($data)){
            return new JsonModel(array('status' => 'ok'));
        }else{
            return new JsonModel(array('status' => 'ko')); // Todo: peux être retourner un message d'erreur
        }
    }

    /**
     * Default action called for a PUT with an ID, to replace an existing entity
     * @param int $id The entity ID to replace
     * @param array $data The new data for the entity
     * @return JsonModel JSON status confirmation
     */
    public function update($id, $data)
    {
        $this->getService()->update($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * Default action called for a PATCH with an ID, to update an existing entity while keeping unreferenced fields
     * unchanged (as opposed to update()/PUT which will erase empty fields not passed in parameter)
     * @param int $id The entity ID to patch
     * @param array $data The new fields to replace on the entity
     * @return JsonModel JSON status confirmation
     */
    public function patch($id, $data)
    {
        $this->getService()->patch($id, $data);

        return new JsonModel(array('status' => 'ok'));
    }

    /**
     * Automatically loads the dependencies of the entity based on the class' "dependencies" field
     * @param AbstractEntity $entity The entity for which the deps should be resolved
     * @param array $dependencies The dependencies fields
     * @param string $EntityDependency name of class of the dependency (object) entity to get focus on
     * @param array $subField the list of subfield to fetch
     */
    public function formatDependencies(&$entity, $dependencies, $EntityDependency = "", $subField = []) {
        foreach($dependencies as $dependency) {
            if (!empty($entity[$dependency])) {
                if (is_object($entity[$dependency])) {
                    if (is_a($entity[$dependency], '\MonarcCore\Model\Entity\AbstractEntity')) {
                        $entity[$dependency] = $entity[$dependency]->getJsonArray();
                        unset($entity[$dependency]['__initializer__']);
                        unset($entity[$dependency]['__cloner__']);
                        unset($entity[$dependency]['__isInitialized__']);
                    }elseif(get_class($entity[$dependency]) == 'Doctrine\ORM\PersistentCollection'){
                        $entity[$dependency]->initialize();
                        if($entity[$dependency]->count()){
                            $$dependency = $entity[$dependency]->getSnapshot();
                            $entity[$dependency] = [];
                            foreach($$dependency as $d){
                              if(is_a($d, $EntityDependency)){ //fetch more info
                                  $temp = $d->toArray();
                                  if(!empty($subField)){
                                    foreach ($subField as $key => $value){
                                      $temp[$value] = $d->$value->getJsonArray();
                                      unset($temp[$value]['__initializer__']);
                                      unset($temp[$value]['__cloner__']);
                                      unset($temp[$value]['__isInitialized__']);
                                    }
                                    $entity[$dependency][] = $temp;
                                  }
                              }
                              else if(is_a($d, '\MonarcCore\Model\Entity\AbstractEntity')){
                                    $entity[$dependency][] = $d->getJsonArray();
                              }else{
                                  $entity[$dependency][] = $d;
                              }
                            }
                        }
                    }
                } else if (is_array($entity[$dependency])) {
                    foreach($entity[$dependency] as $key => $value) {
                        if (is_a($entity[$dependency][$key], '\MonarcCore\Model\Entity\AbstractEntity')) {
                            $entity[$dependency][$key] = $entity[$dependency][$key]->getJsonArray();
                            unset($entity[$dependency][$key]['__initializer__']);
                            unset($entity[$dependency][$key]['__cloner__']);
                            unset($entity[$dependency][$key]['__isInitialized__']);
                        }
                    }
                }
            }
        }
    }

    /**
     * Computes a recursive array based on the parents
     * @param array $array The source array
     * @param int $parent The parent ID
     * @param int $level The current recursion level
     * @param array $fields The fields to keep
     * @return array A recursive array of all the children
     * @throws \MonarcCore\Exception\Exception If there is no parent
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
                throw new \MonarcCore\Exception\Exception('Parent missing', 412);
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
