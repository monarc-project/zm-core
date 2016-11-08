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
    protected $objectExportService;
    protected $amvService;

    /**
     * Instantiate Object To Anr
     *
     * @param $anrId
     * @param $data
     * @return mixed|null
     * @throws \Exception
     */
    public function instantiateObjectToAnr($anrId, $data, $managePosition = true, $rootLevel = false) {

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

        $this->updateImpactsInherited($anrId, $parent, $data);

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
        $this->updateInstanceLevels($rootLevel, $data['object'], $instance);

        //manage position
        if ($managePosition) {
            if (isset($data['implicitPosition'])) {
                $previousInstance = (isset($data['previous'])) ? $data['previous'] : null;

                $this->managePosition('parent', $instance, $parentId, $data['implicitPosition'], $previousInstance, 'post');
            }
            else {
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
    public function updateInstance($anrId, $id, $data, &$historic = [], $managePosition = false){

        $historic[] = $id;
        $initialData = $data;

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->getEntity($id);

        if (!$instance) {
            throw new \Exception('Instance not exist', 412);
        }

        $instance->setDbAdapter($table->getDb());
        $instance->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        if ($managePosition) {
            $this->updatePosition($anrId, $instance, $data);
        }

        $this->updateConsequences($anrId, $data);

        $this->filterPostFields($data, $instance, $this->forbiddenFields + ['c', 'i', 'd']);
        $instance->exchangeArray($data);

        $this->setDependencies($instance, $this->dependencies);

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

        $this->updateBrothers($anrId, $instance, $initialData, $historic);

        if (count($historic) == 1) {
            $this->objectImpacts($instance);
        }

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
    public function patchInstance($anrId, $id, $data, $historic = [], $modifyCid = false){

        //security
        $this->filterPatchFields($data);

        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->getEntity($id);

        if (!$instance) {
            throw new \Exception('Instance not exist', 412);
        }
        $instanceParent = ($instance->parent) ? $instance->parent->id : null;

        $this->patchPosition($anrId, $instance, $instanceParent, $data);

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
        
        if (isset($data['c']) && $data['c'] != -1) {
            $data['ch'] = 0;
        }
        if (isset($data['d']) && $data['d'] != -1) {
            $data['dh'] = 0;
        }
        if (isset($data['i']) && $data['i'] != -1) {
            $data['ih'] = 0;
        }

        $instance->setLanguage($this->getLanguage());
        $instance->exchangeArray($data, true);

        $this->setDependencies($instance, $this->dependencies);

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

        if ($instance->level != Instance::LEVEL_ROOT) {
            throw new \Exception('This is not a root instance', 412);
        }

        $parent_id = null;

        if ($instance->parent != null && $instance->parent->id) {
            $parent_id = $instance->parent->id;
        }

        $this->managePosition('parent', $instance, $parent_id, null, null, 'delete');

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
     * @param $rootLevel
     * @param $objectId
     * @param $instance
     */
    protected function updateInstanceLevels($rootLevel, $objectId, &$instance) {

        if ($rootLevel) {
            $instance->setLevel(Instance::LEVEL_ROOT);
        } else {
            //retrieve children
            /** @var ObjectObjectService $objectObjectService */
            $objectObjectService = $this->get('objectObjectService');
            $children = $objectObjectService->getChildren($objectId);

            if (!count($children)) {
                $instance->setLevel(Instance::LEVEL_LEAF);
            } else {
                $instance->setLevel(Instance::LEVEL_INTER);
            }
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
    protected function updateImpactsInherited($anrId, $parent, &$data) {

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
    protected function updateBrothers($anrId, $instance, $data, &$historic) {
        $fieldsToDelete = ['parent', 'createdAt', 'creator', 'risks', 'oprisks', 'instances'];
        //if source object is global, reverberate to other instance with the same source object
        if ($instance->object->scope == Object::SCOPE_GLOBAL) {
            //retrieve instance with same object source
            /** @var InstanceTable $table */
            $table = $this->get('table');
            $brothers = $table->getEntityByFields(['object' => $instance->object->id]);
            foreach ($brothers as $brother) {
                if (($brother->id != $instance->id) && (!in_array($brother->id, $historic))) {
                    foreach($fieldsToDelete as $fieldToDelete) {
                        if (isset($data[$fieldToDelete])) {
                            unset($data[$fieldToDelete]);
                        }
                    }
                    $data['id'] = $brother->id;
                    $data['c'] = $brother->c;
                    $data['i'] = $brother->i;
                    $data['d'] = $brother->d;

                    if (isset($data['consequences'])) {

                        //retrieve instance consequence id for the brother isnatnce id ans scale impact type
                        /** @var InstanceConsequenceTable $instanceConsequenceTable */
                        $instanceConsequenceTable = $this->get('instanceConsequenceTable');
                        $instanceConsequences = $instanceConsequenceTable->getEntityByFields(['instance' => $brother->id]);
                        foreach($instanceConsequences as $instanceConsequence) {
                            foreach($data['consequences'] as $key => $dataConsequence) {
                                if ($dataConsequence['scaleImpactType'] == $instanceConsequence->scaleImpactType->type) {
                                    $data['consequences'][$key]['id'] = $instanceConsequence->id;
                                }
                            }
                        }
                    }

                    $this->updateInstance($anrId, $brother->id, $data, $historic, false);
                }
            }
        }
    }

    /**
     * Update Consequences
     *
     * @param $anrId
     * @param $data
     */
    public function updateConsequences($anrId, $data) {
        if (isset($data['consequences'])) {
            $i = 1;
            foreach($data['consequences'] as $consequence) {
                $patchInstance = ($i == count($data['consequences'])) ? true : false;

                $dataConsequences = [
                    'anr' => $anrId,
                    'c' => intval($consequence['c_risk']),
                    'i' => intval($consequence['i_risk']),
                    'd' => intval($consequence['d_risk']),
                    'isHidden' => intval($consequence['isHidden']),
                ];

                /** @var InstanceConsequenceService $instanceConsequenceService */
                $instanceConsequenceService = $this->get('instanceConsequenceService');
                $instanceConsequenceService->patchConsequence($consequence['id'], $dataConsequences, $patchInstance);

                $i++;
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
     * Update Position
     *
     * @param $anrId
     * @param $instance
     * @param $data
     */
    public function updatePosition($anrId, $instance, $data) {
        if (isset($data['position'])) {
            if ((isset($data['position']) && ($data['position'] != $instance->position)) || (isset($data['parent']) && ($data['parent'] != $instance->parent))) {

                $parent = (isset($data['parent']) && $data['parent']) ? $data['parent'] : null;
                $parentId = ($parent) ? $parent['id'] : null;

                if ($data['position']) {
                    $previousInstancePosition = ($data['position'] > $instance->position) ? $data['position'] : $data['position'] - 1;
                    $fields = [
                        'anr' => $anrId,
                        'position' => $previousInstancePosition,
                        'parent' => $parentId
                    ];

                    /** @var InstanceTable $table */
                    $table = $this->get('table');
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
    }

    /**
     * Patch Position
     *
     * @param $anrId
     * @param $instance
     * @param $instanceParent
     * @param $data
     */
    public function patchPosition($anrId, $instance, $instanceParent, $data) {
        if (isset($data['position'])) {
            if (($data['position'] != $instance->position) || ($data['parent'] != $instanceParent)) {

                if ($instance->level != Instance::LEVEL_ROOT) {
                    throw new \Exception('You may only move a root-level instance', 412);
                }

                $parent = (isset($data['parent']) && $data['parent']) ? $data['parent'] : null;

                if ($data['position']) {
                    if (($data['parent'] == $instanceParent) && ($data['position'] > $instance->position)) {
                        $previousInstancePosition = $data['position'];
                    } else {
                        $previousInstancePosition = $data['position'] - 1;
                    }
                    $fields = ['anr' => $anrId, 'position' => $previousInstancePosition, 'parent' => ($parent) ? $parent : 'null'];
                    $previous = $this->get('table')->getEntityByFields($fields);
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
        $instance['instances'] = $this->getOtherInstances($instance);

        return $instance;
    }

    /**
     * Get Similar Assets to ANR
     *
     * @param $instance
     * @return array
     */
    public function getOtherInstances($instance){
        $instances = array();
        $result = $this->get('table')->getRepository()
            ->createQueryBuilder('t')
            ->where("t.anr = ?1")
            ->andWhere("t.object = ?2")
            ->setParameter(1,$instance['anr']->id)
            ->setParameter(2,$instance['object']->id)
            ->getQuery()->getResult();
        $anr = $instance['anr']->getJsonArray();

        foreach($result as $r){
            $asc = $this->get('table')->getAscendance($r);
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

        // Order by AMV link position, then max risk
        /** @var AmvTable $amvTable */
        $amvTable = $this->get('amvTable');
        $amvs = [];

        // Cache the AMVs data
        foreach ($instancesRisks as $ir) {
            if (!isset($amvs[$ir->amv->id])) {
                $amv = $amvTable->getEntity($ir->amv->id);
                $amvs[$ir->amv->id] = $amv;
            }
        }

        // Sort by AMV position, then max cached risk
        usort($instancesRisks, function ($a, $b) use ($amvs) {
            $amv_a = $amvs[$a->amv->id];
            $amv_b = $amvs[$b->amv->id];

            if ($amv_a->position == $amv_b->position) {
                return $a->cacheMaxRisk - $b->cacheMaxRisk;
            } else {
                return $amv_a->position - $amv_b->position;
            }
        });

        $risks = [];
        foreach ($instancesRisks as $instanceRisk) {
            $amv = $amvs[$instanceRisk->amv->id];

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
                'assetLabel1' => $amv->asset->label1,
                'assetLabel2' => $amv->asset->label2,
                'assetLabel3' => $amv->asset->label3,
                'assetLabel4' => $amv->asset->label4,
                'assetDescription1' => $amv->asset->description1,
                'assetDescription2' => $amv->asset->description2,
                'assetDescription3' => $amv->asset->description3,
                'assetDescription4' => $amv->asset->description4,
                'threat' => $amv->threat->id,
                'threatLabel1' => $amv->threat->label1,
                'threatLabel2' => $amv->threat->label2,
                'threatLabel3' => $amv->threat->label3,
                'threatLabel4' => $amv->threat->label4,
                'threatDescription1' => $amv->threat->description1,
                'threatDescription2' => $amv->threat->description2,
                'threatDescription3' => $amv->threat->description3,
                'threatDescription4' => $amv->threat->description4,
                'threatRate' => $instanceRisk->threatRate,
                'vulnerability' => $amv->vulnerability->id,
                'vulnLabel1' => $amv->vulnerability->label1,
                'vulnLabel2' => $amv->vulnerability->label2,
                'vulnLabel3' => $amv->vulnerability->label3,
                'vulnLabel4' => $amv->vulnerability->label4,
                'vulnDescription1' => $amv->vulnerability->description1,
                'vulnDescription2' => $amv->vulnerability->description2,
                'vulnDescription3' => $amv->vulnerability->description3,
                'vulnDescription4' => $amv->vulnerability->description4,
                'vulnerabilityRate' => $instanceRisk->vulnerabilityRate,
                'kindOfMeasure' => $instanceRisk->kindOfMeasure,
                'reductionAmount' => $instanceRisk->reductionAmount,
                'c_impact' => ($instanceRisk->instance) ? $instanceRisk->instance->c : null,
                'c_risk' => $instanceRisk->riskC,
                'c_risk_enabled' => $amv->threat->c,
                'i_impact' => ($instanceRisk->instance) ? $instanceRisk->instance->i : null,
                'i_risk' => $instanceRisk->riskI,
                'i_risk_enabled' => $amv->threat->i,
                'd_impact' => ($instanceRisk->instance) ? $instanceRisk->instance->d : null,
                'd_risk' => $instanceRisk->riskD,
                'd_risk_enabled' => $amv->threat->d,
                't' => ((!$instanceRisk->kindOfMeasure) || ($instanceRisk->kindOfMeasure == InstanceRisk::KIND_NOT_TREATED)) ? false : true,
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
            $riskOps[] = [
                'id' => $instanceRiskOp->id,
                'label1' => $instanceRiskOp->riskCacheLabel1,
                'label2' => $instanceRiskOp->riskCacheLabel2,
                'label3' => $instanceRiskOp->riskCacheLabel3,
                'label4' => $instanceRiskOp->riskCacheLabel4,

                'description1' => $instanceRiskOp->riskCacheDescription1,
                'description2' => $instanceRiskOp->riskCacheDescription2,
                'description3' => $instanceRiskOp->riskCacheDescription3,
                'description4' => $instanceRiskOp->riskCacheDescription4,

                'netProb' => $instanceRiskOp->netProb,
                'netR' => $instanceRiskOp->netR,
                'netO' => $instanceRiskOp->netO,
                'netL' => $instanceRiskOp->netL,
                'netF' => $instanceRiskOp->netF,
                'netP' => $instanceRiskOp->netP,
                'cacheNetRisk' => $instanceRiskOp->cacheNetRisk,

                'brutProb' => $instanceRiskOp->brutProb,
                'brutR' => $instanceRiskOp->brutR,
                'brutO' => $instanceRiskOp->brutO,
                'brutL' => $instanceRiskOp->brutL,
                'brutF' => $instanceRiskOp->brutF,
                'brutP' => $instanceRiskOp->brutP,
                'cacheBrutRisk' => $instanceRiskOp->cacheBrutRisk,

                'kindOfMeasure' => $instanceRiskOp->kindOfMeasure,
                'comment' => $instanceRiskOp->comment,
                't' => (($instanceRiskOp->kindOfMeasure == InstanceRiskOp::KIND_NOT_TREATED) || (!$instanceRiskOp->kindOfMeasure)) ? false : true,

                'targetedProb' => $instanceRiskOp->targetedProb,
                'targetedR' => $instanceRiskOp->targetedR,
                'targetedO' => $instanceRiskOp->targetedO,
                'targetedL' => $instanceRiskOp->targetedL,
                'targetedF' => $instanceRiskOp->targetedF,
                'targetedP' => $instanceRiskOp->targetedP,
                'cacheTargetedRisk' => $instanceRiskOp->cacheTargetedRisk,
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

            if (!$scaleImpactType->isHidden || $instanceConsequence->locallyTouched) {
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
                    'isHidden' => $instanceConsequence->isHidden,
                    'locallyTouched' => $instanceConsequence->locallyTouched,
                ];
            }
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
        //$scalesImpactTypes = $scaleImpactTypeTable->getEntityByFields(['anr' => $anrId, 'isHidden' => 0]);
        $scalesImpactTypes = $scaleImpactTypeTable->getEntityByFields(['anr' => $anrId]);

        /** @var InstanceConsequenceTable $instanceConsequenceTable */
        $instanceConsequenceTable = $this->get('instanceConsequenceTable');

        $nbConsequences = count($scalesImpactTypes);
        $i = 1;
        foreach($scalesImpactTypes as $scalesImpactType) {

            $lastConsequence = ($nbConsequences == $i) ? true : false;

            $data = [
                'anr' => $this->get('anrTable')->getEntity($anrId),
                'instance' => $this->get('instanceTable')->getEntity($instanceId),
                'object' => $object,
                'scaleImpactType' => $scalesImpactType,
                'isHidden' => $scalesImpactType->isHidden,
            ];

            $class = $this->get('instanceConsequenceEntity');
            $instanceConsequenceEntity = new $class();

            $instanceConsequenceEntity->exchangeArray($data);

            $instanceConsequenceTable->save($instanceConsequenceEntity,  $lastConsequence);

            $i++;
        }
    }

    public function export(&$data) {
        if (empty($data['id'])) {
            throw new \Exception('Instance to export is required',412);
        }
        if (empty($data['password'])) {
            $data['password'] = '';
        }

        $filename = "";
        $return = $this->generateExportArray($data['id'],$filename);
        $data['filename'] = $filename;

        return base64_encode($this->encrypt(json_encode($return),$data['password']));
    }

    public function generateExportArray($id, &$filename = "", $with_eval = false, &$with_scale = true){
        if (empty($id)) {
            throw new \Exception('Instance to export is required',412);
        }
        $entity = $this->get('table')->getEntity($id);

        if (!$entity) {
            throw new \Exception('Entity `id` not found.');
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('label'.$this->getLanguage()));

        $objInstance = array(
            'id' => 'id',
            'name1' => 'name1',
            'name2' => 'name2',
            'name3' => 'name3',
            'name4' => 'name4',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'disponibility' => 'disponibility',
            'level' => 'level',
            'assetType' => 'assetType',
            'exportable' => 'exportable',
            'position' => 'position',
            'c' => 'c',
            'i' => 'i',
            'd' => 'd',
            'ch' => 'ch',
            'ih' => 'ih',
            'dh' => 'dh',
        );

        $return = array(
            'type' => 'instance',
            'version' => $this->getVersion(),
            'with_eval' => $with_eval,
            'instance' => $entity->getJsonArray($objInstance),
            'object' => $this->get('objectExportService')->generateExportArray($entity->get('object')->get('id')),
            // 'asset' => $this->get('assetService')->generateExportArray($entity->get('asset')->get('id')), // l'asset sera porté par l'objet
        );
        $return['instance']['asset'] = $entity->get('asset')->get('id');
        $return['instance']['object'] = $entity->get('object')->get('id');
        $return['instance']['root'] = 0;
        $return['instance']['parent'] = $entity->get('parent')?$entity->get('parent')->get('id'):0;

        // Scales
        if($with_eval && $with_scale){
            $with_scale = false;
            $return['scales'] = array();
            $scaleTable = $this->get('scaleTable');
            $scales = $scaleTable->getEntityByFields(['anr' => $entity->get('anr')->get('id')]);
            $scalesArray = array(
                'min'=>'min',
                'max'=>'max',
                'type'=>'type',
            );
            foreach ($scales as $s) {
                $return['scales'][$s->type] = $s->getJsonArray($scalesArray);
            }
        }

        // Instance risk
        $return['risks'] = array();
        $instanceRiskTable = $this->get('instanceRiskService')->get('table');
        $instanceRiskResults = $instanceRiskTable->getRepository()
            ->createQueryBuilder('t')
            ->where("t.instance = :i")
            ->setParameter(':i',$entity->get('id'));
        $instanceRiskArray = array(
            'id' => 'id',
            'specific' => 'specific',
            'mh' => 'mh',
            'threatRate' => 'threatRate',
            'vulnerabilityRate' => 'vulnerabilityRate',
            'kindOfMeasure' => 'kindOfMeasure',
            'reductionAmount' => 'reductionAmount',
            'comment' => 'comment',
            'commentAfter' => 'commentAfter',
            'riskC' => 'riskC',
            'riskI' => 'riskI',
            'riskD' => 'riskD',
            'cacheMaxRisk' => 'cacheMaxRisk',
            'cacheTargetedRisk' => 'cacheTargetedRisk',
        );

        $treatsObj = array(
            'id' => 'id',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'c' => 'c',
            'i' => 'i',
            'd' => 'd',
            'status' => 'status',
            'isAccidental' => 'isAccidental',
            'isDeliberate' => 'isDeliberate',
            'descAccidental1' => 'descAccidental1',
            'descAccidental2' => 'descAccidental2',
            'descAccidental3' => 'descAccidental3',
            'descAccidental4' => 'descAccidental4',
            'exAccidental1' => 'exAccidental1',
            'exAccidental2' => 'exAccidental2',
            'exAccidental3' => 'exAccidental3',
            'exAccidental4' => 'exAccidental4',
            'descDeliberate1' => 'descDeliberate1',
            'descDeliberate2' => 'descDeliberate2',
            'descDeliberate3' => 'descDeliberate3',
            'descDeliberate4' => 'descDeliberate4',
            'exDeliberate1' => 'exDeliberate1',
            'exDeliberate2' => 'exDeliberate2',
            'exDeliberate3' => 'exDeliberate3',
            'exDeliberate4' => 'exDeliberate4',
            'typeConsequences1' => 'typeConsequences1',
            'typeConsequences2' => 'typeConsequences2',
            'typeConsequences3' => 'typeConsequences3',
            'typeConsequences4' => 'typeConsequences4',
            'trend' => 'trend',
            'comment' => 'comment',
            'qualification' => 'qualification',
        );
        $vulsObj = array(
            'id' => 'id',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
        );
        //TODO : traiter amv & asset & threat & vuln
        foreach($instanceRiskResults as $ir){
            if(!$with_eval){
                $ir->set('vulnerabilityRate', '-1');
                $ir->set('threatRate', '-1');
                $ir->set('kindOfMeasure', 0);
                $ir->set('reductionAmount', 0);
                $ir->set('comment', '');
                $ir->set('commentAfter', '');
            }

            $ir->set('mh',1);
            $ir->set('riskC','-1');
            $ir->set('riskI','-1');
            $ir->set('riskD','-1');
            $return['risks'][$ir->get('id')] = $ir->getJsonArray($instanceRiskArray);

            $return['risks'][$ir->get('id')]['amv'] = $ir->get('amv')->get('id');
            if(empty($return['amvs'][$ir->get('amv')->get('id')])){
                list(
                    $amv,
                    $threats,
                    $vulns,
                    $themes) = $this->get('amvService')->generateExportArray($ir->get('amv'));
                $return['amvs'][$ir->get('amv')->get('id')] = $amv;
                if(empty($return['threats'])){
                    $return['threats'] = $threats;
                }else{
                    $return['threats'] += $threats;
                }
                if(empty($return['vuls'])){
                    $return['vuls'] = $vulns;
                }else{
                    $return['vuls'] += $vulns;
                }
            }

            $threat = $ir->get('threat');
            if(!empty($threat)){
                if(empty($return['threats'][$$ir->get('threat')->get('id')])){
                    $return['threats'][$ir->get('threat')->get('id')] = $ir->get('threat')->getJsonArray($treatsObj);
                }
                $return['risks'][$ir->get('id')]['threat'] = $ir->get('threat')->get('id');
            }else{
                $return['risks'][$ir->get('id')]['threat'] = null;
            }
            
            $vulnerability = $ir->get('vulnerability');
            if(!empty($vulnerability)){
                if(empty($return['threats'][$$ir->get('threat')->get('id')])){
                    $return['vuls'][$ir->get('vulnerability')->get('id')] = $ir->get('vulnerability')->getJsonArray($vulnerability);
                }
                $return['risks'][$ir->get('id')]['vulnerability'] = $ir->get('vulnerability')->get('id');
            }else{
                $return['risks'][$ir->get('id')]['vulnerability'] = null;
            }
        }

        // Instance risk op
        $return['risksop'] = array();
        $instanceRiskOpTable = $this->get('instanceRiskOpService')->get('table');
        $instanceRiskOpResults = $instanceRiskOpTable->getRepository()
            ->createQueryBuilder('t')
            ->where("t.instance = :i")
            ->setParameter(':i',$entity->get('id'));
        $instanceRiskOpArray = array(
            'id' => 'id',
            //'rolfRisk' => 'rolfRisk', // TODO doit-on garder cette donnée ?
            'riskCacheLabel1' => 'riskCacheLabel1',
            'riskCacheLabel2' => 'riskCacheLabel2',
            'riskCacheLabel3' => 'riskCacheLabel3',
            'riskCacheLabel4' => 'riskCacheLabel4',
            'riskCacheDescription1' => 'riskCacheDescription1',
            'riskCacheDescription2' => 'riskCacheDescription2',
            'riskCacheDescription3' => 'riskCacheDescription3',
            'riskCacheDescription4' => 'riskCacheDescription4',
            'brutProb' => 'brutProb',
            'brutR' => 'brutR',
            'brutO' => 'brutO',
            'brutL' => 'brutL',
            'brutF' => 'brutF',
            'netProb' => 'netProb',
            'netR' => 'netR',
            'netO' => 'netO',
            'netL' => 'netL',
            'netF' => 'netF',
            'targetedProb' => 'targetedProb',
            'targetedR' => 'targetedR',
            'targetedO' => 'targetedO',
            'targetedL' => 'targetedL',
            'targetedF' => 'targetedF',
            'cacheTargetedRisk' => 'cacheTargetedRisk',
            'cacheNetRisk' => 'cacheNetRisk',
            'cacheBrutRisk' => 'cacheBrutRisk',
            'kindOfMeasure' => 'kindOfMeasure',
            'comment' => 'comment',
            'mitigation' => 'mitigation',
            'specific' => 'specific',
            'netP' => 'netP',
            'targetedP' => 'targetedP',
            'brutP' => 'brutP',
        );
        $toReset = array(
            'brutProb' => 'brutProb',
            'brutR' => 'brutR',
            'brutO' => 'brutO',
            'brutL' => 'brutL',
            'brutF' => 'brutF',
            'netProb' => 'netProb',
            'netR' => 'netR',
            'netO' => 'netO',
            'netL' => 'netL',
            'netF' => 'netF',
            'targetedProb' => 'targetedProb',
            'targetedR' => 'targetedR',
            'targetedO' => 'targetedO',
            'targetedL' => 'targetedL',
            'targetedF' => 'targetedF',
            'cacheTargetedRisk' => 'cacheTargetedRisk',
            'cacheNetRisk' => 'cacheNetRisk',
            'cacheBrutRisk' => 'cacheBrutRisk',
        );
        foreach ($instanceRiskOpResults as $iro) {
            if(!$with_eval){
                foreach($toReset as $r){
                    $iro->set($r,'-1');
                }
                $iro->set('kindOfMeasure',0);
                $iro->set('comment','');
                $iro->set('mitigation','');
            }
            $return['risksop'][$iro->get('id')] = $iro->getJsonArray($instanceRiskOpArray);
        }

        // Instance consequence
        if($with_eval){
            $instanceConseqArray = array(
                'id' => 'id',
                'isHidden' => 'isHidden',
                'locallyTouched' => 'locallyTouched',
                'c' => 'c',
                'i' => 'i',
                'd' => 'd',
            );
            $scaleTypeArray = array(
                'id' => 'id',
                'label1' => 'label1',
                'label2' => 'label2',
                'label3' => 'label3',
                'label4' => 'label4',
                'isSys' => 'isSys',
                'isHidden' => 'isHidden',
                'position' => 'position',
            );
            $return['consequences'] = array();
            $instanceConseqTable = $this->get('instanceConsequenceService')->get('table');
            $instanceConseqResults = $instanceConseqTable->getRepository()
                ->createQueryBuilder('t')
                ->where("t.instance = :i")
                ->setParameter(':i',$entity->get('id'));
            foreach($instanceConseqResults as $ic){
                $return['consequences'][$ic->get('id')] = $ic->getJsonArray($instanceConseqArray);
                $return['consequences'][$ic->get('id')]['scaleImpactType'] = $ic->get('scaleImpactType')->getJsonArray($scaleTypeArray);
                $return['consequences'][$ic->get('id')]['scaleImpactType']['scale'] = $ic->get('scaleImpactType')->get('scale')->get('id');
            }
        }

        // TODO: gérer les fils
        $instanceTableResults = $this->get('table')->getRepository()
            ->createQueryBuilder('t')
            ->where('t.parent = :p')
            ->setParameter(':i',$entity->get('id'));
        $return['children'] = array();
        $f = '';
        foreach($instanceTableResults as $i){
            $return['children'][$i->get('id')] = $this->generateExportArray($i->get('id'),$f,$with_eval, $with_scale);
        }
        return $return;
    }
}
