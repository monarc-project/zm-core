<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Entity\Instance;
use MonarcCore\Model\Entity\InstanceRisk;
use MonarcCore\Model\Entity\InstanceRiskOp;
use MonarcCore\Model\Entity\Object;
use MonarcCore\Model\Table\InstanceConsequenceTable;
use MonarcCore\Model\Table\InstanceTable;
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
    protected $dependencies = ['anr', 'asset', 'object', '[parent](instance)', '[root](instance)'];
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];

    // Tables & Entities
    protected $anrTable;
    protected $amvTable;
    protected $objectTable;
    protected $scaleTable;
    protected $scaleImpactTypeTable;
    protected $instanceConsequenceTable;
    protected $instanceConsequenceEntity;
    protected $recommandationRiskTable; // Used for FO
    protected $recommandationMeasureTable; // Used for FO
    protected $recommandationTable; // Used for FO

    // Services
    protected $instanceConsequenceService;
    protected $instanceRiskService;
    protected $instanceRiskOpService;
    protected $objectObjectService;
    protected $translateService;

    // Useless (Deprecated)
    protected $instanceTable;
    protected $assetTable;
    protected $rolfRiskTable;

    // Export (Services)
    protected $objectExportService;
    protected $amvService;

    protected $forbiddenFields = ['anr', 'asset', 'object', 'ch', 'dh', 'ih'];

    /**
     * Instantiate Object To Anr
     *
     * @param $anrId
     * @param $data
     * @return mixed|null
     * @throws \Exception
     */
    public function instantiateObjectToAnr($anrId, $data, $managePosition = true, $rootLevel = false, $mode = Instance::MODE_CREA_NODE)
    {
        //retrieve object properties
        $object = $this->get('objectTable')->getEntity($data['object']);

        //verify if user is authorized to instantiate this object
        $authorized = false;
        foreach ($object->anrs as $anr) {
            if ($anr->id == $anrId) {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            throw new \Exception('Object is not an object of this anr', 412);
        }

        $data['anr'] = $anrId;

        $commonProperties = ['name1', 'name2', 'name3', 'name4', 'label1', 'label2', 'label3', 'label4'];
        foreach ($commonProperties as $commonProperty) {
            $data[$commonProperty] = $object->$commonProperty;
        }

        if (isset($data['parent']) && empty($data['parent'])) {
            $data['parent'] = null;
        } elseif (!empty($data['parent'])) {
            $parent = $this->get('table')->getEntity($data['parent']);
            if (!$parent) {
                $data['parent'] = null;
                unset($parent);
            }
        }

        //set impacts
        /** @var InstanceTable $table */
        $parent = ($data['parent']) ? $this->get('table')->getEntity($data['parent']) : null;

        $this->updateImpactsInherited($anrId, $parent, $data);

        //asset
        if (isset($object->asset)) {
            $data['asset'] = $object->asset->id;
        }
        //manage position
        if (!$managePosition) {
            unset($data['implicitPosition']);
            unset($data['previous']);
        } elseif (isset($data['position'])) {
            $data['position']++;
            if ($data['position'] <= 1) {
                $data['implicitPosition'] = 1;
            } else {
                $return = $this->get('table')->getRepository()->createQueryBuilder('t')
                    ->select('COUNT(t.id)');
                if (isset($parent)) {
                    $return = $return->where('t.parent = :parent')
                        ->setParameter(':parent', $parent->get('id'));
                } else {
                    $return = $return->where('t.parent IS NULL');
                }
                if ($data['anr']) {
                    $return = $return->andWhere('t.anr = :anr')
                        ->setParameter(':anr', $data['anr']);
                } else {
                    $return = $return->andWhere('t.anr IS NULL');
                }
                $max = $return->getQuery()->getSingleScalarResult();
                if ($data['position'] == $max + 1) {
                    $data['implicitPosition'] = 2;
                } else {
                    $return = $this->get('table')->getRepository()->createQueryBuilder('t')
                        ->select('t.id');
                    if (isset($parent)) {
                        $return = $return->where('t.parent = :parent')
                            ->setParameter(':parent', $parent->get('id'));
                    } else {
                        $return = $return->where('t.parent IS NULL');
                    }
                    if ($data['anr']) {
                        $return = $return->andWhere('t.anr = :anr')
                            ->setParameter(':anr', $data['anr']);
                    } else {
                        $return = $return->andWhere('t.anr IS NULL');
                    }
                    $return = $return->andWhere('t.position = :pos')
                        ->setParameter(':pos', $data['position'] - 1)
                        ->setMaxResults(1);
                    try {
                        $max = $return->getQuery()->getSingleScalarResult();
                    } catch (\Exception $e) {
                        $max = 0; // c'est moche
                    }
                    if ($max) {
                        $data['implicitPosition'] = 3;
                        $data['previous'] = $max;
                    } else {
                        $data['implicitPosition'] = 2;
                    }
                }
            }
            unset($data['position']);
        }

        //create instance
        $instance = $this->get('entity');
        if ($instance->get('id')) {
            $c = get_class($instance);
            $instance = new $c;
            $instance->initParametersChanges();
        }
        //$instance->squeezeAutoPositionning(true);//delegate the position algorithm to the existing code
        //$instance = new $class();
        //$instance->setLanguage($this->getLanguage());
        $instance->exchangeArray($data, false);

        //instance dependencies
        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        //level
        $this->updateInstanceLevels($rootLevel, $data['object'], $instance, $mode);

        $id = $this->get('table')->save($instance);

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
     * Get Recursive Child
     *
     * @param $childList
     * @param $id
     */
    protected function getRecursiveChild(&$childList, $id)
    {
        //retrieve children
        $children = $this->get('table')->getRepository()->createQueryBuilder('t')
            ->select(['t.id'])
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        if (count($children)) {
            foreach ($children as $child) {
                $childList[] = $child['id'];
                //retrieve children of children
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
    public function updateInstance($anrId, $id, $data, &$historic = [], $managePosition = false)
    {
        $historic[] = $id;
        $initialData = $data;

        //retrieve instance
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->getEntity($id);
        if (!$instance) {
            throw new \Exception('Instance does not exist', 412);
        }
        $instance->setDbAdapter($table->getDb());
        $instance->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        if (isset($data['parent']) && empty($data['parent'])) {
            $data['parent'] = null;
        } elseif (!empty($data['parent'])) {
            $parent = $this->get('table')->getEntity(isset($data['parent']['id']) ? $data['parent']['id'] : $data['parent']);
            if (!$parent) {
                $data['parent'] = null;
                unset($parent);
            }
        }

        //manage position
        if (!$managePosition) {
            unset($data['implicitPosition']);
            unset($data['previous']);
        } elseif (isset($data['position'])) {
            $data['position']++;
            if ($data['position'] <= 1) {
                $data['implicitPosition'] = 1;
            } else {
                $return = $this->get('table')->getRepository()->createQueryBuilder('t')
                    ->select('COUNT(t.id)');
                if (isset($parent)) {
                    $return = $return->where('t.parent = :parent')
                        ->setParameter(':parent', $parent->get('id'));
                } else {
                    $return = $return->where('t.parent IS NULL');
                }
                $anr = $instance->get('anr');
                if ($anr) {
                    $return = $return->andWhere('t.anr = :anr')
                        ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
                } else {
                    $return = $return->andWhere('t.anr IS NULL');
                }
                $return = $return->getQuery()->getSingleScalarResult();
                if ($data['position'] == $return) {
                    $data['implicitPosition'] = 2;
                } else {
                    $return = $this->get('table')->getRepository()->createQueryBuilder('t')
                        ->select('t.id');
                    if (isset($parent)) {
                        $return = $return->where('t.parent = :parent')
                            ->setParameter(':parent', $parent->get('id'));
                    } else {
                        $return = $return->where('t.parent IS NULL');
                    }
                    $anr = $instance->get('anr');
                    if ($anr) {
                        $return = $return->andWhere('t.anr = :anr')
                            ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
                    } else {
                        $return = $return->andWhere('t.anr IS NULL');
                    }
                    $return = $return->andWhere('t.position = :pos')
                        ->setParameter(':pos', $data['position'] + ($data['position'] < $instance->get('position') ? -1 : 0))
                        ->setMaxResults(1)
                        ->getQuery()->getSingleScalarResult();
                    if ($return) {
                        $data['implicitPosition'] = 3;
                        $data['previous'] = $return;
                    } else {
                        $data['implicitPosition'] = 2;
                    }
                }
            }
            unset($data['position']);
        }

        $dataConsequences = (isset($data['consequences'])) ? $data['consequences'] : null;

        $this->filterPostFields($data, $instance, $this->forbiddenFields + ['c', 'i', 'd']);
        $instance->exchangeArray($data);

        $this->setDependencies($instance, $this->dependencies);

        //if ($instance->parent) {
        //    $parentId = (is_object($instance->parent)) ? $instance->parent->id : $instance->parent['id'];
        //    $instance->parent = $table->getEntity($parentId);
        //} else {
        //    $instance->parent = null;
        //}
        //if ($instance->root) {
        //    $rootId = (is_object($instance->root)) ? $instance->root->id : $instance->root['id'];
        //    $instance->root = $table->getEntity($rootId);
        //} else {
        //    $instance->root = null;
        //}

        $id = $this->get('table')->save($instance);

        if ($dataConsequences) {
            $this->updateConsequences($anrId, ['consequences' => $dataConsequences], true);
        }

        $this->updateRisks($anrId, $id);

        //if ($instance->root) {
        //    $this->updateChildrenRoot($id, $instance->root);
        //}

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
    public function patchInstance($anrId, $id, $data, $historic = [], $modifyCid = false)
    {
        //security
        if ($modifyCid) { // on provient du trigger
            $this->forbiddenFields = ['anr', 'asset', 'object'];
        }

        $this->filterPatchFields($data);

        //retrieve instance
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->getEntity($id);
        if (!$instance) {
            throw new \Exception('Instance does not exist', 412);
        }

        if (isset($data['parent']) && empty($data['parent'])) {
            $data['parent'] = null;
        } elseif (!empty($data['parent'])) {
            $parent = $this->get('table')->getEntity($data['parent']);
            if (!$parent) {
                $data['parent'] = null;
                unset($parent);
            }
        }

        //manage position
        if (isset($data['position'])) {
            $data['position']++; // TODO: to delete
            if ($data['position'] <= 1) {
                $data['implicitPosition'] = 1;
            } else {
                $return = $this->get('table')->getRepository()->createQueryBuilder('t')
                    ->select('COUNT(t.id)');
                if (isset($parent)) {
                    $return = $return->where('t.parent = :parent')
                        ->setParameter(':parent', $parent->get('id'));
                } else {
                    $return = $return->where('t.parent IS NULL');
                }
                $anr = $instance->get('anr');
                if ($anr) {
                    $return = $return->andWhere('t.anr = :anr')
                        ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
                } else {
                    $return = $return->andWhere('t.anr IS NULL');
                }
                $return = $return->getQuery()->getSingleScalarResult();
                if ($data['position'] == $return) {
                    $data['implicitPosition'] = 2;
                } else {
                    $return = $this->get('table')->getRepository()->createQueryBuilder('t')
                        ->select('t.id');
                    if (isset($parent)) {
                        $return = $return->where('t.parent = :parent')
                            ->setParameter(':parent', $parent->get('id'));
                    } else {
                        $return = $return->where('t.parent IS NULL');
                    }
                    $anr = $instance->get('anr');
                    if ($anr) {
                        $return = $return->andWhere('t.anr = :anr')
                            ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
                    } else {
                        $return = $return->andWhere('t.anr IS NULL');
                    }
                    $return = $return->andWhere('t.position = :pos')
                        ->setParameter(':pos', $data['position'] + ($data['position'] < $instance->get('position') ? -1 : 0))
                        ->setMaxResults(1)
                        ->getQuery()->getSingleScalarResult();
                    if ($return) {
                        $data['implicitPosition'] = 3;
                        $data['previous'] = $return;
                    } else {
                        $data['implicitPosition'] = 2;
                    }
                }
            }
            unset($data['position']);
        }

        if (!$modifyCid) { // on ne provient pas du trigger
            if (isset($data['c'])) {
                $data['ch'] = ($data['c'] == -1) ? 1 : 0;
            }
            if (isset($data['d'])) {
                $data['dh'] = ($data['d'] == -1) ? 1 : 0;
            }
            if (isset($data['i'])) {
                $data['ih'] = ($data['i'] == -1) ? 1 : 0;
            }
        }

        $instance->setLanguage($this->getLanguage());
        $instance->setDbAdapter($this->get('table')->getDb());
        $instance->exchangeArray($data, true);

        $this->setDependencies($instance, $this->dependencies);

        //$instance->parent = ($instance->parent) ? $table->getEntity($instance->parent) : null;

        $id = $table->save($instance);

        $parentId = ($instance->parent) ? $instance->parent->id : null;
        $this->refreshImpactsInherited($anrId, $parentId, $instance);

        $this->updateRisks($anrId, $id);

        //if ($instance->root) {
        //    $this->updateChildrenRoot($id, $instance->root);
        //}

        $this->updateChildrenImpacts($instance);

        $data['asset'] = $instance->asset->id;
        $data['object'] = $instance->object->id;
        $data['name1'] = $instance->name1;
        $data['label1'] = $instance->label1;

        unset($data['implicitPosition']);
        unset($data['previous']);
        unset($data['position']);

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
    public function delete($id)
    {
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->getEntity($id);

        //only root instance can be delete
        if ($instance->level != Instance::LEVEL_ROOT) {
            throw new \Exception('This is not a root instance', 412);
        }

        $this->get('instanceRiskService')->deleteInstanceRisks($id, $instance->anr->id);
        $this->get('instanceRiskOpService')->deleteInstanceRisksOp($id, $instance->anr->id);

        $table->delete($id);
    }

    /**
     * Object Impacts
     *
     * @param $instance
     */
    protected function objectImpacts($instance)
    {
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

        //update object by event
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
    protected function createChildren($anrId, $parentId, $object)
    {
        //retrieve object children and create instance for each child
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($object);
        foreach ($children as $child) {
            $data = [
                'object' => $child->child->id,
                'parent' => $parentId,
                'position' => $child->position,
                'c' => '-1',
                'i' => '-1',
                'd' => '-1',
            ];
            if ($object->get('anr')) {
                $data['anr'] = $object->get('anr')->get('id');
            }
            $this->instantiateObjectToAnr($anrId, $data, false);
        }
    }

    /**
     * Update Level
     *
     * @param $rootLevel
     * @param $objectId
     * @param $instance
     * @param $mode
     */
    protected function updateInstanceLevels($rootLevel, $objectId, &$instance, $mode)
    {
        if (($rootLevel) || ($mode == Instance::MODE_CREA_ROOT)) {
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
    protected function updateChildrenRoot($instanceId, $root)
    {
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $children = $table->getEntityByFields(['parent' => $instanceId]);
        foreach ($children as $child) {
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
    protected function updateImpactsInherited($anrId, $parent, &$data)
    {
        $this->verifyRates($anrId, $data);

        //for cid, if a value is received and it is different of -1, the inherited value (h) is equal to 0 else to 1
        if (isset($data['c'])) {
            $data['ch'] = ($data['c'] == -1) ? 1 : 0;
        }
        if (isset($data['i'])) {
            $data['ih'] = ($data['i'] == -1) ? 1 : 0;
        }
        if (isset($data['d'])) {
            $data['dh'] = ($data['d'] == -1) ? 1 : 0;
        }

        //for cid, if a value is received
        //if this value equal -1
        //retrieve parent value
        if (isset($data['c']) || isset($data['i']) || isset($data['d'])) {
            if (((isset($data['c'])) && ($data['c'] == -1))
                || ((isset($data['i'])) && ($data['i'] == -1))
                || ((isset($data['d'])) && ($data['d'] == -1))
            ) {
                if ($parent) {
                    if ((isset($data['c'])) && ($data['c'] == -1)) {
                        $data['c'] = (int)$parent->c;
                    }

                    if ((isset($data['i'])) && ($data['i'] == -1)) {
                        $data['i'] = (int)$parent->i;
                    }

                    if ((isset($data['d'])) && ($data['d'] == -1)) {
                        $data['d'] = (int)$parent->d;
                    }
                } else {
                    $data['c'] = -1;
                    $data['i'] = -1;
                    $data['d'] = -1;
                }
            }
        }
    }

    /**
     * Update children
     *
     * @param $instance
     */
    protected function updateChildrenImpacts($instance)
    {
        /** @var InstanceTable $table */
        $table = $this->get('table');
        if (!$instance instanceof \MonarcCore\Model\Entity\InstanceSuperClass) {
            $instance = $this->get('table')->getEntity($instance);
        }
        $children = $table->getEntityByFields(['parent' => $instance->id]);

        foreach ($children as $child) {

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

            if ($child->anr) {
                $this->updateRisks($child->anr->id, $child->id);
            }
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
    protected function updateBrothers($anrId, $instance, $data, &$historic)
    {
        $fieldsToDelete = ['parent', 'createdAt', 'creator', 'risks', 'oprisks', 'instances', 'position'];
        //if source object is global, reverberate to other instance with the same source object
        if ($instance->object->scope == Object::SCOPE_GLOBAL) {
            //retrieve instance with same object source
            /** @var InstanceTable $table */
            $table = $this->get('table');
            $brothers = $table->getEntityByFields(['object' => $instance->object->id]);
            foreach ($brothers as $brother) {
                if (($brother->id != $instance->id) && (!in_array($brother->id, $historic))) {
                    foreach ($fieldsToDelete as $fieldToDelete) {
                        if (isset($data[$fieldToDelete])) {
                            unset($data[$fieldToDelete]);
                        }
                    }
                    $data['id'] = $brother->id;
                    $data['c'] = $brother->c;
                    $data['i'] = $brother->i;
                    $data['d'] = $brother->d;

                    if (isset($data['consequences'])) {

                        //retrieve instance consequence id for the brother instance id ans scale impact type
                        /** @var InstanceConsequenceTable $instanceConsequenceTable */
                        $instanceConsequenceTable = $this->get('instanceConsequenceTable');
                        $instanceConsequences = $instanceConsequenceTable->getEntityByFields(['instance' => $brother->id]);
                        foreach ($instanceConsequences as $instanceConsequence) {
                            foreach ($data['consequences'] as $key => $dataConsequence) {
                                if ($dataConsequence['scaleImpactType'] == $instanceConsequence->scaleImpactType->type) {
                                    $data['consequences'][$key]['id'] = $instanceConsequence->id;
                                }
                            }
                        }
                    }

                    unset($data['parent']);

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
    public function updateConsequences($anrId, $data, $fromInstance = false)
    {
        if (isset($data['consequences'])) {
            $i = 1;
            foreach ($data['consequences'] as $consequence) {
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
                $instanceConsequenceService->patchConsequence($consequence['id'], $dataConsequences, $patchInstance, $fromInstance);

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
    protected function updateRisks($anrId, $instanceId)
    {
        //instances risk
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRisks = $instanceRiskService->getInstanceRisks($instanceId, $anrId);

        $nb = count($instanceRisks);
        foreach ($instanceRisks as $i => $instanceRisk) {
            $instanceRiskService->updateRisks($instanceRisk->id, $i + 1 >= $nb);
        }
    }

    /**
     * Refresh Impacts Inherited
     *
     * @param $anrId
     * @param $parentId
     * @param $instance
     */
    protected function refreshImpactsInherited($anrId, $parentId, $instance)
    {
        $parent = ($parentId > 0) ? $this->getEntityByIdAndAnr($parentId, $anrId) : null;

        //for cid, if value is inherited, retrieve value of parent
        //if there is no parent and value is inherited, value is equal to -1
        if ($instance->ch || $instance->ih || $instance->dh) {
            if ($parent) {
                if ($instance->ch) {
                    $instance->c = $parent['c'];
                }
                if ($instance->ih) {
                    $instance->i = $parent['i'];
                }
                if ($instance->dh) {
                    $instance->d = $parent['d'];
                }
            } else {
                if ($instance->ch) {
                    $instance->c = -1;
                }
                if ($instance->ih) {
                    $instance->i = -1;
                }
                if ($instance->dh) {
                    $instance->d = -1;
                }
            }

            $this->get('table')->save($instance);
        }
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntityByIdAndAnr($id, $anrId)
    {
        $instance = $this->get('table')->get($id); // pourquoi on n'a pas de contrôle sur $instance['anr']->id == $anrId ?
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
    public function getOtherInstances($instance)
    {
        $instances = [];
        $result = $this->get('table')->getRepository()
            ->createQueryBuilder('t')
            ->where("t.anr = ?1")
            ->andWhere("t.object = ?2")
            ->setParameter(1, $instance['anr']->id)
            ->setParameter(2, $instance['object']->id)
            ->getQuery()->getResult();
        $anr = $instance['anr']->getJsonArray();

        foreach ($result as $r) {
            $names = [
                'name1' => $anr['label1'],//." > ".$r->get('name1'),
                'name2' => $anr['label2'],//." > ".$r->get('name2'),
                'name3' => $anr['label3'],//." > ".$r->get('name3'),
                'name4' => $anr['label4'],//." > ".$r->get('name4'),
            ];

            $asc = array_reverse($this->get('table')->getAscendance($r));
            foreach ($asc as $a) {
                $names['name1'] .= ' > ' . $a['name1'];
                $names['name2'] .= ' > ' . $a['name2'];
                $names['name3'] .= ' > ' . $a['name3'];
                $names['name4'] .= ' > ' . $a['name4'];
            }

            $names['id'] = $r->get('id');
            $instances[] = $names;
        }
        return $instances;
    }

    /**
     * Get Risks
     *
     * @param $anrId
     * @param null $instanceId
     * @param array $params
     * @param bool $count
     * @return int
     * @throws \Exception
     */
    public function getRisks($anrId, $instanceId = null, $params = [], $count = false)
    {
        $params['order'] = isset($params['order']) ? $params['order'] : 'maxRisk';

        if (!empty($instanceId)) {
            $instance = $this->get('table')->getEntity($instanceId);
            if ($instance->get('anr')->get('id') != $anrId) {
                throw new \Exception('Anr ids differents', 412);
            }
        }

        $l = $this->getLanguage();
        if ($count) {
            $arraySelect = ['COUNT(o.id) as nb'];
        } else {
            $arraySelect = [
                'o.id as oid',
                'ir.id as id',
                'i.id as instance',
                'a.id as amv',
                'ass.id as asset',
                'ass.label' . $l . ' as assetLabel' . $l . '',
                'ass.description' . $l . ' as assetDescription' . $l . '',
                't.id as threat',
                't.code as threatCode',
                't.label' . $l . ' as threatLabel' . $l . '',
                't.description' . $l . ' as threatDescription' . $l . '',
                'ir.threat_rate as threatRate',
                'v.id as vulnerability',
                'v.code as vulnCode',
                'v.label' . $l . ' as vulnLabel' . $l . '',
                'v.description' . $l . ' as vulnDescription' . $l . '',
                'ir.vulnerability_rate as vulnerabilityRate',
                'ir.`specific` as `specific`',
                'ir.reduction_amount as reductionAmount',
                'i.c as c_impact',
                'ir.risk_c as c_risk',
                't.c as c_risk_enabled',
                'i.i as i_impact',
                'ir.risk_i as i_risk',
                't.i as i_risk_enabled',
                'i.d as d_impact',
                'ir.risk_d as d_risk',
                't.d as d_risk_enabled',
                'ir.cache_targeted_risk as target_risk',
                'ir.cache_max_risk as max_risk',
                'ir.comment as comment',
                'CONCAT(m1.code, \' - \', m1.description' . $l . ') as measure1',
                'CONCAT(m2.code, \' - \', m2.description' . $l . ') as measure2',
                'CONCAT(m3.code, \' - \', m3.description' . $l . ') as measure3',
                'o.scope as scope',
                'ir.kind_of_measure as kindOfMeasure',
                'IF(ir.kind_of_measure IS NULL OR ir.kind_of_measure = ' . InstanceRisk::KIND_NOT_TREATED . ', false, true) as t',
            ];
        }

        $sql = "
            SELECT      " . implode(',', $arraySelect) . "
            FROM        (
                SELECT      id, threat_rate, vulnerability_rate, `specific`, reduction_amount, risk_c, risk_i, risk_d, cache_targeted_risk, cache_max_risk, comment, kind_of_measure, instance_id, amv_id, threat_id, vulnerability_id, asset_id
                FROM        instances_risks
                WHERE       anr_id = :anrid
                AND         cache_max_risk >= -1
                ORDER BY    cache_max_risk DESC) AS ir
            INNER JOIN  instances i
            ON          ir.instance_id = i.id
            LEFT JOIN   amvs AS a
            ON          ir.amv_id = a.id
            INNER JOIN  threats AS t
            ON          ir.threat_id = t.id
            INNER JOIN  vulnerabilities AS v
            ON          ir.vulnerability_id = v.id
            LEFT JOIN   assets AS ass
            ON          ir.asset_id = ass.id
            INNER JOIN  objects AS o
            ON          i.object_id = o.id
            LEFT JOIN   measures as m1
            ON          a.measure1_id = m1.id
            LEFT JOIN   measures as m2
            ON          a.measure2_id = m2.id
            LEFT JOIN   measures as m3
            ON          a.measure3_id = m3.id
            WHERE       1 = 1 ";
        $queryParams = [
            ':anrid' => $anrId,
        ];
        $typeParams = [];

        if (empty($instance)) {
            // On prend toutes les instances, on est sur l'anr
        } elseif ($instance->get('asset') && $instance->get('asset')->get('type') == \MonarcCore\Model\Entity\AssetSuperClass::TYPE_PRIMARY) {
            $instanceIds = [];
            $instanceIds[$instance->get('id')] = $instance->get('id');
            $this->get('table')->initTree($instance);
            $temp = isset($instance->parameters['children']) ? $instance->parameters['children'] : [];
            while (!empty($temp)) {
                $sub = array_shift($temp);
                $instanceIds[$sub->get('id')] = $sub->get('id');
                if (!empty($sub->parameters['children'])) {
                    foreach ($sub->parameters['children'] as $subsub) {
                        array_unshift($temp, $subsub);
                    }
                }
            }

            $sql .= " AND i.id IN (:ids) ";
            $queryParams[':ids'] = $instanceIds;
            $typeParams[':ids'] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        } else {
            $sql .= " AND i.id = :id ";
            $queryParams[':id'] = $instance->get('id');
        }

        // FILTER: kind_of_measure ==
        if (isset($params['kindOfMeasure'])) {
            if ($params['kindOfMeasure'] == \MonarcCore\Model\Entity\InstanceRiskSuperClass::KIND_NOT_TREATED) {
                $sql .= " AND (ir.kind_of_measure IS NULL OR ir.kind_of_measure = :kom) ";
                $queryParams[':kom'] = \MonarcCore\Model\Entity\InstanceRiskSuperClass::KIND_NOT_TREATED;
            } else {
                $sql .= " AND ir.kind_of_measure = :kom ";
                $queryParams[':kom'] = $params['kindOfMeasure'];
            }
        }
        // FILTER: Keywords
        if (!empty($params['keywords'])) {
            $filters = [
                'ass.label' . $l . '',
                //'amv.label'.$l.'',
                't.label' . $l . '',
                'v.label' . $l . '',
                'm1.code',
                'm1.description' . $l . '',
                'm2.code',
                'm2.description' . $l . '',
                'm3.code',
                'm3.description' . $l . '',
                'i.name' . $l . '',
                'ir.comment',
            ];
            $orFilter = [];
            foreach ($filters as $f) {
                $k = str_replace('.', '', $f);
                $orFilter[] = $f . " LIKE :" . $k;
                $queryParams[":$k"] = '%' . $params['keywords'] . '%';
            }
            $sql .= " AND (" . implode(' OR ', $orFilter) . ") ";
        }
        // FILTER: cache_max_risk (min)
        if (isset($params['thresholds']) && $params['thresholds'] > 0) {
            $sql .= " AND ir.cache_max_risk > :min ";
            $queryParams[':min'] = $params['thresholds'];
        }

        // GROUP BY if scope = GLOBAL
        $sql .= " GROUP BY IF(o.scope = " . Object::SCOPE_GLOBAL . ",o.id,ir.id), ir.threat_id, ir.vulnerability_id ";

        // ORDER
        $params['order_direction'] = isset($params['order_direction']) && strtolower(trim($params['order_direction'])) != 'asc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY ";
        switch ($params['order']) {
            case 'instance':
                $sql .= " i.name$l ";
                break;
            case 'auditOrder':
                $sql .= " a.position ";
                break;
            case 'c_impact':
                $sql .= " i.c ";
                break;
            case 'i_impact':
                $sql .= " i.i ";
                break;
            case 'd_impact':
                $sql .= " i.d ";
                break;
            case 'threat':
                $sql .= " t.label$l ";
                break;
            case 'vulnerability':
                $sql .= " v.label$l ";
                break;
            case 'vulnerabilityRate':
                $sql .= " ir.vulnerability_rate ";
                break;
            case 'threatRate':
                $sql .= " ir.threat_rate ";
                break;
            case 'targetRisk':
                $sql .= " ir.cache_targeted_risk ";
                break;
            default:
            case 'maxRisk':
                $sql .= " ir.cache_max_risk ";
                break;
        }
        $sql .= " " . $params['order_direction'] . " ";
        if ($params['order'] != 'instance') {
            $sql .= " , i.name$l ASC ";
        }
        $sql .= " , t.code ASC , v.code ASC ";

        if ($count) {
            $res = $this->get('anrTable')->getDb()->getEntityManager()->getConnection()
                ->fetchAll($sql, $queryParams, $typeParams);
            return count($res);
        } else {
            // LIMIT
            if (!empty($params['limit']) && !empty($params['page']) && $params['limit'] > 0) {
                $sql .= " LIMIT :l1, :l2 ";
                $queryParams[':l1'] = intval(($params['page'] - 1) * $params['limit']);
                $queryParams[':l2'] = intval($params['limit']);
                $typeParams[':l1'] = \PDO::PARAM_INT;
                $typeParams[':l2'] = \PDO::PARAM_INT;
            }
            return $this->get('anrTable')->getDb()->getEntityManager()->getConnection()
                ->fetchAll($sql, $queryParams, $typeParams);
        }
    }

    /**
     * Find In Fields
     *
     * @param $obj
     * @param $search
     * @param array $fields
     * @return bool
     */
    protected function findInFields($obj, $search, $fields = [])
    {
        foreach ($fields as $field) {
            if (stripos((is_object($obj) ? $obj->{$field} : $obj[$field]), $search) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get Csv Risks
     *
     * @param $anrId
     * @param null $instance
     * @param array $params
     * @return string
     */
    public function getCsvRisks($anrId, $instance = null, $params = [])
    {
        $risks = $this->getRisks($anrId, $instance, $params);

        $lang = $this->getLanguage();

        $translate = $this->get('translateService');

        $output = '';
        if (count($risks) > 0) {
            $fields = [
                'instanceName' . $lang => $translate->translate('Instance', $lang),
                'c_impact' => $translate->translate('Impact C', $lang),
                'i_impact' => $translate->translate('Impact I', $lang),
                'd_impact' => $translate->translate('Impact D', $lang),
                'threatLabel' . $lang => $translate->translate('Threat', $lang),
                'threatCode' => $translate->translate('Threat code', $lang),
                'threatRate' => $translate->translate('Prob.', $lang),
                'vulnLabel' . $lang => $translate->translate('Vulnerability', $lang),
                'vulnerabilityCode' => $translate->translate('Vulnerability code', $lang),
                'vulnerabilityRate' => $translate->translate('Qualif.', $lang),
                'c_risk' => $translate->translate('Current risk C', $lang),
                'i_risk' => $translate->translate('Current risk I', $lang),
                'd_risk' => $translate->translate('Current risk D', $lang),
                'target_risk' => $translate->translate('Target risk', $lang),
            ];

            // Fill in the header
            $output .= implode(',', array_values($fields)) . "\n";

            // Fill in the lines then
            foreach ($risks as $risk) {
                $array_values = [];
                foreach ($fields as $k => $v) {
                    $array_values[] = $risk[$k];
                }
                $output .= '"';
                $output .= implode('","', str_replace('"', '\"', $array_values));
                $output .= "\"\r\n";
            }
        }

        return $output;
    }

    /**
     * Get Risks Op
     *
     * @param $instance
     * @param $anrId
     * @return array
     */
    public function getRisksOp($anrId, $instance = null, $params = [])
    {
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');

        $instancesIds = [];
        $instancesInfos = [];
        if ($instance) {
            $i = $instanceTable->getEntity($instance['id']);

            // Il faut aussi récupérer les fils
            $instanceTable->initTree($i);
            $temp = [$i];
            while (!empty($temp)) {
                $current = array_shift($temp);
                if ($current->get('asset')->get('type') == Asset::TYPE_PRIMARY) {
                    $instancesIds[] = $current->get('id');
                    $instancesInfos[$current->id] = [
                        'id' => $current->id,
                        'scope' => $current->object->scope,
                        'name1' => $current->name1,
                        'name2' => $current->name2,
                        'name3' => $current->name3,
                        'name4' => $current->name4
                    ];
                }
                $children = $current->getParameter('children');
                if (!empty($children)) {
                    foreach ($children as $c) {
                        array_unshift($temp, $c);
                    }
                }
            }
        } else {
            $instances = $instanceTable->getEntityByFields(['anr' => $anrId]);
            foreach ($instances as $i) {
                if ($i->get('asset')->get('type') == Asset::TYPE_PRIMARY) {
                    $instancesIds[] = $i->id;
                    $instancesInfos[$i->id] = [
                        'id' => $i->id,
                        'scope' => $i->object->scope,
                        'name1' => $i->name1,
                        'name2' => $i->name2,
                        'name3' => $i->name3,
                        'name4' => $i->name4
                    ];
                }
            }
        }

        //retrieve risks instances
        /** @var InstanceRiskOpService $instanceRiskServiceOp */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        $instancesRisksOp = $instanceRiskOpService->getInstancesRisksOp($instancesIds, $anrId, $params);

        $riskOps = [];
        foreach ($instancesRisksOp as $instanceRiskOp) {
            // Add risk
            $riskOps[] = [
                'id' => $instanceRiskOp->id,
                'instanceInfos' => isset($instancesInfos[$instanceRiskOp->instance->id]) ? $instancesInfos[$instanceRiskOp->instance->id] : [],
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

    /**
     * Get Csv Risks Op
     *
     * @param $anrId
     * @param null $instance
     * @param array $params
     * @return string
     */
    public function getCsvRisksOp($anrId, $instance = null, $params = [])
    {
        $risks = $this->getRisksOp($anrId, $instance, $params);

        $output = '';
        if (count($risks) > 0) {
            // Fill in the header
            $output .= implode(',', array_keys($risks[0])) . "\n";

            // Fill in the lines then
            foreach ($risks as $risk) {
                $array_values = array_values($risk);
                $output .= '"';
                $output .= implode('","', str_replace('"', '\"', $array_values));
                $output .= "\"\r\n";
            }
        }

        return $output;
    }

    /**
     * Get Instances Risks
     *
     * @param $anrId
     * @param $instances
     * @return array
     */
    protected function getInstancesRisks($anrId, $instances)
    {
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
    protected function getConsequences($anrId, $instance)
    {
        $instanceId = $instance['id'];

        /** @var InstanceConsequenceTable $table */
        $table = $this->get('instanceConsequenceTable');
        $instanceConsequences = $table->getEntityByFields(['anr' => $anrId, 'instance' => $instanceId]);

        $consequences = [];
        foreach ($instanceConsequences as $instanceConsequence) {
            /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
            $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
            $scaleImpactType = $scaleImpactTypeTable->getEntity($instanceConsequence->scaleImpactType->id);

            if (!$scaleImpactType->isHidden) {
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
    public function findByAnr($anrId)
    {
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');
        $allInstances = $instanceTable->getEntityByFields(['anr' => $anrId], ['parent' => 'DESC', 'position' => 'ASC']);

        $instances = $temp = [];
        foreach ($allInstances as $key => $instance) {
            $instanceArray = $instance->getJsonArray();
            $instanceArray['scope'] = $instance->object->scope;
            $instanceArray['child'] = [];
            $instanceArray['parent'] = is_null($instance->get('parent')) ? 0 : $instance->get('parent')->get('id');

            $instances[$instanceArray['parent']][$instanceArray['id']] = $instanceArray;
            if (is_null($instance->get('parent'))) {
                $temp[] = $instanceArray;
            }
        }
        unset($allInstances);

        if (!empty($instances) && !empty($temp)) {
            while (!empty($temp)) {
                $current = array_shift($temp);
                if (!empty($instances[$current['id']])) {
                    foreach ($instances[$current['id']] as &$fam) {
                        $instances[$current['parent']][$current['id']]['child'][$fam['id']] = &$fam;
                        array_unshift($temp, $fam);
                    }
                    if (isset($instances[$current['parent']][$current['id']]['child'])) {
                        $instances[$current['parent']][$current['id']]['child'] = array_values($instances[$current['parent']][$current['id']]['child']);
                    }
                }
            }
        }
        return isset($instances[0]) ? array_values($instances[0]) : [];
    }

    /**
     * Create Instance Consequences
     *
     * @param $instanceId
     * @param $anrId
     * @param $object
     */
    public function createInstanceConsequences($instanceId, $anrId, $object)
    {
        if ($object->scope == Object::SCOPE_GLOBAL) {
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('instanceTable');
            $brothers = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $object->id]);
        }

        if (($object->scope == Object::SCOPE_GLOBAL) && (count($brothers) > 1)) {
            foreach ($brothers as $brother) {
                if ($brother->id != $instanceId) {
                    $refInstance = $brother;
                    break;
                }
            }

            /** @var InstanceConsequenceTable $instanceConsequenceTable */
            $instanceConsequenceTable = $this->get('instanceConsequenceTable');
            $instancesConsequences = $instanceConsequenceTable->getEntityByFields(['anr' => $anrId, 'instance' => $refInstance->id]);

            $i = 1;
            $nbInstancesConsequences = count($instancesConsequences);
            foreach ($instancesConsequences as $instanceConsequence) {

                $data = [
                    'anr' => $this->get('anrTable')->getEntity($anrId),
                    'instance' => $this->get('instanceTable')->getEntity($instanceId),
                    'object' => $object,
                    'scaleImpactType' => $instanceConsequence->scaleImpactType,
                    'isHidden' => $instanceConsequence->isHidden,
                    'locallyTouched' => $instanceConsequence->locallyTouched,
                    'c' => $instanceConsequence->c,
                    'i' => $instanceConsequence->i,
                    'd' => $instanceConsequence->d,
                ];

                $class = $this->get('instanceConsequenceEntity');
                $instanceConsequenceEntity = new $class();

                $instanceConsequenceEntity->exchangeArray($data);

                $instanceConsequenceTable->save($instanceConsequenceEntity, ($i == $nbInstancesConsequences));

                $i++;
            }
        } else {
            //retrieve scale impact types
            /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
            $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
            //$scalesImpactTypes = $scaleImpactTypeTable->getEntityByFields(['anr' => $anrId, 'isHidden' => 0]);
            $scalesImpactTypes = $scaleImpactTypeTable->getEntityByFields(['anr' => $anrId]);

            /** @var InstanceConsequenceTable $instanceConsequenceTable */
            $instanceConsequenceTable = $this->get('instanceConsequenceTable');

            $i = 1;
            $nbScalesImpactTypes = count($scalesImpactTypes);
            foreach ($scalesImpactTypes as $scalesImpactType) {
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
                $instanceConsequenceTable->save($instanceConsequenceEntity, ($i == $nbScalesImpactTypes));
                $i++;
            }
        }
    }

    /**
     * Export
     *
     * @param $data
     * @return string
     * @throws \Exception
     */
    public function export(&$data)
    {
        if (empty($data['id'])) {
            throw new \Exception('Instance to export is required', 412);
        }
        if (empty($data['password'])) {
            $data['password'] = '';
        }

        $filename = "";
        $with_eval = isset($data['assessments']) && $data['assessments'];
        $return = $this->generateExportArray($data['id'], $filename, $with_eval);
        $data['filename'] = $filename;

        return base64_encode($this->encrypt(json_encode($return), $data['password']));
    }

    /**
     * Generate Export Array
     *
     * @param $id
     * @param string $filename
     * @param bool $with_eval
     * @param bool $with_scale
     * @return array
     * @throws \Exception
     */
    public function generateExportArray($id, &$filename = "", $with_eval = false, &$with_scale = true)
    {
        if (empty($id)) {
            throw new \Exception('Instance to export is required', 412);
        }
        $entity = $this->get('table')->getEntity($id);

        if (!$entity) {
            throw new \Exception('Entity `id` not found.');
        }

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $entity->get('name' . $this->getLanguage()));

        $objInstance = [
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
        ];

        $return = [
            'type' => 'instance',
            'version' => $this->getVersion(),
            'with_eval' => $with_eval,
            'instance' => $entity->getJsonArray($objInstance),
            'object' => $this->get('objectExportService')->generateExportArray($entity->get('object')->get('id')),
            // 'asset' => $this->get('assetService')->generateExportArray($entity->get('asset')->get('id')), // l'asset sera porté par l'objet
        ];
        $return['instance']['asset'] = $entity->get('asset')->get('id');
        $return['instance']['object'] = $entity->get('object')->get('id');
        $return['instance']['root'] = 0;
        $return['instance']['parent'] = $entity->get('parent') ? $entity->get('parent')->get('id') : 0;

        // Scales
        if ($with_eval && $with_scale) {
            $with_scale = false;
            $return['scales'] = [];
            $scaleTable = $this->get('scaleTable');
            $scales = $scaleTable->getEntityByFields(['anr' => $entity->get('anr')->get('id')]);
            $scalesArray = [
                'min' => 'min',
                'max' => 'max',
                'type' => 'type',
            ];
            foreach ($scales as $s) {
                $return['scales'][$s->type] = $s->getJsonArray($scalesArray);
            }
        }

        // Instance risk
        $return['risks'] = [];
        $instanceRiskTable = $this->get('instanceRiskService')->get('table');
        $instanceRiskResults = $instanceRiskTable->getRepository()
            ->createQueryBuilder('t')
            ->where("t.instance = :i")
            ->setParameter(':i', $entity->get('id'))->getQuery()->getResult();
        $instanceRiskArray = [
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
        ];

        $treatsObj = [
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
        ];
        $vulsObj = [
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
        ];
        $riskIds = [];
        foreach ($instanceRiskResults as $ir) {
            $riskIds[$ir->get('id')] = $ir->get('id');
            if (!$with_eval) {
                $ir->set('vulnerabilityRate', '-1');
                $ir->set('threatRate', '-1');
                $ir->set('kindOfMeasure', 0);
                $ir->set('reductionAmount', 0);
                $ir->set('comment', '');
                $ir->set('commentAfter', '');
            }

            $ir->set('mh', 1);
            $ir->set('riskC', '-1');
            $ir->set('riskI', '-1');
            $ir->set('riskD', '-1');
            $return['risks'][$ir->get('id')] = $ir->getJsonArray($instanceRiskArray);

            $irAmv = $ir->get('amv');
            $return['risks'][$ir->get('id')]['amv'] = empty($irAmv) ? null : $irAmv->get('id');
            if (!empty($return['risks'][$ir->get('id')]['amv']) && empty($return['amvs'][$ir->get('amv')->get('id')])) {
                list(
                    $amv,
                    $threats,
                    $vulns,
                    $themes,
                    $measures) = $this->get('amvService')->generateExportArray($ir->get('amv')); // TODO: measuress
                $return['amvs'][$ir->get('amv')->get('id')] = $amv;
                if (empty($return['threats'])) {
                    $return['threats'] = $threats;
                } else {
                    $return['threats'] += $threats;
                }
                if (empty($return['vuls'])) {
                    $return['vuls'] = $vulns;
                } else {
                    $return['vuls'] += $vulns;
                }
                if (empty($return['measures'])) {
                    $return['measures'] = $measures;
                } else {
                    $return['measures'] += $measures;
                }
            }

            $threat = $ir->get('threat');
            if (!empty($threat)) {
                if (empty($return['threats'][$ir->get('threat')->get('id')])) {
                    $return['threats'][$ir->get('threat')->get('id')] = $ir->get('threat')->getJsonArray($treatsObj);
                }
                $return['risks'][$ir->get('id')]['threat'] = $ir->get('threat')->get('id');
            } else {
                $return['risks'][$ir->get('id')]['threat'] = null;
            }

            $vulnerability = $ir->get('vulnerability');
            if (!empty($vulnerability)) {
                if (empty($return['vuls'][$ir->get('vulnerability')->get('id')])) {
                    $return['vuls'][$ir->get('vulnerability')->get('id')] = $ir->get('vulnerability')->getJsonArray($vulsObj);
                }
                $return['risks'][$ir->get('id')]['vulnerability'] = $ir->get('vulnerability')->get('id');
            } else {
                $return['risks'][$ir->get('id')]['vulnerability'] = null;
            }
        }

        // Recommandation
        if ($with_eval && !empty($riskIds) && $this->get('recommandationRiskTable')) {
            $recosObj = [
                'id' => 'id',
                'code' => 'code',
                'description' => 'description',
                'position' => 'position',
                'comment' => 'comment',
                'responsable' => 'responsable',
                'duedate' => 'duedate',
                'counterTreated' => 'counterTreated',
            ];
            $return['recos'] = $recoIds = [];
            $recoRisk = $this->get('recommandationRiskTable')->getEntityByFields(['anr' => $entity->get('anr')->get('id'), 'instanceRisk' => $riskIds], ['id' => 'ASC']);
            foreach ($recoRisk as $rr) {
                if (!empty($rr)) {
                    $return['recos'][$rr->get('instanceRisk')->get('id')][$rr->get('recommandation')->get('id')] = $rr->get('recommandation')->getJsonArray($recosObj);
                    $return['recos'][$rr->get('instanceRisk')->get('id')][$rr->get('recommandation')->get('id')]['commentAfter'] = $rr->get('commentAfter');
                    $recoIds[$rr->get('recommandation')->get('id')] = $rr->get('recommandation')->get('id');
                }
            }

            if (!empty($recoIds) && $this->get('recommandationMeasureTable')) {
                $measuresObj = [
                    'id' => 'id',
                    'code' => 'code',
                    'status' => 'status',
                    'description1' => 'description1',
                    'description2' => 'description2',
                    'description3' => 'description3',
                    'description4' => 'description4',
                ];
                $links = $this->get('recommandationMeasureTable')->getEntityByFields(['anr' => $entity->get('anr')->get('id'), 'recommandation' => $recoIds]);
                $data['recolinks'] = [];
                if (!isset($return['measures'])) {
                    $return['measures'] = [];
                }
                foreach ($links as $lk) {
                    if (!empty($lk)) {
                        $return['recolinks'][$lk->get('recommandation')->get('id')][$lk->get('measure')->get('id')] = $lk->get('measure')->get('id');
                        $return['measures'][$lk->get('measure')->get('id')] = $lk->get('measure')->getJsonArray($measuresObj);
                    }
                }
            }
        }

        // Instance risk op
        $return['risksop'] = [];
        $instanceRiskOpTable = $this->get('instanceRiskOpService')->get('table');
        $instanceRiskOpResults = $instanceRiskOpTable->getRepository()
            ->createQueryBuilder('t')
            ->where("t.instance = :i")
            ->setParameter(':i', $entity->get('id'))->getQuery()->getResult();
        $instanceRiskOpArray = [
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
        ];
        $toReset = [
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
        ];
        foreach ($instanceRiskOpResults as $iro) {
            if (!$with_eval) {
                foreach ($toReset as $r) {
                    $iro->set($r, '-1');
                }
                $iro->set('kindOfMeasure', 0);
                $iro->set('comment', '');
                $iro->set('mitigation', '');
            }
            $return['risksop'][$iro->get('id')] = $iro->getJsonArray($instanceRiskOpArray);
        }

        // TODO: Recommandations liées aux RisksOp

        // Instance consequence
        if ($with_eval) {
            $instanceConseqArray = [
                'id' => 'id',
                'isHidden' => 'isHidden',
                'locallyTouched' => 'locallyTouched',
                'c' => 'c',
                'i' => 'i',
                'd' => 'd',
            ];
            $scaleTypeArray = [
                'id' => 'id',
                'label1' => 'label1',
                'label2' => 'label2',
                'label3' => 'label3',
                'label4' => 'label4',
                'isSys' => 'isSys',
                'isHidden' => 'isHidden',
                'position' => 'position',
            ];
            $return['consequences'] = [];
            $instanceConseqTable = $this->get('instanceConsequenceService')->get('table');
            $instanceConseqResults = $instanceConseqTable->getRepository()
                ->createQueryBuilder('t')
                ->where("t.instance = :i")
                ->setParameter(':i', $entity->get('id'))->getQuery()->getResult();
            foreach ($instanceConseqResults as $ic) {
                $return['consequences'][$ic->get('id')] = $ic->getJsonArray($instanceConseqArray);
                $return['consequences'][$ic->get('id')]['scaleImpactType'] = $ic->get('scaleImpactType')->getJsonArray($scaleTypeArray);
                $return['consequences'][$ic->get('id')]['scaleImpactType']['scale'] = $ic->get('scaleImpactType')->get('scale')->get('id');
            }
        }

        $instanceTableResults = $this->get('table')->getRepository()
            ->createQueryBuilder('t')
            ->where('t.parent = :p')
            ->setParameter(':p', $entity->get('id'))->getQuery()->getResult();
        $return['children'] = [];
        $f = '';
        foreach ($instanceTableResults as $i) {
            $return['children'][$i->get('id')] = $this->generateExportArray($i->get('id'), $f, $with_eval, $with_scale);
        }
        return $return;
    }

    /**
     * Get Displayed Ascendance
     *
     * @param $instance
     * @param bool $simple
     * @param null $anr_label
     * @param bool $ignore_last
     * @return string
     */
    public function getDisplayedAscendance($instance, $simple = false, $anr_label = null, $ignore_last = false)
    {
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $ascendance = $table->getAscendance($instance);

        $label = '';
        foreach ($ascendance as $parent) {
            if (!$ignore_last) {
                if (!$simple) {
                    $label .= "<span class=\"superior\"> > </span>" . $parent['name1'] . ' ' . $label;
                } else {
                    $label .= " > " . $parent['name1'] . ' ' . $label;
                }
            } else {
                $ignore_last = false;//permet de passer $this mais de continuer ensuite
            }
        }

        return $label;
    }
}
