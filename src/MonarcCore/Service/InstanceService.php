<?php
namespace MonarcCore\Service;
use DoctrineTest\InstantiatorTestAsset\ExceptionAsset;
use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Entity\Instance;
use MonarcCore\Model\Entity\InstanceRisk;
use MonarcCore\Model\Entity\InstanceRiskOp;
use MonarcCore\Model\Entity\Object;
use MonarcCore\Model\Entity\Scale;
use MonarcCore\Model\Table\AmvTable;
use MonarcCore\Model\Table\InstanceConsequenceTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\RolfRiskTable;
use MonarcCore\Model\Table\ScaleTable;
use MonarcCore\Model\Table\ScaleImpactTypeTable;
use Zend\EventManager\EventManager;


/**
 * Instance Service
 *
 * Class InstanceService
 * @package MonarcCore\Service
 */
class InstanceService extends AbstractService
{
    protected $dependencies = ['anr', 'asset', 'object'];
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];

    protected $amvTable;
    protected $anrTable;
    protected $assetTable;
    protected $instanceTable;
    protected $objectTable;
    protected $rolfRiskTable;
    protected $scaleTable;
    protected $scaleImpactTypeTable;
    protected $instanceConsequenceService;
    protected $instanceRiskService;
    protected $instanceRiskOpService;
    protected $instanceConsequenceTable;
    protected $objectObjectService;
    protected $instanceConsequenceEntity;
    protected $forbiddenFields = ['anr', 'asset', 'object', 'ch', 'dh', 'ih'];

    /**
     * Instantiate Object To Anr
     *
     * @param $anrId
     * @param $data
     * @return mixed|null
     * @throws \Exception
     */
    public function instantiateObjectToAnr($anrId, $data, $managePosition = true) {

        //retrieve object properties
        $object = $this->get('objectTable')->getEntity($data['object']);

        $authorized = false;
        foreach($object->anrs as $anr) {
            if ($anr->id == $anrId) {
                $authorized = true;
            }
        }

        if (!$authorized) {
            throw new \Exception('Object is not an object of this anr', 412);
        }

        $data['anr'] = $anrId;

        $commonProperties = ['name1', 'name2', 'name3', 'name4', 'label1', 'label2', 'label3', 'label4'];
        foreach($commonProperties as $commonProperty) {
            $data[$commonProperty] = $object->$commonProperty;
        }

        //set impacts
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $parent = ($data['parent']) ? $table->getEntity($data['parent']) : null;
        $parentId = ($data['parent']) ? ($data['parent']) : null;

        $this->updateImpacts($anrId, $parent, $data);

        //asset
        if (isset($object->asset)) {
            $data['asset'] = $object->asset->id;
        }

        //create instance
        $class = $this->get('entity');
        $instance = new $class();
        $instance->setLanguage($this->getLanguage());
        $instance->exchangeArray($data);

        //instance dependencies
        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        //parent and root
        // on fait un getEntity juste au dessus : $parent = ($data['parent']) ? $table->getEntity($data['parent']) : null;
        $instance->setParent($parent);
        $root = ($data['parent']) ? $this->getRoot($instance) : null;
        $instance->setRoot($root);

        //level
        $this->updateLevels($parent, $data['object'], $instance);

        //manage position
        if ($managePosition) {
            if ($data['position']) {
                $fields = ['anr' => $anrId, 'position' => $data['position'], 'parent' => ($parentId) ? $parentId : 'null'];
                $previousInstance = $table->getEntityByFields($fields);
                if ($previousInstance) {
                    $previousInstance = $previousInstance[0];
                    $implicitPosition = 3;
                } else {
                    $previousInstance = null;
                    $implicitPosition = 2;
                }
            } else {
                $previousInstance = null;
                $implicitPosition = 1;
            }

            $this->managePosition('parent', $instance, $parentId, $implicitPosition, $previousInstance, 'post');
        }

        $id = $table->createInstanceToAnr($anrId, $instance, $parent, $instance->position);

        //instances risk
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRiskService->createInstanceRisks($id, $anrId, $object);

        //instances risks op
        /** @var InstanceRiskOpService $instanceRiskOpService */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        $instanceRiskOpService->createInstanceRisksOp($id, $anrId, $object);

        //instances consequences
        $this->createInstanceConsequences($id, $anrId, $object);

        $this->createChildren($anrId, $id, $object);

        return $id;
    }

    protected function getRecursiveChild(&$childList, $id) {
        $childs = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        if (count($childs)) {
            foreach ($childs as $child) {
                $childList[] = $child['id'];
                $this->getRecursiveChild($childList, $child['id']);
            }
        }
    }


    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function updateInstance($anrId, $id, $data, $historic = []){

        $historic[] = $id;

        /** @var InstanceTable $table */
        $table = $this->get('table');

        $instance = $table->getEntity($id);

        if (!$instance) {
            throw new \Exception('Instance not exist', 412);
        }

        //security
        $this->filterPostFields($data, $instance);

        $instance->setDbAdapter($table->getDb());
        $instance->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        if (isset($data['position'])) {
            if (($data['position'] != $instance->position) || ($data['parent'] != $instance->parent)) {

                $parent = (isset($data['parent']) && $data['parent']) ? $data['parent'] : null;
                $parentId = ($parent) ? $parent['id'] : null;

                if ($data['position']) {
                    $previousInstancePosition = ($data['position'] > $instance->position) ? $data['position'] : $data['position'] - 1;
                    $fields = [
                        'anr' => $anrId,
                        'position' => $previousInstancePosition,
                        'parent' => $parentId
                    ];

                    $entities = $table->getEntityByFields($fields);

                    if ($entities) {
                        $implicitPosition = 3;
                        $previous = $entities[0];
                    } else {
                        $implicitPosition = 1;
                        $previous = null;
                    }
                } else {
                    $implicitPosition = 1;
                    $previous = null;
                }

                $this->managePosition('parent', $instance, $parentId, $implicitPosition, $previous, 'update');
            }
        }

        if (isset($data['consequences'])) {
            foreach($data['consequences'] as $consequence) {
                $dataConsequences = [
                    'anr' => $anrId,
                    'c' => intval($consequence['c_risk']),
                    'i' => intval($consequence['i_risk']),
                    'd' => intval($consequence['d_risk']),
                    'isHidden' => intval($consequence['is_hidden']),
                ];

                /** @var InstanceConsequenceService $instanceConsequenceService */

                $instanceConsequenceService = $this->get('instanceConsequenceService');
                $instanceConsequenceService->patch($consequence['id'], $dataConsequences);
            }
        }

        $this->updateImpacts($anrId, $instance->parent, $data);


        $instance->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        if ($instance->parent) {
            $parentId = (is_object($instance->parent)) ? $instance->parent->id : $instance->parent['id'];
            $instance->parent = $table->getEntity($parentId);
        } else {
            $instance->parent = null;
        }
        if ($instance->root) {
            $rootId = (is_object($instance->root)) ? $instance->root->id : $instance->root['id'];
            $instance->root = $table->getEntity($rootId);
        } else {
            $instance->root = null;
        }


        $id = $this->get('table')->save($instance);

        $this->updateRisks($anrId, $id);

        if ($instance->root) {
            $this->updateChildrenRoot($id, $instance->root);
        }

        $this->updateChildrenImpacts($instance);

        $this->updateBrothers($anrId, $instance, $data, $historic);

        $this->objectImpacts($instance);

        return $id;
    }

    /**
     * Patch
     *
     * @param $anrId
     * @param $id
     * @param $data
     * @param array $historic
     * @return mixed|null
     * @throws \Exception
     */
    public function patchInstance($anrId, $id, $data, $historic = []){

        //security
        $this->filterPatchFields($data);

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->getEntity($id);

        if (!$instance) {
            throw new \Exception('Instance not exist', 412);
        }
        $instanceParent = ($instance->parent) ? $instance->parent->id : null;

        if (isset($data['position'])) {
            if (($data['position'] != $instance->position) || ($data['parent'] != $instanceParent)) {

                $parent = (isset($data['parent']) && $data['parent']) ? $data['parent'] : null;

                if ($data['position']) {
                    if (($data['parent'] == $instanceParent) && ($data['position'] > $instance->position)) {
                        $previousInstancePosition = $data['position'];
                    } else {
                        $previousInstancePosition = $data['position'] - 1;
                    }
                    $fields = ['anr' => $anrId, 'position' => $previousInstancePosition, 'parent' => ($parent) ? $parent : 'null'];
                    $previous = $table->getEntityByFields($fields);
                    if ($previous) {
                        $implicitPosition = 3;
                        $previous = $previous[0];
                    } else {
                        $implicitPosition = 2;
                        $previous = null;
                    }
                } else {
                    $implicitPosition = 1;
                    $previous = null;
                }
                $this->managePosition('parent', $instance, $parent, $implicitPosition, $previous, 'update');
            }
        }

        //parent values
        $parent = null;
        if (isset($data['parent'])) {
            if ($data['parent']) {
                $parent = ($data['parent']) ? $table->getEntity($data['parent']) : null;
                $instance->setParent($parent);

                $root = ($data['parent']) ? $this->getRoot($instance) : null;
                $instance->setRoot($root);
            } else {
                $parent = null;
                $instance->setParent($parent);
                $root = null;
                $instance->setRoot($root);
            }
        }

        $this->updateImpacts($anrId, $parent, $data);

        $this->updateLevels($parent, $instance->object->id, $instance);

        $instance->setLanguage($this->getLanguage());
        $instance->exchangeArray($data, true);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        $instance->parent = ($instance->parent) ? $table->getEntity($instance->parent) : null;

        $id = $table->save($instance);

        $this->updateRisks($anrId, $id);

        if ($instance->root) {
            $this->updateChildrenRoot($id, $instance->root);
        }

        $this->updateChildrenImpacts($instance);

        $data['asset'] = $instance->asset->id;
        $data['object'] = $instance->object->id;
        $data['name1'] = $instance->name1;
        $data['label1'] = $instance->label1;

        $this->updateBrothers($anrId, $instance, $data, $historic);

        $this->objectImpacts($instance);

        return $id;
    }

    /**
     * Delete
     *
     * @param $id
     * @throws \Exception
     */
    public function delete($id) {

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->getEntity($id);

        if (!$instance) {
            throw new \Exception('Instance not exist', 412);
        }

        if ($instance->level != Instance::LEVEL_ROOT) {
            throw new \Exception('This is not a root instance', 412);
        }

        $this->managePosition('parent', $instance, $instance->parent, null, null, 'delete');

        $this->get('table')->delete($id);
    }

    /**
     * Object Impacts
     *
     * @param $instance
     */
    protected function objectImpacts($instance) {
        $objectId = $instance->object->id;
        $data = [
            'name1' => $instance->name1,
            'name2' => $instance->name2,
            'name3' => $instance->name3,
            'name4' => $instance->name4,
            'label1' => $instance->label1,
            'label2' => $instance->label2,
            'label3' => $instance->label3,
            'label4' => $instance->label4,
        ];

        $eventManager = new EventManager();
        $eventManager->setIdentifiers('object');

        $sharedEventManager = $eventManager->getSharedManager();
        $eventManager->setSharedManager($sharedEventManager);
        $eventManager->trigger('patch', null, compact(['objectId', 'data']));
    }

    /**
     * Create Children
     *
     * @param $anrId
     * @param $parentId
     * @param $object
     */
    protected function createChildren($anrId, $parentId, $object) {

        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($object);

        foreach($children as $child) {
            $data = [
                'object' => $child->child->id,
                'parent' => $parentId,
                'position' => $child->position,
                'c' => '-1',
                'i' => '-1',
                'd' => '-1'
            ];
            $this->instantiateObjectToAnr($anrId, $data, false);
        }
    }

    /**
     * Update level
     *
     * @param $parent
     * @param $objectId
     * @param $instance
     */
    protected function updateLevels($parent, $objectId, &$instance) {

        //retrieve children
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($objectId);

        if (!$parent) {
            $instance->setLevel(Instance::LEVEL_ROOT);
        } else if (!count($children)) {
            $instance->setLevel(Instance::LEVEL_LEAF);
        } else {
            $instance->setLevel(Instance::LEVEL_INTER);
        }
    }

    /**
     * Update Children Root
     *
     * @param $instanceId
     * @param $root
     */
    protected function updateChildrenRoot($instanceId, $root) {

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $children = $table->getEntityByFields(['parent' => $instanceId]);

        foreach($children as $child) {
            $child->setRoot($root);
            $table->save($child);
            $this->updateChildrenRoot($child->id, $root);
        }
    }

    /**
     * Update Impacts
     *
     * @param $anrId
     * @param $parent
     * @param $data
     */
    protected function updateImpacts($anrId, $parent, &$data) {

        $this->verifyRates($anrId, $data);

        //values
        if (isset($data['c'])) {
            $data['ch'] = ($data['c'] == -1) ? 1 : 0;
        }
        if (isset($data['i'])) {
            $data['ih'] = ($data['i'] == -1) ? 1 : 0;
        }
        if (isset($data['d'])) {
            $data['dh'] = ($data['d'] == -1) ? 1 : 0;
        }

        if (isset($data['c']) || isset($data['i']) || isset($data['d'])) {
            if (((isset($data['c'])) && ($data['c'] == -1))
                || ((isset($data['i'])) && ($data['i'] == -1))
                || ((isset($data['d'])) && ($data['d'] == -1)))  {
                if ($parent) {
                    if ((isset($data['c'])) && ($data['c'] == -1)) {
                        $data['c'] = (int) $parent->c;
                    }

                    if ((isset($data['i'])) && ($data['i'] == -1)) {
                        $data['i'] = (int) $parent->i;
                    }

                    if ((isset($data['d'])) && ($data['d'] == -1)) {
                        $data['d'] = (int) $parent->d;
                    }
                }
            }
        }
    }

    /**
     * Update children
     *
     * @param $instance
     */
    protected function updateChildrenImpacts($instance) {

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $children = $table->getEntityByFields(['parent' => $instance->id]);

        foreach($children as $child) {

            if ($child->ch) {
                $child->c = $instance->c;
            }

            if ($child->ih) {
                $child->i = $instance->i;
            }

            if ($child->dh) {
                $child->d = $instance->d;
            }

            $table->save($child);

            //update children
            $childrenData = [
                'c' => $child->c,
                'i' => $child->i,
                'd' => $child->d,
            ];
            $this->updateChildrenImpacts($child, $childrenData);
        }
    }

    /**
     * Update Brothers
     *
     * @param $anrId
     * @param $instance
     * @param $data
     * @param $historic
     */
    protected function updateBrothers($anrId, $instance, $data, $historic) {
        //if source object is global, reverberate to other instance with the same source object
        if ($instance->object->scope == Object::SCOPE_GLOBAL) {
            //retrieve instance with same object source
            /** @var InstanceTable $table */
            $table = $this->get('table');
            $brothers = $table->getEntityByFields(['object' => $instance->object->id]);
            foreach($brothers as $brother) {
                if (($brother->id != $instance->id) && (!in_array($brother->id, $historic))) {
                    $this->updateInstance($anrId, $brother->id, $data, $historic);
                }
            }
        }
    }

    /**
     * Update Risks
     *
     * @param $anrId
     * @param $instanceId
     */
    protected function updateRisks($anrId, $instanceId) {
        //instances risk
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRisks = $instanceRiskService->getInstanceRisks($instanceId, $anrId);

        foreach($instanceRisks as $instanceRisk) {
            $instanceRiskService->updateRisks($instanceRisk->id);
        }
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntityByIdAndAnr($id, $anrId){

        $instance = $this->get('table')->get($id); // pourquoi on n'a pas de contrôle sur $instance['anr']->id == $anrId ?
        $instance['risks'] = $this->getRisks($anrId, $instance);
        $instance['oprisks'] = $this->getRisksOp($anrId, $instance);
        $instance['consequences'] = $this->getConsequences($anrId, $instance);
        $instance['assets'] = $this->getAssets($instance);

        return $instance;
    }

    /**
     * Get Similar Assets to ANR
     *
     * @param $instance
     * @return array
     */
    public function getAssets($instance){
        $instances = array();
        $result = $this->get('table')->getRepository()
            ->createQueryBuilder('t')
            ->where("t.anr = ?1")
            ->andWhere("t.object = ?2")
            ->setParameter(1,$instance['anr']->id)
            ->setParameter(2,$instance['object']->id)
            ->getQuery()->getResult();
        $anr = $instance['anr']->getJsonArray();

        $i = 0;
        foreach($result as $r){
            $i++;
            $asc = $this->get('table')->getAscendance($r,$i);
            $names = array(
                'name1' => $anr['label1'],//." > ".$r->get('name1'),
                'name2' => $anr['label2'],//." > ".$r->get('name2'),
                'name3' => $anr['label3'],//." > ".$r->get('name3'),
                'name4' => $anr['label4'],//." > ".$r->get('name4'),
            );
            foreach($asc as $a){
                $names['name1'] .= ' > '.$a['name1'];
                $names['name2'] .= ' > '.$a['name2'];
                $names['name3'] .= ' > '.$a['name3'];
                $names['name4'] .= ' > '.$a['name4'];
            }
            $names['id'] = $r->get('id');
            $instances[] = $names;
        }
        return $instances;
    }

    /**
     * Get Risks
     *
     * @param $instance
     * @param $anrId
     * @return array
     */
    public function getRisks($anrId, $instance = null) {

        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');

        if ($instance) {
            $instanceId = $instance['id'];
            $instance = $instanceTable->getEntity($instanceId);

            if ($instance->object->asset->type == Asset::ASSET_PRIMARY) {
                //retrieve descendants
                $instances = $instanceTable->getDescendantsObjects($instanceId);
                $instances[] = $instance;

                $instancesRisks = $this->getInstancesRisks($anrId, $instances);

            } else {
                /** @var InstanceRiskService $instanceRiskService */
                $instanceRiskService = $this->get('instanceRiskService');
                $instancesRisks = $instanceRiskService->getInstanceRisks($instanceId, $anrId);
            }
        } else {
            $instances = $instanceTable->getEntityByFields(['anr' => $anrId]);

            $instancesRisks = $this->getInstancesRisks($anrId, $instances);
        }

        //order by max risk
        $tmpInstancesRisks = [];
        $tmpInstancesMaxRisks = [];
        foreach($instancesRisks as $instancesRisk) {
            $tmpInstancesRisks[$instancesRisk->id] = $instancesRisk;
            $tmpInstancesMaxRisks[$instancesRisk->id] = $instancesRisk->cacheMaxRisk;
        }
        arsort($tmpInstancesMaxRisks);
        $instancesRisks = [];
        foreach($tmpInstancesMaxRisks as $id => $tmpInstancesMaxRisk) {
            $instancesRisks[] = $tmpInstancesRisks[$id];
        }

        $risks = [];
        foreach ($instancesRisks as $instanceRisk) {
            /** @var AmvTable $amvTable */
            $amvTable = $this->get('amvTable');
            $amv = $amvTable->getEntity($instanceRisk->amv->id);

            for($i =1; $i<=3; $i++) {
                $name = 'measure' . $i;
                if ($amv->$name) {
                    ${$name} = $amv->$name->getJsonArray();
                    unset(${$name}['__initializer__']);
                    unset(${$name}['__cloner__']);
                    unset(${$name}['__isInitialized__']);
                } else {
                    ${$name} = null;
                }
            }

            $risks[] = [
                'id' => $instanceRisk->id,
                'instance' => $instanceRisk->instance->id,
                'amv' => $amv->id,
                'asset' => $amv->asset->id,
                'assetDescription1' => $amv->asset->label1,
                'assetDescription2' => $amv->asset->label2,
                'assetDescription3' => $amv->asset->label3,
                'assetDescription4' => $amv->asset->label4,
                'threat' => $amv->threat->id,
                'threatDescription1' => $amv->threat->label1,
                'threatDescription2' => $amv->threat->label2,
                'threatDescription3' => $amv->threat->label3,
                'threatDescription4' => $amv->threat->label4,
                'threatRate' => $instanceRisk->threatRate,
                'vulnerability' => $amv->vulnerability->id,
                'vulnDescription1' => $amv->vulnerability->label1,
                'vulnDescription2' => $amv->vulnerability->label2,
                'vulnDescription3' => $amv->vulnerability->label3,
                'vulnDescription4' => $amv->vulnerability->label4,
                'vulnerabilityRate' => $instanceRisk->vulnerabilityRate,
                'kindOfMeasure' => $instanceRisk->kindOfMeasure,
                'reductionAmount' => $instanceRisk->reductionAmount,
                'c_impact' => ($instance) ? $instance->c : null,
                'c_risk' => $instanceRisk->riskC,
                'c_risk_enabled' => $amv->threat->c,
                'i_impact' => ($instance) ? $instance->i : null,
                'i_risk' => $instanceRisk->riskI,
                'i_risk_enabled' => $amv->threat->i,
                'd_impact' => ($instance) ? $instance->d : null,
                'd_risk' => $instanceRisk->riskD,
                'd_risk_enabled' => $amv->threat->d,
                't' => ($instanceRisk->kindOfMeasure == InstanceRisk::KIND_NOT_TREATED) ? false : true,
                'target_risk' => $instanceRisk->cacheTargetedRisk,
                'max_risk' => $instanceRisk->cacheMaxRisk,
                'comment' => $instanceRisk->comment,
                'measure1' => $measure1,
                'measure2' => $measure2,
                'measure3' => $measure3,
            ];
        }

        return $risks;
    }

    /**
     * Get Risks Op
     *
     * @param $instance
     * @param $anrId
     * @return array
     */
    public function getRisksOp($anrId, $instance = null) {

        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');

        if ($instance) {
            $instanceId = $instance['id'];
            $instance = $instanceTable->getEntity($instanceId);

            //retrieve descendants
            $instances = $instanceTable->getDescendantsObjects($instanceId);
            $instances[] = $instance;
        } else {
            $instances = $instanceTable->getEntityByFields(['anr' => $anrId]);
        }
        $instancesIds = [];
        foreach ($instances as $instance2) {
            $instancesIds[] = $instance2->id;
        }

        //retrieve risks instances
        /** @var InstanceRiskOpService $instanceRiskServiceOp */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        $instancesRisksOp = $instanceRiskOpService->getInstancesRisksOp($instancesIds, $anrId);

        //order by net risk
        $tmpInstancesRisksOp = [];
        $tmpInstancesMaxRisksOp = [];
        foreach($instancesRisksOp as $instancesRiskOp) {
            $tmpInstancesRisksOp[$instancesRiskOp->id] = $instancesRiskOp;
            $tmpInstancesMaxRisksOp[$instancesRiskOp->id] = $instancesRiskOp->cacheNetRisk;
        }
        arsort($tmpInstancesMaxRisksOp);
        $instancesRisksOp = [];
        foreach($tmpInstancesMaxRisksOp as $id => $tmpInstancesMaxRiskOp) {
            $instancesRisksOp[] = $tmpInstancesRisksOp[$id];
        }

        $riskOps = [];
        foreach ($instancesRisksOp as $instanceRiskOp) {

            $fields = ['r', 'o', 'l', 'f', 'p'];

            $maxNet = -1;
            $maxTarget = -1;
            foreach ($fields as $field) {
                $nameNet = 'net' . $field;
                $nameTarget = 'net' . $field;
                if ($instanceRiskOp->$nameNet > $maxNet) {
                    $maxNet = $instanceRiskOp->$nameNet;
                }
                if ($instanceRiskOp->$nameTarget > $maxTarget) {
                    $maxTarget = $instanceRiskOp->$nameTarget;
                }
            }

            $risk = (($maxNet != -1) && ($instanceRiskOp->netProb != -1)) ? $instanceRiskOp->netProb * $maxNet : '';
            $target = (($maxTarget != -1) && ($instanceRiskOp->netProb != -1)) ? $instanceRiskOp->netProb * $maxTarget : '';

            $riskOps[] = [
                'id' => $instanceRiskOp->id,
                'description1' => $instanceRiskOp->riskCacheLabel1,
                'description2' => $instanceRiskOp->riskCacheLabel2,
                'description3' => $instanceRiskOp->riskCacheLabel3,
                'description4' => $instanceRiskOp->riskCacheLabel4,
                'prob' => $instanceRiskOp->netProb,
                'kindOfMeasure' => $instanceRiskOp->kindOfMeasure,
                'r' => $instanceRiskOp->netR,
                'o' => $instanceRiskOp->netO,
                'l' => $instanceRiskOp->netL,
                'f' => $instanceRiskOp->netF,
                'p' => $instanceRiskOp->netP,
                'brut_prob' => $instanceRiskOp->brutProb,
                'brut_r' => $instanceRiskOp->brutR,
                'brut_o' => $instanceRiskOp->brutO,
                'brut_l' => $instanceRiskOp->brutL,
                'brut_f' => $instanceRiskOp->brutF,
                'brut_p' => $instanceRiskOp->brutP,
                'risk' => $risk,
                'comment' => $instanceRiskOp->comment,
                't' => ($instanceRiskOp->kindOfMeasure == InstanceRiskOp::KIND_NOT_TREATED) ? false : true,
                'target' => $target,
            ];
        }

        return $riskOps;
    }



    protected function getInstancesRisks($anrId, $instances) {

        $instancesIds = [];

        //verify and retrieve duplicate global
        $globalInstancesIds = [];
        $duplicateGlobalObject = [];
        foreach ($instances as $instance2) {
            if ($instance2->object->scope == Object::SCOPE_GLOBAL) {
                if (in_array($instance2->object->id, $globalInstancesIds)) {
                    $duplicateGlobalObject[] = $instance2->object->id;
                } else {
                    $globalInstancesIds[] = $instance2->object->id;
                }

            }
        }

        //retrieve instance associated to duplicate global object
        $specialInstances = $instancesIds = [];
        foreach ($instances as $instance2) {
            if (in_array($instance2->object->id, $duplicateGlobalObject)) {
                $specialInstances[] = $instance2->id;
            } else {
                $instancesIds[] = $instance2->id;
            }
        }

        //retrieve risks instances
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instancesRisks = $instanceRiskService->getInstancesRisks($instancesIds, $anrId);

        //retrieve risks special instances
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $specialInstancesRisks = $instanceRiskService->getInstancesRisks($specialInstances, $anrId);

        //if there are several times the same risk, keep the highest
        $specialInstancesUniquesRisks = [];
        foreach ($specialInstancesRisks as $risk) {
            if (
                (isset($specialInstancesUniquesRisks[$risk->amv->id]))
                &&
                ($risk->cacheMaxRisk > $specialInstancesUniquesRisks[$risk->amv->id]->cacheMaxRisk)
            ) {
                $specialInstancesUniquesRisks[$risk->amv->id] = $risk;
            } else {
                $specialInstancesUniquesRisks[$risk->amv->id] = $risk;
            }
        }

        $instancesRisks = $instancesRisks + $specialInstancesUniquesRisks;

        return $instancesRisks;
    }

    /**
     * Get Consequences
     *
     * @param $instance
     * @param $anrId
     * @return array
     */
    protected function getConsequences($anrId, $instance) {

        $instanceId = $instance['id'];

        /** @var InstanceConsequenceTable $table */
        $table = $this->get('instanceConsequenceTable');
        $instanceConsequences = $table->getEntityByFields(['anr' => $anrId, 'instance' => $instanceId]);

        $consequences = [];
        foreach ($instanceConsequences as $instanceConsequence) {
            /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
            $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
            $scaleImpactType = $scaleImpactTypeTable->getEntity($instanceConsequence->scaleImpactType->id);

            $consequences[] = [
                'id' => $instanceConsequence->id,
                'scaleImpactType' => $scaleImpactType->type,
                'scaleImpactTypeDescription1' => $scaleImpactType->label1,
                'scaleImpactTypeDescription2' => $scaleImpactType->label2,
                'scaleImpactTypeDescription3' => $scaleImpactType->label3,
                'scaleImpactTypeDescription4' => $scaleImpactType->label4,
                'c_risk' => $instanceConsequence->c,
                'i_risk' => $instanceConsequence->i,
                'd_risk' => $instanceConsequence->d,
                'is_hidden' => $instanceConsequence->isHidden,
            ];
        }

        return $consequences;
    }


    /**
     * Find By Anr
     *
     * @param $anrId
     * @return mixed
     */
    public function findByAnr($anrId) {

        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');
        $instances = $instanceTable->getEntityByFields(['anr' => $anrId], ['position' => 'ASC']);

        foreach($instances as $key => $instance) {
            $instanceArray = $instance->getJsonArray();
            $instanceArray['scope'] = $instance->object->scope;

            $instances[$key] = $instanceArray;
        }

        return $instances;
    }

    /**
     * Create Instance Consequences
     *
     * @param $instanceId
     * @param $anrId
     * @param $object
     */
    public function createInstanceConsequences($instanceId, $anrId, $object) {

        //retrieve scale impact types
        /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
        $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
        $scalesImpactTypes = $scaleImpactTypeTable->getEntityByFields(['anr' => $anrId, 'isHidden' => 0]);

        /** @var InstanceConsequenceTable $instanceConsequenceTable */
        $instanceConsequenceTable = $this->get('instanceConsequenceTable');

        foreach($scalesImpactTypes as $scalesImpactType) {
            $data = [
                'anr' => $this->get('anrTable')->getEntity($anrId),
                'instance' => $this->get('instanceTable')->getEntity($instanceId),
                'object' => $object,
                'scaleImpactType' => $scalesImpactType,
            ];


            $class = $this->get('instanceConsequenceEntity');
            $instanceConsequenceEntity = new $class();

            $instanceConsequenceEntity->exchangeArray($data);

            $instanceConsequenceTable->save($instanceConsequenceEntity);
        }
    }
}
