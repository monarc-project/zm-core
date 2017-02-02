<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Scale;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\ObjectObjectTable;
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

    protected $serviceFactory;
    protected $table;
    protected $entity;
    protected $label;
    protected $forbiddenFields = [];
    protected $dependencies = [];

    /**
     * @return null
     */
    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    /**
     * Construct
     *
     * AbstractService constructor.
     * @param null $serviceFactory
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
     * Parse Frontend Filter
     *
     * @param $filter
     * @param array $columns
     * @return array
     */
    protected function parseFrontendFilter($filter, $columns = [])
    {
        $output = [];
        if (!is_null($filter)) {
            if ($columns) {
                foreach ($columns as $c) {
                    $output[$c] = $filter;
                }
            }
        }

        return $output;
    }

    /**
     * Parse Frontend Order
     *
     * @param $order
     * @return array|null
     */
    protected function parseFrontendOrder($order)
    {
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
     * Get Filtered Count
     *
     * @param null $filter
     * @return int
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        return $this->get('table')->countFiltered(
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
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
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id)
    {
        return $this->get('table')->get($id);
    }

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {
        $class = $this->get('entity');
        $entity = new $class();
        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        /** @var AnrTable $table */
        $table = $this->get('table');

        return $table->save($entity, $last);
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id, $data)
    {
        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \Exception('Entity does not exist', 412);
        }

        if (!empty($data['anr'])) {
            if ($entity->get('anr')->get('id') != $data['anr']) {
                throw new \Exception('Anr id error', 412);
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
                throw new \Exception('You are not authorized to do this action', 412);
            }
        }

        $this->filterPostFields($data, $entity);

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function patch($id, $data)
    {
        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \Exception('Entity does not exist', 412);
        }
        if (!empty($data['anr'])) {
            if ($entity->get('anr')->get('id') != $data['anr']) {
                throw new \Exception('Anr id error', 412);
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
                    throw new \Exception('You are not authorized to do this action', 412);
                }
            }
        }

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        foreach ($this->dependencies as $dependency) {
            if (!isset($data[$dependency])) {
                $data[$dependency] = $entity->$dependency->id;
            }
        }

        $entity->exchangeArray($data, true);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id)
    {
        return $this->get('table')->delete($id);
    }

    /**
     * Delete From Anr
     *
     * @param $id
     * @param null $anrId
     * @return mixed
     * @throws \Exception
     */
    public function deleteFromAnr($id, $anrId = null)
    {
        if (!is_null($anrId)) {
            $entity = $this->get('table')->getEntity($id);
            if ($entity->anr->id != $anrId) {
                throw new \Exception('Anr id error', 412);
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
                throw new \Exception('You are not authorized to do this action', 412);
            }
        }

        return $this->delete($id);
    }

    /**
     * Delete List From Anr
     *
     * @param $data
     * @param null $anrId
     * @return mixed
     * @throws \Exception
     */
    public function deleteListFromAnr($data, $anrId = null)
    {
        if (!is_null($anrId)) {
            foreach ($data as $id) {
                $entity = $this->get('table')->getEntity($id);
                if ($entity->anr->id != $anrId) {
                    throw new \Exception('Anr id error', 412);
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
                throw new \Exception('You are not authorized to do this action', 412);
            }
        }

        return $this->get('table')->deleteList($data);
    }

    /**
     * Detele list
     *
     * @param $data
     */
    public function deleteList($data)
    {
        return $this->get('table')->deleteList($data);
    }

    /**
     * Compare Entities
     *
     * @param $newEntity
     * @param $oldEntity
     * @return array
     */
    public function compareEntities($newEntity, $oldEntity)
    {
        $deps = [];
        foreach ($this->dependencies as $dep) {
            $propertyname = $dep;
            $matching = [];
            if (preg_match("/(\[([a-z0-9]*)\])\(([a-z0-9]*)\)$/", $dep, $matching) != false) {//si c'est 0 c'est pas bon non plus
                $propertyname = str_replace($matching[0], $matching[2], $dep);
                $dep = str_replace($matching[0], $matching[3], $dep);
            }
            $deps[$propertyname] = $propertyname;
        }

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
     * Historize update
     *
     * @param $type
     * @param $entity
     * @param $oldEntity
     */
    public function historizeUpdate($type, $entity, $oldEntity)
    {
        $diff = $this->compareEntities($entity, $oldEntity);

        if (count($diff)) {
            $this->historize($entity, $type, 'update', implode(' / ', $diff));
        }
    }

    /**
     * Historize create
     *
     * @param $type
     * @param $entity
     */
    public function historizeCreate($type, $entity, $details)
    {
        $this->historize($entity, $type, 'create', implode(' / ', $details));
    }

    /**
     * Historize delete
     *
     * @param $type
     * @param $entity
     */
    public function historizeDelete($type, $entity, $details)
    {
        $this->historize($entity, $type, 'delete', implode(' / ', $details));
    }

    /**
     * Historize
     *
     * @param $entity
     * @param $type
     * @param $verb
     * @param $details
     */
    public function historize($entity, $type, $verb, $details)
    {
        $entityId = null;
        if (is_object($entity) && (property_exists($entity, 'id'))) {
            $entityId = $entity->id;
        } else if (is_array($entity) && (isset($entity['id']))) {
            $entityId = $entity['id'];
        }
        $data = [
            'type' => $type,
            'sourceId' => $entityId,
            'action' => $verb,
            'label1' => (is_object($entity) && property_exists($entity, 'label1')) ? $entity->label1 : $this->label[0],
            'label2' => (is_object($entity) && property_exists($entity, 'label2')) ? $entity->label2 : $this->label[1],
            'label3' => (is_object($entity) && property_exists($entity, 'label3')) ? $entity->label3 : $this->label[2],
            'label4' => (is_object($entity) && property_exists($entity, 'label4')) ? $entity->label4 : $this->label[3],
            'details' => $details,
        ];

        $historicalService = $this->get('historicalService');
        $historicalService->create($data, $last = true);
    }

    /**
     * Format dependencies
     *
     * @param $entity
     * @param $dependencies
     */
    protected function formatDependencies(&$entity, $dependencies)
    {
        foreach ($dependencies as $dependency) {
            if (!empty($entity[$dependency])) {
                $entity[$dependency] = $entity[$dependency]->getJsonArray();
                unset($entity[$dependency]['__initializer__']);
                unset($entity[$dependency]['__cloner__']);
                unset($entity[$dependency]['__isInitialized__']);
            }
        }
    }

    /**
     * Set Dependencies
     *
     * @param $entity
     * @param $dependencies
     * @throws \Exception
     */
    public function setDependencies(&$entity, $dependencies)
    {
        $db = $entity->getDbAdapter();
        if (empty($db)) {
            $db = $this->get('table')->getDb();
        }
        $metadata = $db->getClassMetadata(get_class($entity));

        foreach ($dependencies as $dependency) {
            // = preg_replace("/[0-9]/", "", $dependency);
            $deptable = $propertyname = $dependency;
            $matching = [];
            if (preg_match("/(\[([a-z0-9]*)\])\(([a-z0-9]*)\)$/", $deptable, $matching) != false) {//si c'est 0 c'est pas bon non plus
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
                                throw new \Exception('You are not authorized to use this dependency', 412);
                            }
                        }

                        if (!$dep->id) {
                            throw new \Exception('Entity does not exist', 412);
                        }
                        $entity->set($propertyname, $dep);
                    } elseif (!array_key_exists('id', $value)) {
                        $a_dep = [];
                        foreach ($value as $v) {
                            if (!is_null($v) && !empty($v) && !is_object($v)) {
                                $dep = $db->getReference($class, $v);
                                if (!$dep->id) {
                                    throw new \Exception('Entity does not exist', 412);
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
                            throw new \Exception('Entity does not exist', 412);
                        }
                        $entity->$method($dep);
                    } elseif (!array_key_exists('id', $value)) {
                        $a_dep = [];
                        foreach ($value as $v) {
                            if (!is_null($v) && !empty($v) && !is_object($v)) {
                                $dep = $this->get($tableName)->getReference($v);
                                if (!$dep->id) {
                                    throw new \Exception('Entity does not exist', 412);
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
     * Manage Relative Position Update
     *
     * @param $field
     * @param $entity
     * @param $direction
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
     * Get root
     *
     * @param $entity
     * @return mixed
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
     * Get Risk C
     *
     * @param $c
     * @param $tRate
     * @param $vRate
     * @return int
     */
    protected function getRiskC($c, $tRate, $vRate)
    {
        $cRisks = (($c != -1) && ($tRate != -1) && ($vRate != -1)) ? $c * $tRate * $vRate : -1;

        return $cRisks;
    }

    /**
     * Get Risk I
     *
     * @param $i
     * @param $tRate
     * @param $vRate
     * @return int
     */
    protected function getRiskI($i, $tRate, $vRate)
    {
        $iRisks = (($i != -1) && ($tRate != -1) && ($vRate != -1)) ? $i * $tRate * $vRate : -1;

        return $iRisks;
    }

    /**
     * Get Risk D
     *
     * @param $d
     * @param $tRate
     * @param $vRate
     * @return int
     */
    protected function getRiskD($d, $tRate, $vRate)
    {
        $dRisks = (($d != -1) && ($tRate != -1) && ($vRate != -1)) ? $d * $tRate * $vRate : -1;

        return $dRisks;
    }

    /**
     * Get Target Risk
     *
     * @param $impacts
     * @param $tRate
     * @param $vRate
     * @param $vRateReduc
     * @return int|mixed
     */
    protected function getTargetRisk($impacts, $tRate, $vRate, $vRateReduc)
    {
        $targetRisk = ((max($impacts) != -1) && ($tRate != -1) && ($vRate != -1))
            ? max($impacts) * $tRate * ($vRate - $vRateReduc) : -1;

        return $targetRisk;
    }

    /**
     * Filter Patch Fields
     *
     * @param $data
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
     * Filter Post Fields
     *
     * @param $data
     * @param $entity
     * @param bool $forbiddenFields
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
     * Verify Rates
     *
     * @param $anrId
     * @param $instanceRisk
     * @param $data
     * @throws \Exception
     */
    protected function verifyRates($anrId, $data, $instanceRisk = null)
    {
        //TODO : ensure that this method is never called inside a loop
        $errors = [];

        $scaleThreat = $scaleVul = $scaleImpact = null;

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

        if ($instanceRisk) {
            if (isset($data['reductionAmount'])) {
                $reductionAmount = (int)$data['reductionAmount'];

                $vulnerabilityRate = (isset($data['vulnerabilityRate'])) ? (int)$data['vulnerabilityRate'] : $instanceRisk['vulnerabilityRate'];
                if (($vulnerabilityRate != -1) && (($reductionAmount < 0) || ($reductionAmount > $vulnerabilityRate))) {
                    $errors[] = 'Value for reduction amount is not valid';
                }
            }
        }

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
                    if ($value != -1) {
                        if (($value < $scaleImpact->get('min')) || ($value > $scaleImpact->get('max'))) {
                            $errors[] = 'Value for ' . $field . ' is not valid';
                        }
                    }
                }
            }
        }

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

                    if ($value != -1) {
                        if (($value < $scaleThreat->get('min')) || ($value > $scaleThreat->get('max'))) {
                            $errors[] = 'Value for ' . $field . ' is not valid';
                        }
                    }
                }
            }
        }

        if (count($errors)) {
            throw new \Exception(implode(', ', $errors), 412);
        }
    }

    /**
     * Encrypt
     *
     * @param $data
     * @param $key
     * @return string
     */
    protected function encrypt($data, $key)
    {
        return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }

    /**
     * Decrypt
     *
     * @param $data
     * @param $key
     * @return string
     */
    protected function decrypt($data, $key)
    {
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }

    /**
     * Return Git version
     *
     * @param $type (major|full)
     * @return version
     */
    protected function getVersion($type = 'major')
    {
        switch (strtolower($type)) {
            default:
            case 'major':
                if (!empty($this->monarcConf['version'])) {
                    return implode('.', array_slice(explode('.', $this->monarcConf['version']), 0, 2));
                } else {
                    return null;
                }
                break;
            case 'full':
                return isset($this->monarcConf['version']) ? $this->monarcConf['version'] : null;
                break;
        }
    }
}