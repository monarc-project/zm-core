<?php
namespace MonarcCore\Service;
use DoctrineTest\InstantiatorTestAsset\ExceptionAsset;
use MonarcCore\Model\Entity\Instance;
use MonarcCore\Model\Entity\InstanceRisk;
use MonarcCore\Model\Entity\InstanceRiskOp;
use MonarcCore\Model\Entity\Object;
use MonarcCore\Model\Entity\Scale;
use MonarcCore\Model\Table\AmvTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\RolfRiskTable;
use MonarcCore\Model\Table\ScaleTable;
use MonarcCore\Model\Table\ScaleTypeTable;


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
    protected $instanceConsequenceService;
    protected $objectObjectService;

    /**
     * Instantiate Object To Anr
     *
     * @param $anrId
     * @param $objectId
     * @param $parentId
     * @param $position
     * @param $impacts
     * @return mixed|null
     * @throws \Exception
     */
    public function instantiateObjectToAnr($anrId, $objectId, $parentId, $position, $impacts) {

        //retrieve object properties
        $object = $this->get('objectTable')->getEntity($objectId);

        if ((is_null($object->anr)) || ($object->anr->id != $anrId)) {
            throw new \Exception('Object is not an object of this anr', 412);
        }

        $data = [
            'object' => $objectId,
            'parent' => ($parentId) ? $parentId : null,
            'position' => $position,
            'anr' => $anrId,
        ];
        $commonProperties = ['name1', 'name2', 'name3', 'name4', 'label1', 'label2', 'label3', 'label4'];
        foreach($commonProperties as $commonProperty) {
            $data[$commonProperty] = $object->$commonProperty;
        }

        //set impacts
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $parent = ($parentId) ? $table->getEntity($parentId) : null;
        $this->updateImpacts($anrId, $impacts, $parent, $data);
        
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
        $parent = ($parentId) ? $table->getEntity($parentId) : null;
        $instance->setParent($parent);
        $root = ($parentId) ? $this->getRoot($instance) : null;
        $instance->setRoot($root);

        //retrieve children
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($objectId);

        //level
        $this->updateLevels($parent, $children, $instance);

        $id = $table->createInstanceToAnr($instance, $anrId, $parentId, $position);

        //instances risk
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRiskService->createInstanceRisks($id, $anrId, $objectId);

        //instances risks op
        /** @var InstanceRiskOpService $instanceRiskOpService */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        $instanceRiskOpService->createInstanceRisksOp($id, $anrId, $object);

        //instances consequences
        /** @var InstanceConsequenceService $instanceConsequenceService */
        $instanceConsequenceService = $this->get('instanceConsequenceService');
        $instanceConsequenceService->createInstanceConsequences($id, $anrId, $object);

        //children
        foreach($children as $child) {
            $impacts = [
                'c' => '-1',
                'i' => '-1',
                'd' => '-1'
            ];
            $this->instantiateObjectToAnr($anrId, $child->child->id, $id, $child->position, $impacts);
        }

        return $id;
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
                'c_risk' => $this->getRiskC($instance['c'], $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate),
                'c_risk_enabled' => $amv->threat->c,
                'i_impact' => $instance['i'],
                'i_risk' => $this->getRiskC($instance['i'], $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate),
                'i_risk_enabled' => $amv->threat->i,
                'd_impact' => $instance['d'],
                'd_risk' => $this->getRiskC($instance['d'], $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate),
                'd_risk_enabled' => $amv->threat->d,
                't' => ($instanceRisk->kindOfMeasure == InstanceRisk::KIND_NOT_TREATED) ? false : true,
                'target_risk' => $this->getTargetRisk($instance['c'], $instance['i'], $instance['d'], $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate, $instanceRisk->reductionAmount),
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

        /** @var InstanceConsequenceService $instanceConsequenceService */
        $instanceConsequenceService = $this->get('instanceConsequenceService');
        $instanceConsequences = $instanceConsequenceService->getInstanceConsequences($instanceId, $anrId);

        $consequences = [];
        foreach ($instanceConsequences as $instanceConsequence) {
            /** @var ScaleTypeTable $scaleImpactTypeTable */
            $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
            $scaleImpactType = $scaleImpactTypeTable->getEntity($instanceConsequence->scaleImpactType->id);

            $consequences[] = [
                'id' => $instanceConsequence->id,
                'scaleImpactTypeDescription1' => $scaleImpactType->label1,
                'scaleImpactTypeDescription2' => $scaleImpactType->label2,
                'scaleImpactTypeDescription3' => $scaleImpactType->label3,
                'scaleImpactTypeDescription4' => $scaleImpactType->label4,
                'c_risk' => $instanceConsequence->c,
                'i_risk' => $instanceConsequence->i,
                'd_risk' => $instanceConsequence->d,
            ];
        }

        return $consequences;

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

        $entity = $table->getEntity($id);

        if (!$entity) {
            throw new \Exception('Instance not exist', 412);
        }

        $entity->setDbAdapter($table->getDb());
        $entity->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        //impacts
        $impacts = [
            'c' => $data['c'],
            'i' => $data['i'],
            'd' => $data['d'],
        ];
        $this->updateImpacts($anrId, $impacts, $entity->parent, $data);

        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        //retrieve children
        $children = $table->getEntityByFields(['parent' => $id]);

        $id = $this->get('table')->save($entity);

        foreach($children as $child) {
            $fields = [
                'id', 'asset', 'object',
                'name1', 'name2', 'name3', 'name4',
                'label1', 'label2', 'label3', 'label4',
                'c', 'i', 'd', 'ch', 'ih', 'dh'
            ];

            $child = $this->get('table')->get($child->id, $fields);

            foreach ($this->dependencies as $dependency){
                $child[$dependency] = $child[$dependency]->id;
            }

            if ($child['ch']) {
                $child['c'] = -1;
            }
            if ($child['ih']) {
                $child['i'] = -1;
            }
            if ($child['dh']) {
                $child['d'] = -1;
            }

            $this->updateInstance($anrId, $child['id'], $child, $historic);
        }

        //if source object is global, reverberate to other instance with the same source object
        if ($entity->object->scope == Object::SCOPE_GLOBAL) {
            //retrieve instance with same object source
            $brothers = $table->getEntityByFields(['object' => $entity->object->id]);
            foreach($brothers as $brother) {
                if (($brother->id != $id) && (!in_array($brother->id, $historic))) {
                    $this->updateInstance($anrId, $brother->id, $data, $historic);
                }
            }
        }

        return $id;
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
     * Update impacts
     *
     * @param $anrId
     * @param $newImpacts
     * @param $parent
     * @param $data
     * @throws \Exception
     */
    public function updateImpacts($anrId, $newImpacts, $parent, &$data) {
        /** @var ScaleTable $scaleTable */
        $scaleTable = $this->get('scaleTable');
        $scale = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => Scale::TYPE_IMPACT])[0];
        foreach($newImpacts as $key => $impact) {
            $data[$key] = $impact;
            $data[$key . 'h'] = ($impact < 0) ? true : false;

            if ($impact < 0) { //retrieve parent value
                if ($parent) {
                    $data[$key] = $parent->$key;
                } else {
                    $data[$key] = -1;
                }
            } else { //verify min and max
                if (($impact < $scale->min) || ($impact > $scale->max)) {
                    throw new \Exception('Impact must be between ' . $scale->min . ' and ' . $scale->max , 412);
                }
            }
        }
    }

    /**
     * Update level
     *
     * @param $parent
     * @param $children
     * @param $instance
     */
    public function updateLevels($parent, $children, &$instance) {
        if (!$parent) {
            $instance->setLevel(Instance::LEVEL_ROOT);
        } else if (!count($children)) {
            $instance->setLevel(Instance::LEVEL_LEAF);
        } else {
            $instance->setLevel(Instance::LEVEL_INTER);
        }
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data){

        /** @var InstanceTable $table */
        $table = $this->get('table');

        $instance = $table->getEntity($id);
        $instance->setLanguage($this->getLanguage());
        $instance->exchangeArray($data, true);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        if (array_key_exists('parent', $data)) {
            $parent = ($data['parent']) ? $table->getEntity($data['parent']) : null;
            $instance->setParent($parent);

            $root = ($data['parent']) ? $this->getRoot($instance) : null;
            $instance->setRoot($root);

            $table->save($instance);

            $this->changeRootForChildren($id, $root);
        }


    }

    /**
     * Change root for children
     *
     * @param $instanceId
     * @param $root
     */
    public function changeRootForChildren($instanceId, $root) {

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $children = $table->getEntityByFields(['parent' => $instanceId]);

        foreach($children as $child) {

            $child->setRoot($root);

            $table->save($child);

            $this->changeRootForChildren($child->id, $root);
        }
    }
}