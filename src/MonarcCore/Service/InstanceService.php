<?php
namespace MonarcCore\Service;
use DoctrineTest\InstantiatorTestAsset\ExceptionAsset;
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

        //create object
        $class = $this->get('entity');
        $instance = new $class();
        $instance->setLanguage($this->getLanguage());
        $instance->exchangeArray($data);

        //entity dependencies
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

                if ($data['position']) {
                    $implicitPosition = 3;
                    $previousInstancePosition = ($data['position'] > $instance->position) ? $data['position'] : $data['position'] - 1;
                    $fields = ['anr' => $anrId, 'position' => $previousInstancePosition, 'parent' => ($parent) ? $parent : 'null'];
                    $previous = $table->getEntityByFields($fields)[0];
                } else {
                    $implicitPosition = 1;
                    $previous = null;
                }

                $this->managePosition('parent', $instance, $parent, $implicitPosition, $previous, 'update');
            }
        }

        $this->updateImpacts($anrId, $instance->parent, $data);

        $instance->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        $instance->parent = ($instance->parent) ? $table->getEntity($instance->parent) : null;

        $id = $this->get('table')->save($instance);

        $this->updateRisks($anrId, $id);

        if ($instance->root) {
            $this->updateChildrenRoot($id, $instance->root);
        }

        $this->updateChildrenImpacts($instance);

        $this->updateBrothers($anrId, $instance, $data, $historic);

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

        $this->updateBrothers($anrId, $instance, $data, $historic);

        return $id;
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->getEntity($id);

        $this->managePosition('parent', $instance, $instance->parent, null, null, 'delete');

        $this->get('table')->delete($id);
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
     * @param $object
     * @param $instance
     */
    protected function updateLevels($parent, $object, &$instance) {

        //retrieve children
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($object);

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

        $instance = $this->get('table')->get($id);
        $instance['risks'] = $this->getRisks($instance, $anrId);
        $instance['oprisks'] = $this->getRisksOp($instance, $anrId);
        $instance['consequences'] = $this->getConsequences($instance, $anrId);

        return $instance;
    }

    /**
     * Get Risks
     *
     * @param $instance
     * @param $anrId
     * @return array
     */
    protected function getRisks($instance, $anrId) {

        $instanceId = $instance['id'];

        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRisks = $instanceRiskService->getInstanceRisks($instanceId, $anrId);

        $risks = [];
        foreach ($instanceRisks as $instanceRisk) {
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
                'threatDescription1' => $amv->threat->label1,
                'threatDescription2' => $amv->threat->label2,
                'threatDescription3' => $amv->threat->label3,
                'threatDescription4' => $amv->threat->label4,
                'threatRate' => $instanceRisk->threatRate,
                'vulnDescription1' => $amv->vulnerability->label1,
                'vulnDescription2' => $amv->vulnerability->label2,
                'vulnDescription3' => $amv->vulnerability->label3,
                'vulnDescription4' => $amv->vulnerability->label4,
                'vulnerabilityRate' => $instanceRisk->vulnerabilityRate,
                'kindOfMeasure' => $instanceRisk->kindOfMeasure,
                'reductionAmount' => $instanceRisk->reductionAmount,
                'c_impact' => $instance['c'],
                'c_risk' => $instanceRisk->riskC,
                'c_risk_enabled' => $amv->threat->c,
                'i_impact' => $instance['i'],
                'i_risk' => $instanceRisk->riskI,
                'i_risk_enabled' => $amv->threat->i,
                'd_impact' => $instance['d'],
                'd_risk' => $instanceRisk->riskD,
                'd_risk_enabled' => $amv->threat->d,
                't' => ($instanceRisk->kindOfMeasure == InstanceRisk::KIND_NOT_TREATED) ? false : true,
                'target_risk' => $instanceRisk->cacheTargetedRisk,
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
    protected function getRisksOp($instance, $anrId) {

        $instanceId = $instance['id'];

        /** @var InstanceRiskOpServiceService $instanceRiskOpService */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        $instanceRisksOp = $instanceRiskOpService->getInstanceRisksOp($instanceId, $anrId);

        $riskOps = [];
        foreach ($instanceRisksOp as $instanceRiskOp) {

            //retrieve rolf risks
            /** @var RolfRiskTable $rolfRiskTable */
            $rolfRiskTable = $this->get('rolfRiskTable');
            $rolfRisk = $rolfRiskTable->getEntity($instanceRiskOp->rolfRisk->id);

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
                'description1' => $rolfRisk->label1,
                'description2' => $rolfRisk->label2,
                'description3' => $rolfRisk->label3,
                'description4' => $rolfRisk->label4,
                'prob' => $instanceRiskOp->netProb,
                'kindOfMeasure' => $instanceRiskOp->kindOfMeasure,
                'r' => $instanceRiskOp->netR,
                'o' => $instanceRiskOp->netO,
                'l' => $instanceRiskOp->netL,
                'f' => $instanceRiskOp->netF,
                'p' => $instanceRiskOp->netP,
                'risk' => $risk,
                'comment' => $instanceRiskOp->comment,
                't' => ($instanceRiskOp->kindOfMeasure == InstanceRiskOp::KIND_NOT_TREATED) ? false : true,
                'target' => $target,
            ];
        }

        return $riskOps;
    }

    /**
     * Get Consequences
     *
     * @param $instance
     * @param $anrId
     * @return array
     */
    protected function getConsequences($instance, $anrId) {

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

        return $this->get('table')->findByAnr($anrId);
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