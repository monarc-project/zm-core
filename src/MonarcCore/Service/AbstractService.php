<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\AbstractEntity;
use MonarcCore\Model\Entity\Scale;
use MonarcCore\Model\Table\AbstractEntityTable;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\ObjectObjectTable;
use MonarcCore\Model\Table\ScaleTable;
use MonarcCore\Traits\RiskTrait;

use MonarcFO\Model\Table\UserAnrTable;

/**
 * Abstract Service
 *
 * Class AbstractService
 * @package MonarcCore\Service
 */
abstract class AbstractService extends AbstractServiceFactory
{
    use \MonarcCore\Model\GetAndSet;
	use RiskTrait; 

    /**
     * The service factory used in this service
     * @var AbstractServiceFactory|array|null
     */
    protected $serviceFactory;

    /**
     * The default table used in this service
     * @var \MonarcCore\Model\Table\AbstractEntityTable
     */
    protected $table;
    /**
     * The default entity used in this service
     * @var \MonarcCore\Model\Entity\AbstractEntity
     */
    protected $entity;
    protected $label;
    /**
     * The list of fields deleted during POST/PUT/PATCH
     * @var array
     */
    protected $forbiddenFields = [];
    /**
     * The list of fields corresponding to the entity's dependencies
     * @var array
     */
    protected $dependencies = [];

    /**
     * Constructor
     * @param AbstractServiceFactory|array|null $serviceFactory The factory to use for this service, or an array or
     * values to set on the Service variables.
     */
    public function __construct($serviceFactory = null)
    {
        if (is_array($serviceFactory)) {
            foreach ($serviceFactory as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            $this->serviceFactory = $serviceFactory;
        }
    }

    /**
     * Returns the ServiceFactory attached to this service. The factory generally has the same name as the class
     * appended with "Factory".
     * @see AbstractServiceFactory
     */
    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    /**
     * Parses the filter value coming from the frontend and returns an array of columns to filter. Basically, this
     * method will construct an array where the keys are the columns, and the value of each key is the filter parameter.
     * @param string $filter The value to look for
     * @param array $columns An array of columns in which the value is searched
     * @return array Key/pair array as per the description
     */
    protected function parseFrontendFilter($filter, $columns = [])
    {
        $output = [];
        if (!is_null($filter) && $columns) {
            foreach ($columns as $c) {
                $output[$c] = $filter;
            }
        }

        return $output;
    }

    /**
     * Parses the order from the frontend in order to build SQL-compliant ORDER BY. The order passed by the frontend
     * is the name of the column that we should sort the data with, eventually prepended with '-' when we need it in
     * descending order (ascending otherwise).
     * @param string $order The order requested by the frontend/API call
     * @return array|null Returns null if $order is null, otherwise an array ['columnName', 'ASC/DESC']
     */
    protected function parseFrontendOrder($order)
    {
        // Fields in the ORM are using a CamelCase notation, whereas JSON fields use underscores. Convert it here in
        // case there's a value not filtered.
        if (strpos($order, '_') !== false) {
            $o = explode('_', $order);
            $order = "";
            foreach ($o as $n => $oo) {
                if ($n <= 0) {
                    $order = $oo;
                } else {
                    $order .= ucfirst($oo);
                }
            }
        }

        if ($order == null) {
            return null;
        } else if (substr($order, 0, 1) == '-') {
            return [substr($order, 1), 'DESC'];
        } else {
            return [$order, 'ASC'];
        }
    }

    /**
     * Counts and returns the number of elements available for the specified query. Page and limit parameters
     * are ignored but kept for compatibility with getList calls. The order parameter is also ignored since it
     * will have no impact on the final count.
     * @param array|null $filter The array of columns => values which should be filtered (in a WHERE.. OR.. fashion)
     * @param array|null $filterAnd The array of columns => values which should be filtered (in a WHERE.. AND.. fashion)
     * @return int The number of elements retrieved from the query
     */
    public function getFilteredCount($filter = null, $filterAnd = null)
    {
        return $this->get('table')->countFiltered(
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );
    }

    /**
     * Returns the list of elements based on the provided filters passed in parameters. Results are paginated (using the
     * $page and $limit combo), except when $limit is <= 0, in which case all results will be returned.
     * @param int $page The page number, starting at 1.
     * @param int $limit The maximum number of elements retrieved, or 0 to retrieve everything
     * @param array|null $order The order in which elements should be retrieved (['column' => 'ASC/DESC'])
     * @param array|null $filter The array of columns => values which should be filtered (in a WHERE.. OR.. fashion)
     * @param array|null $filterAnd The array of columns => values which should be filtered (in a WHERE.. AND.. fashion)
     * @return array An array of elements based on the provided search query
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        return $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );
    }

    /**
     * Fetches and returns a specific entity based on its ID from the database, or null if no item was found.
     * @param int $id The element'd ID
     * @return array An associative array of the entity's data
     */
    public function getEntity($id)
    {
        return $this->get('table')->get($id);
    }

    /**
     * Creates a new entity of the type of this class, where the fields have the value of the $data array.
     * @param array $data The object's data
     * @param bool $last Whether or not this will be the last element of a batch. Setting this to false will suspend
     *                   flushing to the database to increase performance during batch insertions.
     * @return object The created entity object
     */
    public function create($data, $last = true)
    {
        $class = $this->get('entity');

        /** @var AnrTable $table */
        $table = $this->get('table');

        /** @var AbstractEntity $entity */
        $entity = new $class();

        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($table->getDb());
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $table->save($entity, $last);
    }

    /**
     * Updates an entity in the database. The entity will be fetched using the provided $id, and data will be reset
     * from $data. If you want to update only specific fields while keeping the existing data untouched, see patch()
     * @param int $id Entity's ID
     * @param array $data The new entity's data
     * @return object The new entity's object
     * @throws \MonarcCore\Exception\Exception If the entity does not exist, or doesn't belong to the ANR.
     * @see AbstractService::patch()
     */
    public function update($id, $data)
    {
        // Make sure we have something to update
        if (empty($data)) {
            throw new \MonarcCore\Exception\Exception('Data missing', 412);
        }

        // Fetch the existing entity
        /** @var AbstractEntity $entity */
        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
        }

        // If we try to override this object's ANR, make some sanity and security checks. Ensure the data's ANR matches
        // the existing ANR, and that we have the rights to edit it.
        if (!empty($data['anr'])) {
            if ($entity->get('anr')->get('id') != $data['anr']) {
                throw new \MonarcCore\Exception\Exception('Anr id error', 412);
            }

            $connectedUser = $this->get('table')->getConnectedUser();

            /** @var UserAnrTable $userAnrTable */
            $userAnrTable = $this->get('userAnrTable');
            $rights = $userAnrTable->getEntityByFields(['user' => $connectedUser['id'], 'anr' => $entity->anr->id]);
            $rwd = 0;
            foreach ($rights as $right) {
                if ($right->rwd == 1) {
                    $rwd = 1;
                }
            }

            if (!$rwd) {
                throw new \MonarcCore\Exception\Exception('You are not authorized to do this action', 412);
            }
        }

        // Filter fields we don't want to update, ever
        $this->filterPostFields($data, $entity);

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        // Pass our new data to the entity. This might throw an exception if some data is invalid.
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Patches an entity in the database. This works similarly to the update() method, except that fields that are
     * not inside the $data array won't be touched, and their value will remain unchanged.
     * @param int $id The entity's ID
     * @param array $data The entity's data
     * @return object The updated object
     * @throws \MonarcCore\Exception\Exception If the entity does not exist, or doesn't belong to the ANR.
     */
    public function patch($id, $data)
    {
        /** @var AbstractEntity $entity */
        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
        }
        // If we try to override this object's ANR, make some sanity and security checks. Ensure the data's ANR matches
        // the existing ANR, and that we have the rights to edit it.
        if (!empty($data['anr'])) {
            if ($entity->get('anr')->get('id') != $data['anr']) {
                throw new \MonarcCore\Exception\Exception('Anr id error', 412);
            }

            $connectedUser = $this->get('table')->getConnectedUser();

            /** @var UserAnrTable $userAnrTable */
            $userAnrTable = $this->get('userAnrTable');
            if ($userAnrTable) {
                $rights = $userAnrTable->getEntityByFields(['user' => $connectedUser['id'], 'anr' => $entity->anr->id]);
                $rwd = 0;
                foreach ($rights as $right) {
                    if ($right->rwd == 1) {
                        $rwd = 1;
                    }
                }

                if (!$rwd) {
                    throw new \MonarcCore\Exception\Exception('You are not authorized to do this action', 412);
                }
            }
        }

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());
        foreach ($this->dependencies as $dependency) {
            if ((!isset($data[$dependency])) && ($entity->$dependency)) {
                $data[$dependency] = $entity->$dependency->id;
            }
        }

        // Pass our new data to the entity. This might throw an exception if some data is invalid.
        $entity->exchangeArray($data, true);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Deletes an element from the database from its id
     * @param int $id The object's ID
     * @return bool True if the deletion is successful, false otherwise
     */
    public function delete($id)
    {
        /** @var AbstractEntityTable $table */
        $table = $this->get('table');
        return $table->delete($id);
    }

    /**
     * Deletes multiple elements from the database from their IDs
     * @param array $data The objects to delete, as array
     * @return bool True if the deletion is successful, false otherwise
     */
    public function deleteList($data)
    {
        return $this->get('table')->deleteList($data);
    }

    /**
     * Deletes an element from the database if the ID and the ANR ID matches together, to avoid deleting an
     * item that doesn't belong to the passed ANR ID.
     * @param int $id The object's ID
     * @param int|null $anrId The ANR ID to filter with
     * @return bool True if the deletion is successful, false otherwise
     * @throws \MonarcCore\Exception\Exception If the ANR is invalid, or the user has no rights on the ANR, or the object's ANR ID mismatches
     */
    public function deleteFromAnr($id, $anrId = null)
    {
        if (!is_null($anrId)) {
            $entity = $this->get('table')->getEntity($id);
            if ($entity->anr->id != $anrId) {
                throw new \MonarcCore\Exception\Exception('Anr id error', 412);
            }

            $connectedUser = $this->get('table')->getConnectedUser();

            /** @var UserAnrTable $userAnrTable */
            $userAnrTable = $this->get('userAnrTable');
            $rights = $userAnrTable->getEntityByFields(['user' => $connectedUser['id'], 'anr' => $anrId]);
            $rwd = 0;
            foreach ($rights as $right) {
                if ($right->rwd == 1) {
                    $rwd = 1;
                }
            }

            if (!$rwd) {
                throw new \MonarcCore\Exception\Exception('You are not authorized to do this action', 412);
            }
        }

        return $this->delete($id);
    }

    /**
     * Deletes multiple elements from the database if the ID and the ANR ID matches together, to avoid deleting an
     * item that doesn't belong to the passed ANR ID.
     * @param array $data The objects to delete
     * @param int|null $anrId The ANR ID to filter with
     * @return bool True if the deletion is successful, false otherwise
     * @throws \MonarcCore\Exception\Exception If the ANR is invalid, or the user has no rights on the ANR, or the object's ANR ID mismatches
     */
    public function deleteListFromAnr($data, $anrId = null)
    {
        if (!is_null($anrId)) {
            foreach ($data as $id) {
                $entity = $this->get('table')->getEntity($id);
                if ($entity->anr->id != $anrId) {
                    throw new \MonarcCore\Exception\Exception('Anr id error', 412);
                }
            }

            $connectedUser = $this->get('table')->getConnectedUser();

            /** @var UserAnrTable $userAnrTable */
            $userAnrTable = $this->get('userAnrTable');
            $rights = $userAnrTable->getEntityByFields(['user' => $connectedUser['id'], 'anr' => $anrId]);
            $rwd = 0;
            foreach ($rights as $right) {
                if ($right->rwd == 1) {
                    $rwd = 1;
                }
            }

            if (!$rwd) {
                throw new \MonarcCore\Exception\Exception('You are not authorized to do this action', 412);
            }
        }

        return $this->get('table')->deleteList($data);
    }

    /**
     * Compares the fields values of two entities and return their differences. Note that common fields such as
     * creator, created_at, updated_at, ... are filtered out of the diff.
     * @param AbstractEntity $newEntity The new entity to compare
     * @param AbstractEntity $oldEntity The old entity to compare
     * @return array An array of strings with the difference in the following format: "key: oldValue => newValue"
     */
    public function compareEntities($newEntity, $oldEntity)
    {
        $deps = [];
        foreach ($this->dependencies as $dep) {
            $propertyname = $dep;
            $matching = [];
            if (preg_match("/(\[([a-z0-9]*)\])\(([a-z0-9]*)\)$/", $dep, $matching)) {//si c'est 0 c'est pas bon non plus
                $propertyname = str_replace($matching[0], $matching[2], $dep);
                $dep = str_replace($matching[0], $matching[3], $dep);
            }
            $deps[$propertyname] = $propertyname;
        }

        // Filter out values that will necessarily be different
        $exceptions = ['creator', 'created_at', 'updater', 'updated_at', 'inputFilter', 'dbadapter', 'parameters', 'language'];

        $diff = [];
        foreach ($newEntity->getJsonArray() as $key => $value) {
            if (!in_array($key, $exceptions)) {
                if (isset($deps[$key])) {
                    $oldValue = $oldEntity->get($key);
                    if (!empty($oldValue) && is_object($oldValue)) {
                        $oldValue = $oldValue->get('id');
                    }
                    if (!empty($value) && is_object($value)) {
                        $value = $value->get('id');
                    }
                    if ($oldValue != $value) {
                        $diff[] = $key . ': ' . $oldValue . ' => ' . $value;
                    }
                } elseif ($oldEntity->get($key) != $value) {
                    $diff[] = $key . ': ' . $oldEntity->get($key) . ' => ' . $value;
                }
            }
        }

        return $diff;
    }

    /**
     * Format and cleans up entities dependencies (relationships)
     * @param array $entity The entity data array
     * @param array $dependencies The entity's dependencies
     */
    protected function formatDependencies(&$entity, $dependencies)
    {
        foreach ($dependencies as $dependency) {
            if (!empty($entity[$dependency])) {
                $entity[$dependency] = $entity[$dependency]->getJsonArray();

                // Remove the fields we don't want to appear in the JSON output
                unset($entity[$dependency]['__initializer__']);
                unset($entity[$dependency]['__cloner__']);
                unset($entity[$dependency]['__isInitialized__']);
            }
        }
    }

    /**
     * Defines and loads the entity's dependencies (relationship fields)
     * @param AbstractEntity $entity The entity to load
     * @param array $dependencies The array of dependencies fields (relationships)
     * @throws \MonarcCore\Exception\Exception
     */
    public function setDependencies(&$entity, $dependencies)
    {
        $db = $entity->getDbAdapter();
        if (empty($db)) {
            $db = $this->get('table')->getDb();
        }
        $metadata = $db->getClassMetadata(get_class($entity));

        foreach ($dependencies as $dependency) {
            $deptable = $propertyname = $dependency;
            $matching = [];
            if (preg_match("/(\[([a-z0-9]*)\])\(([a-z0-9]*)\)$/", $deptable, $matching)) {//si c'est 0 c'est pas bon non plus
                $propertyname = str_replace($matching[0], $matching[2], $deptable);
                $deptable = str_replace($matching[0], $matching[3], $deptable);
            }

            $value = $entity->get($propertyname);
            if (!is_null($value) && !empty($value) && !is_object($value)) {
                if ($metadata->hasAssociation($propertyname)) {
                    $class = $metadata->getAssociationTargetClass($propertyname);
                    if (!is_array($value) || isset($value['id'])) {
                        $dep = $db->getReference($class, isset($value['id']) ? $value['id'] : $value);

                        if (isset($dep->anr) && isset($entity->anr) && $dep->anr instanceof \MonarcCore\Model\Entity\AnrSuperClass) {
                            $depAnrId = $dep->anr->id;
                            $entityAnrId = is_integer($entity->anr) ? $entity->anr : $entity->anr->id;
                            if ($depAnrId != $entityAnrId) {
                                throw new \MonarcCore\Exception\Exception('You are not authorized to use this dependency', 412);
                            }
                        }

                        if (!$dep->id) {
                            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
                        }
                        $entity->set($propertyname, $dep);
                    } elseif (!array_key_exists('id', $value)) {
                        $a_dep = [];
                        foreach ($value as $v) {
                            if (!is_null($v) && !empty($v) && !is_object($v)) {
                                $dep = $db->getReference($class, $v);
                                if (!$dep->id) {
                                    throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
                                }
                                $a_dep[] = $dep;
                            }
                        }
                        $entity->set($propertyname, $a_dep);
                    }
                } else { // DEPRECATED
                    $tableName = $deptable . 'Table';
                    $method = 'set' . ucfirst($propertyname);
                    if (!is_array($value) || isset($value['id'])) {
                        $dep = $this->get($tableName)->getReference(isset($value['id']) ? $value['id'] : $value);
                        if (!$dep->id) {
                            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
                        }
                        $entity->$method($dep);
                    } elseif (!array_key_exists('id', $value)) {
                        $a_dep = [];
                        foreach ($value as $v) {
                            if (!is_null($v) && !empty($v) && !is_object($v)) {
                                $dep = $this->get($tableName)->getReference($v);
                                if (!$dep->id) {
                                    throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
                                }
                                $a_dep[] = $dep;
                            }
                        }
                        $entity->$method($a_dep);
                    }
                }
            }
        }
    }

    /**
     * Updates the position field of the element relative to the other elements and the provided direction.
     * @param string $field The position field name
     * @param AbstractEntity $entity The entity to move
     * @param string $direction The direction in which the entity moves (up / down)
     */
    protected function manageRelativePositionUpdate($field, $entity, $direction)
    {
        /** @var ObjectObjectTable $table */
        $table = $this->get('table');

        if ($direction == 'up') {
            $entityAbove = $table->getEntityByFields([$field => $entity->$field, 'position' => $entity->position - 1]);

            if (count($entityAbove) == 1) {
                $entityAbove = $entityAbove[0];
                $entityAbove->position = $entityAbove->position + 1;
                $table->save($entityAbove);
            }

            $entity->position = $entity->position - 1;
            $table->save($entity);
        } else if ($direction == 'down') {
            $entityBelow = $table->getEntityByFields([$field => $entity->$field, 'position' => $entity->position + 1]);

            if (count($entityBelow) == 1) {
                $entityBelow = $entityBelow[0];
                $entityBelow->position = $entityBelow->position - 1;
                $table->save($entityBelow);

                $entity->position = $entity->position + 1;
                $table->save($entity);
            }
        }
    }

    /**
     * Returns the root entity of the provided entity by recursively calling getParent() on it.
     * @param AbstractEntity $entity
     * @return AbstractEntity The resulting parent entity, or itself if the entity has no parent
     */
    public function getRoot($entity)
    {
        if (!is_null($entity->getParent())) {
            return $this->getRoot($entity->getParent());
        } else {
            return $entity;
        }
    }

    
	/**
     * Filter fields for a patch request by removing the forbidden fields list
     * @param array $data The fields data
     */
    protected function filterPatchFields(&$data)
    {
        if (is_array($data)) {
            foreach (array_keys($data) as $key) {
                if (in_array($key, $this->forbiddenFields)) {
                    unset($data[$key]);
                }
            }
        }
    }

    /**
     * Filter fields for a post/put request by removing the forbidden fields list
     * @param array $data The fields data
     * @param AbstractEntity $entity The entity
     * @param bool|array $forbiddenFields The fields to remove, or false
     */
    protected function filterPostFields(&$data, $entity, $forbiddenFields = false)
    {
        $forbiddenFields = (!$forbiddenFields) ? $this->forbiddenFields : $forbiddenFields;
        if (is_array($data)) {
            foreach (array_keys($data) as $key) {
                if (in_array($key, $forbiddenFields)) {
                    if (is_object($entity->$key)) {
                        $data[$key] = ($entity->$key) ? $entity->$key->id : null;
                    } else {
                        $data[$key] = $entity->$key;
                    }
                }
            }
        }
    }

    /**
     * Verifies the consequences rates for the provided data and instance risk
     * @param int $anrId The ANR's ID
     * @param array $data The consequence data array
     * @param array $instanceRisk The instance risk data array
     * @throws \MonarcCore\Exception\Exception If there are incorrect values, return a comma-separated string of all the errors
     */
    protected function verifyRates($anrId, $data, $instanceRisk = null)
    {
        // TODO: Ensure that this method is never called inside a loop
        // TODO: Optimizations: Fetch all threats directly instead of performing one query for each scale type
        $errors = [];
        $scaleThreat = $scaleVul = $scaleImpact = null;

        // Ensure threat rate is within valid bounds
        if (isset($data['threatRate'])) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scaleThreat = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => Scale::TYPE_THREAT]);

            $scaleThreat = $scaleThreat[0];

            $prob = (int)$data['threatRate'];

            if (($prob != -1) && (($prob < $scaleThreat->get('min')) || ($prob > $scaleThreat->get('max')))) {
                $errors[] = 'Value for probability is not valid';
            }
        }

        // Ensure vulnerability rate is within valid bounds
        if (isset($data['vulnerabilityRate'])) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scaleVul = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => Scale::TYPE_VULNERABILITY]);

            $scaleVul = $scaleVul[0];

            $prob = (int)$data['vulnerabilityRate'];

            if (($prob != -1) && (($prob < $scaleVul->get('min')) || ($prob > $scaleVul->get('max')))) {
                $errors[] = 'Value for qualification is not valid';
            }
        }

        // If an instance risk is passed, make sure the reduction amount is not higher than the vulnerability rate
        if ($instanceRisk && isset($data['reductionAmount'])) {
            $reductionAmount = (int)$data['reductionAmount'];

            $vulnerabilityRate = (isset($data['vulnerabilityRate'])) ? (int)$data['vulnerabilityRate'] : $instanceRisk['vulnerabilityRate'];
            if (($vulnerabilityRate != -1) && (($reductionAmount < 0) || ($reductionAmount > $vulnerabilityRate))) {
                $errors[] = 'Value for reduction amount is not valid (min '.$data['vulnerabilityRate'].')';
            }
        }

        // If we have C/I/D or R/O/L/F/P values, ensure they are within the min/max bounds of the corresponding scale impact
        if (isset($data['c']) || isset($data['i']) || isset($data['d'])
            || isset($data['brutR']) || isset($data['brutO']) || isset($data['brutL']) || isset($data['brutF']) || isset($data['brutP'])
            || isset($data['netR']) || isset($data['netO']) || isset($data['netL']) || isset($data['netF']) || isset($data['netP'])
            || isset($data['targetedR']) || isset($data['targetedO']) || isset($data['targetedL']) || isset($data['targetedF']) || isset($data['targetedP'])
        ) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scaleImpact = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => Scale::TYPE_IMPACT]);

            $scaleImpact = $scaleImpact[0];

            $fields = ['c', 'i', 'd', 'brutR', 'brutO', 'brutL', 'brutF', 'brutP', 'netR', 'netO', 'netL', 'netF', 'netP', 'targetedR', 'targetedO', 'targetedL', 'targetedF', 'targetedP'];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $value = (int)$data[$field];
                    if ($value != -1 && ($value < $scaleImpact->get('min') || $value > $scaleImpact->get('max'))) {
                        $errors[] = 'Value for ' . $field . ' is not valid';
                    }
                }
            }
        }

        // If we have raw/net/target probability, ensure the value is within valid bounds
        if (isset($data['brutProb']) || isset($data['netProb']) || isset($data['targetedProb'])) {
            if (is_null($scaleThreat)) {
                /** @var ScaleTable $scaleTable */
                $scaleTable = $this->get('scaleTable');
                $scaleThreat = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => Scale::TYPE_THREAT]);
                $scaleThreat = $scaleThreat[0];
            }

            $fields = ['brutProb', 'netProb', 'targetedProb'];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $value = (int)$data[$field];

                    if ($value != -1 && ($value < $scaleThreat->get('min') || $value > $scaleThreat->get('max'))) {
                        $errors[] = 'Value for ' . $field . ' is not valid';
                    }
                }
            }
        }

        if (count($errors)) {
            throw new \MonarcCore\Exception\Exception(implode(', ', $errors), 412);
        }
    }

    /**
     * Encrypt the provided data using the specified key
     * This is used for import and exporting of files mainly
     * @param string $data The data to encrypt
     * @param string $key The key to use to encrypt the data
     * @return string The encrypted data
     */
    protected function encrypt($data, $key)
    {
        // TODO: Replace mcrypt_encrypt with openssl_encrypt
        # return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
        return openssl_encrypt($data,'AES-256-ECB',$key);
    }

    /**
     * Decrypt the provided data using the specified key
     * This is used for import and exporting of files mainly
     * @param string $data The data to decrypt
     * @param string $key The key to use to decrypt the data
     * @return string The decrypted data
     */
    protected function decrypt($data, $key)
    {
        // TODO: Replace mcrypt_decrypt with openssl_decrypt
        # return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
        return openssl_decrypt($data,'AES-256-ECB',$key);
    }

    /**
     * Computes and returns the Git version
     * @param string $type The format of version to retrieve (major / full)
     * @return string The version string
     */
    protected function getVersion($type = 'major')
    {
        switch (strtolower($type)) {
            case 'full':
                return isset($this->monarcConf['version']) ? $this->monarcConf['version'] : null;
                break;
            default:
            case 'major':
                if (!empty($this->monarcConf['version'])) {
                    return implode('.', array_slice(explode('.', $this->monarcConf['version']), 0, 2));
                } else {
                    return null;
                }
                break;
        }
    }
}
