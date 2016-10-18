<?php
namespace MonarcCore\Service;


use MonarcCore\Model\Entity\Scale;
use MonarcCore\Model\Table\AnrTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ObjectObjectTable;

abstract class AbstractService extends AbstractServiceFactory
{
    use \MonarcCore\Model\GetAndSet;

    protected $serviceFactory;
    protected $table;
    protected $entity;
    protected $label;
    protected $language;
    protected $forbiddenFields = [];
    protected $dependencies = [];

    protected $monarcConf = array();

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
        if (is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                $this->set($k,$v);
            }
        } else {
            $this->serviceFactory = $serviceFactory;
        }
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return mixed
     */
    public function getMonarcConf()
    {
        return $this->monarcConf;
    }

    /**
     * @param mixed $language
     * @return mixed
     */
    public function setMonarcConf($conf)
    {
        $this->monarcConf = $conf;
        return $this->monarcConf;
    }

    /**
     * Parse Frontend Filter
     *
     * @param $filter
     * @param array $columns
     * @return array
     */
    protected function parseFrontendFilter($filter, $columns = array()) {

        $output = array();
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
    protected function parseFrontendOrder($order) {
        if(strpos($order, '_') !== false){
            $o = explode('_', $order);
            $order = "";
            foreach($o as $n => $oo){
                if($n <= 0){
                    $order = $oo;
                }else{
                    $order .= ucfirst($oo);
                }
            }
        }

        if ($order == null) {
            return null;
        } else if (substr($order, 0, 1) == '-') {
            return array(substr($order, 1), 'DESC');
        } else {
            return array($order, 'ASC');
        }
    }


    /**
     * Get Filtered Count
     *
     * @param null $filter
     * @return int
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null) {

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
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null){

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
    public function getEntity($id){

        return $this->get('table')->get($id);
    }

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true) {

        //$entity = $this->get('entity');
        $class = $this->get('entity');
        $entity = new $class();
        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
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
    public function update($id,$data){

        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \Exception('Entity not exist', 412);
        }

        $this->filterPostFields($data, $entity);

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id, $data){

        $entity = $this->get('table')->getEntity($id);
        $entity->setLanguage($this->getLanguage());

        foreach ($this->dependencies as $dependency) {
            if (!isset($data[$dependency])) {
                $data[$dependency] = $entity->$dependency->id;
            }
        }

        $entity->exchangeArray($data, true);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {
        return $this->get('table')->delete($id);
    }

    /**
     * Detele list
     *
     * @param $data
     */
    public function deleteList($data){
        return $this->get('table')->deleteList($data);
    }

    /**
     * Compare Entities
     *
     * @param $newEntity
     * @param $oldEntity
     * @return array
     */
    public function compareEntities($newEntity, $oldEntity){
        $deps = array();
        foreach($this->dependencies as $dep){
            $propertyname = $dep;
            $matching = [];
            if(preg_match("/(\[([a-z0-9]*)\])\(([a-z0-9]*)\)$/", $dep, $matching) != false){//si c'est 0 c'est pas bon non plus
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
                    if(!empty($oldValue) && is_object($oldValue)){
                        $oldValue = $oldValue->get('id');
                    }
                    if(!empty($value) && is_object($value)){
                        $value = $value->get('id');
                    }
                    if($oldValue != $value){
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
    public function historizeUpdate($type, $entity, $oldEntity) {

        $diff = $this->compareEntities($entity, $oldEntity);

        if (count($diff)) {
            $this->historize($entity, $type, 'update', implode(', ', $diff));
        }
    }

    /**
     * Historize create
     *
     * @param $type
     * @param $entity
     */
    public function historizeCreate($type, $entity, $details) {

        $this->historize($entity, $type, 'create', 'creation : ' . implode(', ', $details));
    }

    /**
     * Historize delete
     *
     * @param $type
     * @param $entity
     */
    public function historizeDelete($type, $entity, $details) {

        $this->historize($entity, $type, 'delete', 'delete : ' . implode(', ', $details));
    }

    /**
     * Historize
     *
     * @param $entity
     * @param $type
     * @param $verb
     * @param $details
     */
    public function historize($entity, $type, $verb, $details) {

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
    protected function formatDependencies(&$entity, $dependencies) {

        foreach($dependencies as $dependency) {
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
    protected function setDependencies(&$entity, $dependencies) {

        foreach($dependencies as $dependency) {
             // = preg_replace("/[0-9]/", "", $dependency);
            $deptable = $propertyname = $dependency;
            $matching = [];
            if(preg_match("/(\[([a-z0-9]*)\])\(([a-z0-9]*)\)$/", $deptable, $matching) != false){//si c'est 0 c'est pas bon non plus
                $propertyname = str_replace($matching[0], $matching[2], $deptable);
                $deptable = str_replace($matching[0], $matching[3], $deptable);
            }

            $value = $entity->get($propertyname);
            if (!is_null($value) && !empty($value) && !is_object($value)) {
                $tableName =  $deptable . 'Table';
                $method = 'set' . ucfirst($propertyname);
                if(! is_array($value)){
                    $dep = $this->get($tableName)->getReference($value);
                    if (!$dep->id) {
                        throw new \Exception('Entity not exist', 412);
                    }
                    $entity->$method($dep);
                }
                else{
                    $a_dep = [];
                    foreach($value as $v){
                        $dep = $this->get($tableName)->getReference($v);
                        if (!$dep->id) {
                            throw new \Exception('Entity not exist', 412);
                        }
                        $a_dep[] = $dep;
                    }
                    $entity->$method($a_dep);
                }
            }
        }
    }

    /**
     * Manage position
     *
     * @param $field
     * @param $parentId
     * @param $implicitPosition
     * @param null $previous
     * @return int
     */
    protected function managePositionCreation($field, $parentId, $implicitPosition, $previous = null) {
        $position = 0;

        switch ($implicitPosition) {
            case 1:
                $this->get('table')->changePositionsByParent($field, $parentId, 1, 'up', 'after');
                $position = 1;
                break;
            case 2:
                $maxPosition = $this->get('table')->maxPositionByParent($field, $parentId);
                $position = $maxPosition + 1;
                break;
            case 3:
                if(empty($previous)){ // dans le cas où le "$previous" n'est pas renseigné
                    $maxPosition = $this->get('table')->maxPositionByParent($field, $parentId);
                    $position = $maxPosition + 1;
                }else{
                    $previousObject = $this->get('table')->getEntity($previous);
                    $this->get('table')->changePositionsByParent($field, $parentId, $previousObject->position + 1, 'up', 'after');
                    $position = $previousObject->position + 1;
                }
                break;
        }

        return $position;
    }

    /**
     * Manage position update
     *
     * @param $field
     * @param $entity
     * @param $newParentId
     * @param $implicitPosition
     * @param null $previous
     * @param string $verb
     * @return int
     * @throws \Exception
     */
    protected function managePosition($field, $entity, $newParentId, $implicitPosition, $previous = null, $verb = 'update', $table = false) {

        $table = ($table) ? $table : $this->get('table');

        if ($implicitPosition == 3) {
            if (!$previous) {
                throw new \Exception('Field previous missing', 412);
            }
        }

        if ($entity->$field) {
            if ($newParentId == $entity->$field->id) {
                $position = $this->modifyPositionSameParent($implicitPosition, $field, $entity->$field->id, $entity->position, $previous, $verb, $table);
            } else {
                $position = $this->modifyPositionDifferentParent($implicitPosition, $field, $entity->$field->id, $entity->position, $previous, $verb, $newParentId, $table);
            }
        } else {
            if ($newParentId == null) {
                $position = $this->modifyPositionSameParent($implicitPosition, $field, null, $entity->position, $previous, $verb, $table);
            } else {
                $position = $this->modifyPositionDifferentParent($implicitPosition, $field, null, $entity->position, $previous, $verb, $newParentId, $table);
            }
        }

        return $position;
    }

    /**
     * Modify Position Same Parent
     *
     * @param $implicitPosition
     * @param $field
     * @param $entityParentId
     * @param $entityPosition
     * @param $previous
     * @param $verb
     * @return int
     */
    protected function modifyPositionSameParent($implicitPosition, $field, $entityParentId, $entityPosition, $previous, $verb, $table) {

        switch ($implicitPosition) {
            case 1:
                if ($verb == 'post') {
                    $table->changePositionsByParent($field, $entityParentId, $entityPosition, 'up', 'after');
                } else if ($verb != 'delete') {
                    $table->changePositionsByParent($field, $entityParentId, $entityPosition, 'down', 'after');
                    $table->changePositionsByParent($field, $entityParentId, 0, 'up', 'after');
                }
                $position = 0;
                break;
            case 2:
                $maxPosition = $table->maxPositionByParent($field, $entityParentId);
                if ($verb == 'post') {
                    $position = (is_null($maxPosition)) ? 0 : $maxPosition + 1;
                } else if ($verb != 'delete') {
                    $table->changePositionsByParent($field, $entityParentId, $entityPosition, 'down', 'after', true);
                    $position = $maxPosition;
                }
                break;
            case 3:
                $previousObject = $table->getEntity($previous);
                if ($verb == 'post') {
                    $table->changePositionsByParent($field, $entityParentId, $previousObject->position, 'up', 'after');
                    $position = $previousObject->position;
                } else if ($verb != 'delete') {
                    if ($entityPosition < $previousObject->position) {
                        $table->changePositionsByParent($field, $entityParentId, $entityPosition, 'down', 'after');
                        $table->changePositionsByParent($field, $entityParentId, $previousObject->position, 'up', 'after');
                        $position = $previousObject->position;
                    } else {
                        $table->changePositionsByParent($field, $entityParentId, $previousObject->position, 'up', 'after', true);
                        $table->changePositionsByParent($field, $entityParentId, $entityPosition, 'down', 'after', true);
                        $position = $previousObject->position + 1;
                    }
                }
                break;
            default:
                if ($verb == 'delete') {
                    $table->changePositionsByParent($field, $entityParentId, $entityPosition, 'down', 'after');
                    $position = $entityPosition;
                }
                break;
        }

        return $position;
    }

    /**
     * Modify Position Different Parent
     *
     * @param $implicitPosition
     * @param $field
     * @param $entityParentId
     * @param $entityPosition
     * @param $previous
     * @param $verb
     * @param $newParentId
     * @return int
     */
    protected function modifyPositionDifferentParent($implicitPosition, $field, $entityParentId, $entityPosition,  $previous, $verb, $newParentId, $table) {
        $table->changePositionsByParent($field, $entityParentId, $entityPosition, 'down', 'after', true);

        $position = 0;
        switch ($implicitPosition) {
            case 1:
                $table->changePositionsByParent($field, $newParentId, 0, 'up', 'after');
                $position = 0;
                break;
            case 2:
                if ($verb == 'post') {
                    $maxPosition = $table->maxPositionByParent($field, $newParentId);
                    $position = $maxPosition + 1;
                } else if ($verb != 'delete') {
                    $maxPosition = $table->maxPositionByParent($field, $newParentId);
                    //$table->changePositionsByParent($field, $newParentId, $entityPosition, 'down', 'after');
                    $position = $maxPosition + 1;
                }
                break;
            case 3:
                $previousObject = $table->getEntity($previous);
                $table->changePositionsByParent($field, $newParentId, $previousObject->position, 'up', 'after', true);
                $position = $previousObject->position + 1;
                break;
        }

        return $position;

    }

    protected function manageRelativePositionUpdate($field, $entity, $direction) {
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
    public function getRoot($entity) {
        if (!is_null($entity->getParent())) {
            return $this->getRoot($entity->getParent());
        } else {
            return $entity;
        }
    }

    protected function getRiskC($c, $tRate, $vRate) {
        $cRisks = (($c != -1) && ($tRate != -1) && ($vRate != -1)) ? $c * $tRate * $vRate : -1;

        return $cRisks;
    }

    protected function getRiskI($i, $tRate, $vRate) {
        $iRisks = (($i != -1) && ($tRate != -1) && ($vRate != -1)) ? $i * $tRate * $vRate : -1;

        return $iRisks;
    }

    protected function getRiskD($d, $tRate, $vRate) {
        $dRisks = (($d != -1) && ($tRate != -1) && ($vRate != -1)) ? $d * $tRate * $vRate : -1;

        return $dRisks;
    }

    protected function getTargetRisk($c, $i, $d, $tRate, $vRate, $vRateReduc) {
        $targetRisk = (($c != -1) && ($i != -1) && ($d != -1) && ($tRate != -1) && ($vRate != -1))
            ? max([$c, $i, $d]) * $tRate * ($vRate - $vRateReduc) : -1;

        return $targetRisk;
    }

    protected function filterPatchFields(&$data) {
        if (is_array($data)) {
            foreach (array_keys($data) as $key) {
                if (in_array($key, $this->forbiddenFields)) {
                    unset($data[$key]);
                }
            }
        }
    }

    protected function filterPostFields(&$data, $entity, $forbiddenFields = false) {
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
    protected function verifyRates($anrId, $data, $instanceRisk = null) {

        $errors = [];

        if (isset($data['threatRate'])) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scale = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => Scale::TYPE_THREAT]);

            $scale = $scale[0];

            $prob = (int) $data['threatRate'];

            if (($prob != -1) && (($prob < $scale->get('min')) || ($prob > $scale->get('max')))) {
                $errors[] = 'Value for probability is not valid';
            }
        }

        if (isset($data['vulnerabilityRate'])) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scale = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => Scale::TYPE_VULNERABILITY]);

            $scale = $scale[0];

            $prob = (int) $data['vulnerabilityRate'];

            if (($prob != -1) && (($prob < $scale->get('min')) || ($prob > $scale->get('max')))) {
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

        if (isset($data['c']) || isset($data['i']) || isset($data['d'])) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scale = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => Scale::TYPE_IMPACT]);

            $scale = $scale[0];

            $fields = ['c', 'i', 'd'];

            foreach ($fields as $field) {
                if (isset($data[$field])) {
                    $value = (int) $data['c'];

                    if ($value != -1) {
                        if (($value < $scale->get('min')) || ($value > $scale->get('max'))) {
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

    protected function encrypt($data, $key) {
        return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }

    protected function decrypt($data, $key) {
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
    }

    /**
     * Return Git version
     *
     * @param $type (major|full)
     * @return version
     */
    protected function getVersion($type = 'major'){
        switch(strtolower($type)){
            default:
            case 'major':
                if(!empty($this->monarcConf['version'])){
                    return implode('.',array_slice(explode('.', $this->monarcConf['version']),0,2));
                }else{
                    return null;
                }
                break;
            case 'full':
                return isset($this->monarcConf['version'])?$this->monarcConf['version']:null;
                break;
        }
    }
}
