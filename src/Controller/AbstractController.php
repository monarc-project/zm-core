<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Controller;

use Monarc\Core\Controller\Handler\AbstractRestfulControllerRequestHandler;
use Monarc\Core\Controller\Handler\ControllerRequestResponseHandlerTrait;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Service\AbstractServiceFactory;

/**
 * Abstract Controller used on every REST API controllers
 * @package Monarc\Core\Controller
 */
abstract class AbstractController extends AbstractRestfulControllerRequestHandler
{
    use ControllerRequestResponseHandlerTrait;

    /**
     * The service used by the controller.
     * @var \Monarc\Core\Service\AbstractService
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
     * @param AbstractServiceFactory $service The service factory to use
     */
    public function __construct(AbstractServiceFactory $service)
    {
        $this->service = $service;
    }

    /**
     * @return AbstractServiceFactory The attached service factory
     */
    protected function getService()
    {
        return $this->service;
    }

    /**
     * Default method to prevent access to a non-authorized verb
     * @throws Exception HTTP 405 "Method Not Allowed"
     */
    protected function methodNotAllowed()
    {
        $this->response->setStatusCode(405);

        throw new Exception('Method Not Allowed');
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

        return $this->getPreparedJsonResponse([
            'count' => $service->getFilteredCount($filter),
            $this->name => $entities
        ]);
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

        return $this->getPreparedJsonResponse($entity);
    }

    /**
     * Default action called for a POST (without an ID), to create a new entity
     * @param array $data The posted JSON data
     * @return JsonModel JSON data of the status and the created entity ID
     */
    public function create($data)
    {
      if (array_keys($data) !== range(0, count($data) - 1)) {
          # if $data is an associative array
          $data = array($data);
      }

      $created_objects = array();
      foreach ($data as $key => $new_data) {
          $id = $this->getService()->create($new_data);
          array_push($created_objects, $id);
      }
      return $this->getSuccessfulJsonResponse([
          'id' => count($created_objects)==1 ? $created_objects[0]: $created_objects,
      ]);
    }

    /**
     * Default action called for a DELETE with an ID, to delete an entity
     * @param int $id The entity ID to delete
     * @return JsonModel JSON status confirmation
     */
    public function delete($id)
    {
        if($this->getService()->delete($id)){
            return $this->getSuccessfulJsonResponse();
        }else{
            return $this->getPreparedJsonResponse(['status' => 'ko']); // Todo: peux être retourner un message d'erreur
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
            return $this->getSuccessfulJsonResponse();
        }else{
            return $this->getPreparedJsonResponse(['status' => 'ko']); // Todo: peux être retourner un message d'erreur
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

        return $this->getSuccessfulJsonResponse();
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

        return $this->getSuccessfulJsonResponse();
    }

    /**
     * TODO: Replace the formatter with a proper data normalisation layer.
     *
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
                    if (is_a($entity[$dependency], 'Monarc\Core\Model\Entity\AbstractEntity')) {
                        if(is_a($entity[$dependency], $EntityDependency)) { // fetch more info
                            $entity[$dependency] = $entity[$dependency]->getJsonArray();
                            if(!empty($subField)) {
                                foreach ($subField as $key => $value) {
                                    $entity[$dependency][$value] = $entity[$dependency][$value] ? $entity[$dependency][$value]->getJsonArray() : [];
                                    unset($entity[$dependency][$value]['__initializer__']);
                                    unset($entity[$dependency][$value]['__cloner__']);
                                    unset($entity[$dependency][$value]['__isInitialized__']);
                                }
                            }
                        } else {
                            $entity[$dependency] = $entity[$dependency]->getJsonArray();
                        }
                        unset($entity[$dependency]['__initializer__']);
                        unset($entity[$dependency]['__cloner__']);
                        unset($entity[$dependency]['__isInitialized__']);
                    } elseif (get_class($entity[$dependency]) == 'Doctrine\ORM\PersistentCollection') {
                        $entity[$dependency]->initialize();
                        if ($entity[$dependency]->count()) {
                            $dependenciesObjects = $entity[$dependency]->getSnapshot();
                            $entity[$dependency] = [];
                            foreach($dependenciesObjects as $d) {
                              if(is_a($d, $EntityDependency)) { //fetch more info
                                  $temp = $d->toArray();
                                  if(!empty($subField)) {
                                    foreach ($subField as $key => $value) {
                                      $temp[$value] = $d->$value->getJsonArray();
                                      unset($temp[$value]['__initializer__']);
                                      unset($temp[$value]['__cloner__']);
                                      unset($temp[$value]['__isInitialized__']);
                                    }
                                    $entity[$dependency][] = $temp;
                                  }
                              }
                              else if (is_a($d, 'Monarc\Core\Model\Entity\AbstractEntity')) {
                                  $entity[$dependency][] = $d->getJsonArray();
                              } else {
                                  $entity[$dependency][] = $d;
                              }
                            }
                        }
                    }
                } else if (is_array($entity[$dependency])) {
                    foreach($entity[$dependency] as $key => $value) {
                        if (is_a($entity[$dependency][$key], 'Monarc\Core\Model\Entity\AbstractEntity')) {
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
}
