<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\InstanceConsequenceSuperClass;
use Monarc\Core\Model\Entity\InstanceRiskOp;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ScaleCommentSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceConsequenceTable;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceRiskTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\ScaleCommentTable;
use Monarc\Core\Model\Table\ScaleImpactTypeTable;
use Laminas\EventManager\EventManager;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Mapping\MappingException;
use Laminas\EventManager\SharedEventManager;
use Monarc\Core\Model\Table\ScaleTable;

/**
 * Instance Service
 *
 * Class InstanceService
 * @package Monarc\Core\Service
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
    protected $scaleCommentTable;
    protected $scaleImpactTypeTable;
    protected $instanceConsequenceTable;
    protected $instanceConsequenceEntity;
    protected $instanceRiskOpTable;

    protected $assetTable;

    // Services
    protected $instanceConsequenceService;
    protected $instanceRiskService;
    protected $instanceRiskOpService;
    protected $objectObjectService;
    protected $translateService;
    protected $configService;
    protected $operationalRiskScalesExportService;

    // Export (Services)
    protected $objectExportService;
    protected $amvService;

    protected $forbiddenFields = ['anr', 'asset', 'object', 'ch', 'dh', 'ih'];

    /** @var SharedEventManager */
    private $sharedManager;

    /**
     * Instantiate Object To Anr
     *
     * @param $anrId
     * @param $data
     * @return mixed|null
     * @throws Exception
     */
    public function instantiateObjectToAnr($anrId, $data, $managePosition = true, $rootLevel = false, $mode = Instance::MODE_CREA_NODE)
    {
        try {
            $object = $this->get('objectTable')->getEntity($data['object']);
        } catch (MappingException | QueryException $e) {
            $object = $this->get('objectTable')->getEntity(['uuid' => $data['object'], 'anr' => $anrId]);
        }

        //verify if user is authorized to instantiate this object
        $authorized = false;
        foreach ($object->anrs as $anr) {
            if ($anr->id == $anrId) {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
            throw new Exception('Object is not an object of this anr', 412);
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
            if (in_array('anr', $this->get('assetTable')->getClassMetadata()->getIdentifierFieldNames())) {
                $data['asset'] = ['uuid' => $object->getAsset()->getUuid(), 'anr' => $anrId];
            } else {
                $data['asset'] = $object->getAsset()->getUuid();
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

        // create instance
        /** @var Instance $instance */
        $instance = $this->get('entity');
        if ($instance->get('id')) {
            $c = get_class($instance);
            $instance = new $c;
            $instance->setDbAdapter($this->get('table')->getDb());
            $instance->setLanguage($this->getLanguage());
            $instance->initParametersChanges();
        }
        $instance->exchangeArray($data, false);

        //instance dependencies
        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        //level
        $this->updateInstanceLevels($rootLevel, $data['object'], $instance, $mode, $anrId);

        $instance->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $id = $this->get('table')->save($instance);

        //instances risk
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRiskService->createInstanceRisks($instance, $instance->getAnr(), $object);

        //instances risks op
        /** @var InstanceRiskOpService $instanceRiskOpService */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        $instanceRiskOpService->createInstanceRisksOp($instance, $object);

        //instances consequences
        $instanceConsequence = $this->createInstanceConsequences($id, $anrId, $object);
        $this->get('instanceConsequenceService')->updateInstanceImpacts($instanceConsequence);

        // Check if the root element is not the same as current child element to avoid a circular dependency.
        if ($rootLevel
            || $parent === null
            || $parent->getRoot() === null
            || $parent->getRoot()->getObject()->getUuid() !== $instance->getObject()->getUuid()
        ) {
            $this->createChildren($anrId, $id, $object);
        }

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
     * @throws Exception
     */
    public function updateInstance($anrId, $id, $data, &$historic = [], $managePosition = false)
    {
        $historic[] = $id;
        $initialData = $data;
        //retrieve instance
        /** @var InstanceTable $table */
        $table = $this->get('table');
        /** @var Instance $instance */
        $instance = $table->getEntity($id);
        if (!$instance) {
            throw new Exception('Instance does not exist', 412);
        }
        $instance->setDbAdapter($table->getDb());
        $instance->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new Exception('Data missing', 412);
        }

        if (isset($data['parent']) && empty($data['parent'])) {
            $data['parent'] = null;
        } elseif (!empty($data['parent'])) {
            $parent = $table->getEntity(isset($data['parent']['id']) ? $data['parent']['id'] : $data['parent']);
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
                $return = $table->getRepository()->createQueryBuilder('t')
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
                    $queryBuilder = $table->getRepository()->createQueryBuilder('t')
                        ->select('t.id');
                    if (isset($parent)) {
                        $queryBuilder->where('t.parent = :parent')
                            ->setParameter(':parent', $parent->get('id'));
                    } else {
                        $queryBuilder->where('t.parent IS NULL');
                    }
                    $anr = $instance->getAnr();
                    if ($anr) {
                        $queryBuilder->andWhere('t.anr = :anr')->setParameter('anr', $anr);
                    } else {
                        $queryBuilder->andWhere('t.anr IS NULL');
                    }
                    /** @var Instance $result */
                    $result = $queryBuilder->andWhere('t.position = :pos')
                        ->setParameter(':pos', $data['position'] + ($data['position'] < $instance->get('position') ? -1 : 0))
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();
                    $data['implicitPosition'] = 2;
                    if ($result !== null) {
                        $data['implicitPosition'] = 3;
                        $data['previous'] = $result['id'];
                    }
                }
            }
            unset($data['position']);
        }

        $dataConsequences = (isset($data['consequences'])) ? $data['consequences'] : null;
        $this->filterPostFields($data, $instance, $this->forbiddenFields + ['c', 'i', 'd']);
        $instance->exchangeArray($data);

        $this->setDependencies($instance, $this->dependencies);

        $instance->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $id = $this->get('table')->save($instance);

        if ($dataConsequences) {
            $this->updateConsequences($anrId, ['consequences' => $dataConsequences], true);
        }

        $this->updateRisks($instance);

        $this->updateChildrenImpacts($instance);

        $this->updateBrothers($anrId, $instance, $initialData, $historic);

        if (\count($historic) === 1) {
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
     * @throws Exception
     */
    public function patchInstance($anrId, $id, $data, $historic = [], $modifyCid = false)
    {
        //security
        if ($modifyCid) { // on provient du trigger
            $this->forbiddenFields = ['anr', 'asset',]; //temporary remove object to allow creation
        }

        $this->filterPatchFields($data);

        //retrieve instance
        /** @var InstanceTable $table */
        $table = $this->get('table');
        /** @var Instance $instance */
        $instance = $table->getEntity($id);
        if (!$instance) {
            throw new Exception('Instance does not exist', 412);
        }

        if (isset($data['parent']) && empty($data['parent'])) {
            $data['parent'] = null;
        } elseif (!empty($data['parent'])) {
            $parent = $table->getEntity($data['parent']);
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
                $return = $table->getRepository()->createQueryBuilder('t')->select('COUNT(t.id)');
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
                if ($data['parent'] != $instance->get('parent')) {
                    $return++;
                }
                if ($data['position'] == $return) {
                    $data['implicitPosition'] = 2;
                } else {
                    $queryBuilder = $table->getRepository()->createQueryBuilder('t')
                        ->select('t.id');
                    if (isset($parent)) {
                        $queryBuilder->where('t.parent = :parent')
                            ->setParameter(':parent', $parent->get('id'));
                    } else {
                        $queryBuilder->where('t.parent IS NULL');
                    }
                    $anr = $instance->getAnr();
                    if ($anr) {
                        $queryBuilder->andWhere('t.anr = :anr')->setParameter('anr', $anr);
                    } else {
                        $queryBuilder->andWhere('t.anr IS NULL');
                    }
                    /** @var Instance $result */
                    $result = $queryBuilder->andWhere('t.position = :pos')
                        ->setParameter(':pos', $data['position'] + ($data['position'] < $instance->getPosition() || $data['parent'] != $instance->getParent() ? -1 : 0))
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();
                    $data['implicitPosition'] = 2;
                    if ($result !== null) {
                        $data['implicitPosition'] = 3;
                        $data['previous'] = $result['id'];
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

        $instance->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $id = $table->save($instance);

        $this->refreshImpactsInherited($instance);

        $this->updateRisks($instance);

        $this->updateChildrenImpacts($instance);

        $data['asset'] = ['uuid' => $instance->getObject()->getAsset()->getUuid(), 'anr' => $anrId];
        $data['object'] = $instance->getObject()->getUuid();
        $data['name1'] = $instance->getName1();
        $data['label1'] = $instance->getLabel1();

        unset($data['implicitPosition']);
        unset($data['previous']);
        unset($data['position']);

        $this->updateBrothers($anrId, $instance, $data, $historic);

        $this->objectImpacts($instance);

        return $id;
    }

    /**
     * @param int $id
     *
     * @return void
     *
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete($id)
    {
        /** @var InstanceTable $table */
        $table = $this->get('table');
        $instance = $table->findById($id);

        // only root instance can be delete
        if (!$instance->isLevelRoot()) {
            throw new Exception('This is not a root instance', 412);
        }

        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRiskService->deleteInstanceRisks($instance);

        /** @var InstanceRiskOpService $operationalRiskService */
        $operationalRiskService = $this->get('instanceRiskOpService');
        $operationalRiskService->deleteOperationalRisks($instance);

        $table->deleteEntity($instance);
    }

    /**
     * Object Impacts
     *
     * @param InstanceSuperClass $instance
     */
    protected function objectImpacts($instance)
    {
        $eventManager = new EventManager($this->sharedManager, ['object']);
        $eventManager->trigger('patch', $this, [
            'objectId' => $instance->getObject()->getUuid(),
            'data' => [
                'name1' => $instance->getName1(),
                'name2' => $instance->getName2(),
                'name3' => $instance->getName3(),
                'name4' => $instance->getName4(),
                'label1' => $instance->getLabel1(),
                'label2' => $instance->getLabel2(),
                'label3' => $instance->getLabel3(),
                'label4' => $instance->getLabel4(),
                'anr' => $instance->getAnr()->getId(),
            ],
        ]);
    }

    public function setSharedManager(SharedEventManager $sharedManager)
    {
        $this->sharedManager = $sharedManager;
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
        $children = $objectObjectService->getChildren($object, $anrId);
        foreach ($children as $child) {
            $data = [
                'object' => $child->getChild()->getUuid(),
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
    protected function updateInstanceLevels($rootLevel, $objectId, &$instance, $mode, $anrId = null)
    {
        if (($rootLevel) || ($mode == Instance::MODE_CREA_ROOT)) {
            $instance->setLevel(Instance::LEVEL_ROOT);
        } else {
            //retrieve children
            /** @var ObjectObjectService $objectObjectService */
            $objectObjectService = $this->get('objectObjectService');
            $children = $objectObjectService->getChildren($objectId, $anrId);

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

    /**
     * Update children
     *
     * @param $instance
     */
    public function updateChildrenImpacts($instance)
    {
        /** @var InstanceTable $table */
        $table = $this->get('table');
        if (!$instance instanceof \Monarc\Core\Model\Entity\InstanceSuperClass) {
            $instance = $this->get('table')->getEntity($instance);
        }
        /** @var InstanceSuperClass[] $children */
        $children = $table->getEntityByFields(['parent' => $instance->getId()]);

        foreach ($children as $child) {

            if ($child->getInheritedConfidentiality()) {
                $child->c = $instance->getConfidentiality();
            }

            if ($child->getInheritedIntegrity()) {
                $child->i = $instance->getIntegrity();
            }

            if ($child->getInheritedAvailability()) {
                $child->d = $instance->getAvailability();
            }

            $table->save($child);

            //update children
            $this->updateChildrenImpacts($child);

            if ($child->getAnr()) {
                $this->updateRisks($child);
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
        if ($instance->object->scope == MonarcObject::SCOPE_GLOBAL) {
            //retrieve instance with same object source
            /** @var InstanceTable $table */
            $table = $this->get('table');
            try{
                $brothers = $table->getEntityByFields(['object' => $instance->getObject()->getUuid()]);
            } catch (QueryException|MappingException $e) {
                $brothers = $table->getEntityByFields([
                    'object' => [
                        'uuid' => $instance->getObject()->getUuid(),
                        'anr' => $anrId
                    ]
                ]);
            }
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
                    //Unproper FIX to issue#31 to be reviewed when #7 fixed
                    $tempName='name'.$instance->getLanguage();
                    $tempLabel='label'.$instance->getLanguage();
                    $data['name'.$instance->getLanguage()] = $brother->$tempName;
                    $data['label'.$instance->getLanguage()] = $brother->$tempLabel;

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
                $patchInstance = ($i == count($data['consequences']));

                $dataConsequences = [
                    'anr' => $anrId,
                    'c' => (int)$consequence['c_risk'],
                    'i' => (int)$consequence['i_risk'],
                    'd' => (int)$consequence['d_risk'],
                    'isHidden' => (int)$consequence['isHidden'],
                ];

                /** @var InstanceConsequenceService $instanceConsequenceService */
                $instanceConsequenceService = $this->get('instanceConsequenceService');
                $instanceConsequenceService->patchConsequence($consequence['id'], $dataConsequences, $patchInstance, $fromInstance);

                $i++;
            }
        }
    }

    public function updateRisks(InstanceSuperClass $instance)
    {
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRisks = $instanceRiskService->getInstanceRisks($instance);

        $nb = \count($instanceRisks);
        foreach ($instanceRisks as $i => $instanceRisk) {
            $instanceRiskService->updateRisks($instanceRisk, ($i + 1) >= $nb);
        }
    }

    public function refreshImpactsInherited(InstanceSuperClass $instance)
    {
        //for cid, if value is inherited, retrieve value of parent
        //if there is no parent and value is inherited, value is equal to -1
        if ($instance->getInheritedConfidentiality()
            || $instance->getInheritedIntegrity()
            || $instance->getInheritedAvailability()
        ) {
            if ($instance->getInheritedConfidentiality()) {
                $instance->setConfidentiality(
                    $instance->getParent() !== null ? $instance->getParent()->getConfidentiality() : -1
                );
            }
            if ($instance->getInheritedIntegrity()) {
                $instance->setIntegrity(
                    $instance->getParent() !== null ? $instance->getParent()->getIntegrity() : -1
                );
            }
            if ($instance->getInheritedAvailability()) {
                $instance->setAvailability(
                    $instance->getParent() !== null ? $instance->getParent()->getAvailability() : -1
                );
            }

            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('table');

            $instanceTable->saveEntity($instance);
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
            ->innerJoin('t.object','object')
            ->where("t.anr = :anr")
            ->andWhere("object.uuid = :object_uuid")
            ->setParameter('anr', $instance['anr']->getId())
            ->setParameter('object_uuid', $instance['object']->getUuid())
            ->getQuery()->getResult();
        $anr = $instance['anr']->getJsonArray();

        foreach ($result as $r) {
            $names = [
                'name1' => $anr['label1'],//." > ".$r->get('name1'),
                'name2' => $anr['label2'],//." > ".$r->get('name2'),
                'name3' => $anr['label3'],//." > ".$r->get('name3'),
                'name4' => $anr['label4'],//." > ".$r->get('name4'),
            ];

            $asc = $this->get('table')->getAscendance($r);
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
     * Get Consequences
     *
     * @param $instance
     * @param $anrId
     * @return array
     */
    public function getConsequences($anrId, $instance, $delivery = false)
    {
        $instanceId = $instance['id'];

        /** @var InstanceConsequenceTable $instanceConsequenceTable */
        $instanceConsequenceTable = $this->get('instanceConsequenceTable');
        $instanceConsequences = $instanceConsequenceTable->getEntityByFields(['anr' => $anrId, 'instance' => $instanceId]);

        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->findById($anrId);

        $consequences = [];
        foreach ($instanceConsequences as $instanceConsequence) {
            /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
            $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
            $scaleImpactType = $scaleImpactTypeTable->getEntity($instanceConsequence->scaleImpactType->id);

            if (!$scaleImpactType->isHidden) {
                if ($delivery) {

                    /** @var ScaleCommentTable $scaleCommentTable */
                    $scaleCommentTable = $this->get('scaleCommentTable');
                    /** @var ScaleCommentSuperClass[] $scalesComments */
                    $scalesComments = $scaleCommentTable->getEntityByFields([
                        'anr' => $anrId,
                        'scaleImpactType' => $instanceConsequence->scaleImpactType->id,
                    ]);

                    $comments = [];
                    foreach ($scalesComments as $scaleComment) {
                        $comments[$scaleComment->getScaleValue()] = $scaleComment->getComment($anr->getLanguage());
                    }
                }

                $array = [
                    'id' => $instanceConsequence->id,
                    'scaleImpactType' => $scaleImpactType->type,
                    'scaleImpactTypeId' => $scaleImpactType->id,
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

                if ($delivery) {
                    $array['comments'] = $comments;
                }

                $consequences[] = $array;
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
        foreach ($allInstances as $instance) {
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
        if ($object->scope == MonarcObject::SCOPE_GLOBAL) {
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('table');

            try {
                $brothers = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $object->getUuid()]);
            } catch(MappingException | QueryException $e) {
                $brothers = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => [
                        'uuid' => $object->getUuid(),
                        'anr' => $anrId
                    ],
                ]);
            }
        }

        if (($object->scope == MonarcObject::SCOPE_GLOBAL) && (count($brothers) > 1)) {
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
                    'instance' => $this->get('table')->getEntity($instanceId),
                    'object' => $object,
                    'scaleImpactType' => $instanceConsequence->scaleImpactType,
                    'isHidden' => $instanceConsequence->isHidden,
                    'locallyTouched' => $instanceConsequence->locallyTouched,
                    'c' => $instanceConsequence->c,
                    'i' => $instanceConsequence->i,
                    'd' => $instanceConsequence->d,
                ];

                $class = $this->get('instanceConsequenceEntity');
                /** @var InstanceConsequenceSuperClass $instanceConsequenceEntity */
                $instanceConsequenceEntity = new $class();
                $instanceConsequenceEntity->setLanguage($this->getLanguage());
                $instanceConsequenceEntity->setDbAdapter($this->get('instanceConsequenceTable')->getDb());
                $instanceConsequenceEntity->exchangeArray($data);
                $instanceConsequenceEntity->setCreator(
                    $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
                );

                $instanceConsequenceTable->save($instanceConsequenceEntity, ($i == $nbInstancesConsequences));

                $i++;
            }
        } else {
            //retrieve scale impact types
            /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
            $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
            $scalesImpactTypes = $scaleImpactTypeTable->getEntityByFields(['anr' => $anrId]);

            /** @var InstanceConsequenceTable $instanceConsequenceTable */
            $instanceConsequenceTable = $this->get('instanceConsequenceTable');

            $i = 1;
            $nbScalesImpactTypes = count($scalesImpactTypes);
            foreach ($scalesImpactTypes as $scalesImpactType) {
                $data = [
                    'anr' => $this->get('anrTable')->getEntity($anrId),
                    'instance' => $this->get('table')->getEntity($instanceId),
                    'object' => $object,
                    'scaleImpactType' => $scalesImpactType,
                    'isHidden' => $scalesImpactType->isHidden,
                ];
                $class = $this->get('instanceConsequenceEntity');
                /** @var InstanceConsequenceSuperClass $instanceConsequenceEntity */
                $instanceConsequenceEntity = new $class();
                $instanceConsequenceEntity->setLanguage($this->getLanguage());
                $instanceConsequenceEntity->setDbAdapter($this->get('instanceConsequenceTable')->getDb());
                $instanceConsequenceEntity->exchangeArray($data);
                $instanceConsequenceEntity->setCreator(
                    $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
                );

                $instanceConsequenceTable->save($instanceConsequenceEntity, $i === $nbScalesImpactTypes);
                $i++;
            }
        }

        return $instanceConsequenceEntity;
    }

    /**
     * Export
     *
     * @param $data
     * @return string
     * @throws Exception
     */
    public function export(&$data)
    {
        if (empty($data['id'])) {
            throw new Exception('Instance to export is required', 412);
        }

        $filename = '';

        $withEval = isset($data['assessments']) && $data['assessments'];
        $withControls = isset($data['controls']) && $data['controls'];
        $withRecommendations = isset($data['recommendations']) && $data['recommendations'];
        $withScale = true;

        $exportedInstance = json_encode($this->generateExportArray(
            (int)$data['id'],
            $filename,
            $withEval,
            $withScale,
            $withControls,
            $withRecommendations,
            false
        ));
        $data['filename'] = $filename;

        if (!empty($data['password'])) {
            $exportedInstance = $this->encrypt($exportedInstance, $data['password']);
        }

        return $exportedInstance;
    }

    /**
     * @param int $id
     * @param string $filename
     * @param bool $withEval
     * @param bool $withScale
     * @param bool $withControls
     * @param bool $withRecommendations
     * @param bool $withUnlinkedRecommendations If we want to include all the unlinked (all the linked to the analyse).
     *
     * @return array
     * @throws EntityNotFoundException
     * @throws Exception
     */
    public function generateExportArray(
        $id,
        &$filename = "",
        $withEval = false,
        $withScale = true,
        $withControls = false,
        $withRecommendations = false,
        $withUnlinkedRecommendations = true
    ) {
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');
        $instance = $instanceTable->findById((int)$id);

        $instanceNameBasedOnLanguage = 'getName' . $this->getLanguage();
        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $instance->{$instanceNameBasedOnLanguage}());

        // TODO: ObjectExportService can be a class from client or core.
        /** @var ObjectExportService $objectExportService */
        $objectExportService = $this->get('objectExportService');
        $return = [
            'type' => 'instance',
            'monarc_version' => $this->get('configService')->getAppVersion()['appVersion'],
            'with_eval' => $withEval,
            'instance' => [
                'id' => $instance->getId(),
                'name1' => $instance->getName1(),
                'name2' => $instance->getName2(),
                'name3' => $instance->getName3(),
                'name4' => $instance->getName4(),
                'label1' => $instance->getLabel1(),
                'label2' => $instance->getLabel2(),
                'label3' => $instance->getLabel3(),
                'label4' => $instance->getLabel4(),
                'disponibility' => $instance->getAvailability(),
                'level' => $instance->getLevel(),
                'assetType' => $instance->getAssetType(),
                'exportable' => $instance->getExportable(),
                'position' => $instance->getPosition(),
                'c' => $withEval ? $instance->getConfidentiality() : -1,
                'i' => $withEval ? $instance->getIntegrity() : -1,
                'd' => $withEval ? $instance->getAvailability() : -1,
                'ch' => $withEval ? $instance->getInheritedConfidentiality() : 1,
                'ih' => $withEval ? $instance->getInheritedIntegrity() : 1,
                'dh' => $withEval ? $instance->getInheritedAvailability() : 1,
                'asset' => $instance->getAsset()->getUuid(),
                'object' => $instance->getObject()->getUuid(),
                'root' => 0,
                'parent' => $instance->getParent() ? $instance->getParent()->getId() : 0,
            ],
            // TODO: we don't need to pass anr param for the BackOffice export.
            'object' => $objectExportService->generateExportArray(
                $instance->getObject()->getUuid(),
                $instance->getAnr(),
                $withEval
            ),
        ];

        // Scales
        if ($withEval && $withScale) {
            $return['scales'] = $this->generateExportArrayOfScales($instance->getAnr());
            /** @var OperationalRiskScalesExportService $operationalRiskScalesExportService */
            $operationalRiskScalesExportService = $this->get('operationalRiskScalesExportService');
            $return['operationalRiskScales'] = $operationalRiskScalesExportService->generateExportArray(
                $instance->getAnr()
            );
        }

        // Instance risk
        $return['risks'] = [];
        // TODO: inject the table directly in the service.
        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('instanceRiskService')->get('table');
        $instanceRisks = $instanceRiskTable->findByInstance($instance);

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
            'uuid' => 'uuid',
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
            'a' => 'a',
            'status' => 'status',
        ];
        if ($withEval) {
            $treatsObj = array_merge(
                $treatsObj,
                [
                    'trend' => 'trend',
                    'comment' => 'comment',
                    'qualification' => 'qualification'
                ]
            );
        };
        $vulsObj = [
            'uuid' => 'uuid',
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
        foreach ($instanceRisks as $instanceRisk) {
            $riskIds[$instanceRisk->getId()] = $instanceRisk->getId();
            if (!$withEval) {
                $instanceRisk->set('vulnerabilityRate', -1);
                $instanceRisk->set('threatRate', -1);
                $instanceRisk->set('kindOfMeasure', 0);
                $instanceRisk->set('reductionAmount', 0);
                $instanceRisk->set('cacheMaxRisk', -1);
                $instanceRisk->set('cacheTargetedRisk', -1);
                $instanceRisk->set('comment', '');
                $instanceRisk->set('commentAfter', '');
                $instanceRisk->set('mh', 1);
            }
            if (!$withControls) {
                $instanceRisk->set('comment', '');
                $instanceRisk->set('commentAfter', '');
            }

            $instanceRisk->set('riskC', -1);
            $instanceRisk->set('riskI', -1);
            $instanceRisk->set('riskD', -1);
            $return['risks'][$instanceRisk->get('id')] = $instanceRisk->getJsonArray($instanceRiskArray);

            $irAmv = $instanceRisk->get('amv');
            $return['risks'][$instanceRisk->get('id')]['amv'] = is_null($irAmv) ? null : $irAmv->getUuid();
            if (!empty($return['risks'][$instanceRisk->get('id')]['amv'])
                && empty($return['amvs'][$instanceRisk->getAmv()->getUuid()])
            ) {
                [
                    $amv,
                    $threats,
                    $vulns,
                    $themes,
                    $measures] = $this->get('amvService')->generateExportArray(
                    $instanceRisk->get('amv'),
                    $instanceRisk->getAnr() !== null ? $instanceRisk->getAnr()->getId() : null,
                    $withEval
                );
                $return['amvs'][$instanceRisk->getAmv()->getUuid()] = $amv;
                if (empty($return['threats'])) {
                    $return['threats'] = [];
                }
                if (empty($return['vuls'])) {
                    $return['vuls'] = [];
                }
                if (empty($return['measures'])) {
                    $return['measures'] = [];
                }
                $return['threats'] += $threats;
                $return['vuls'] += $vulns;
                $return['measures'] += $measures;
            }

            $threat = $instanceRisk->getThreat();
            if (!empty($threat)) {
                if (empty($return['threats'][$threat->getUuid()])) {
                    $return['threats'][$instanceRisk->getThreat()->getUuid()] = $instanceRisk->get('threat')->getJsonArray($treatsObj);
                }
                $return['risks'][$instanceRisk->get('id')]['threat'] = $instanceRisk->getThreat()->getUuid();
            } else {
                $return['risks'][$instanceRisk->get('id')]['threat'] = null;
            }

            $vulnerability = $instanceRisk->get('vulnerability');
            if (!empty($vulnerability)) {
                if (empty($return['vuls'][$instanceRisk->getVulnerability()->getUuid()])) {
                    $return['vuls'][$instanceRisk->getVulnerability()->getUuid()] = $instanceRisk->get('vulnerability')->getJsonArray($vulsObj);
                }
                $return['risks'][$instanceRisk->get('id')]['vulnerability'] = $instanceRisk->getVulnerability()->getUuid();
            } else {
                $return['risks'][$instanceRisk->get('id')]['vulnerability'] = null;
            }

            $return['risks'][$instanceRisk->getId()]['context'] = $instanceRisk->getContext();
            $return['risks'][$instanceRisk->getId()]['riskOwner'] = $instanceRisk->getInstanceRiskOwner()
                ? $instanceRisk->getInstanceRiskOwner()->getName()
                : '';
        }

        // Operational instance risks.
        $return['risksop'] = $this->generateExportArrayOfOperationalInstanceRisks($instance, $withEval, $withControls);

        $return = array_merge($return, $this->generateExportArrayOfRecommendations(
            $instance,
            $withEval,
            $withRecommendations,
            $withUnlinkedRecommendations,
            $riskIds,
            !empty($return['risksop']) ? array_keys($return['risksop']) : []
        ));

        // Instance consequence
        if ($withEval) {
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
                'type' => 'type',
                'position' => 'position',
            ];
            $return['consequences'] = [];
            $instanceConseqTable = $this->get('instanceConsequenceService')->get('table');
            $instanceConseqResults = $instanceConseqTable->getRepository()
                ->createQueryBuilder('t')
                ->where("t.instance = :i")
                ->setParameter(':i', $instance->get('id'))->getQuery()->getResult();
            foreach ($instanceConseqResults as $ic) {
                $return['consequences'][$ic->get('id')] = $ic->getJsonArray($instanceConseqArray);
                $return['consequences'][$ic->get('id')]['scaleImpactType'] = $ic->get('scaleImpactType')->getJsonArray($scaleTypeArray);
                $return['consequences'][$ic->get('id')]['scaleImpactType']['scale'] = $ic->get('scaleImpactType')->get('scale')->get('id');
            }
        }

        /** @var InstanceSuperClass[] $childrenInstances */
        $childrenInstances = $instanceTable->getRepository()
            ->createQueryBuilder('t')
            ->where('t.parent = :p')
            ->setParameter(':p', $instance->get('id'))
            ->orderBy('t.position','ASC')->getQuery()->getResult();
        $return['children'] = [];
        $f = '';
        foreach ($childrenInstances as $i) {
            $return['children'][$i->get('id')] = $this->generateExportArray($i->get('id'), $f, $withEval, false, $withControls, $withRecommendations, $withUnlinkedRecommendations);
        }

        return $return;
    }

    /**
     * TODO: move to the FrontOffice as it is used only there.
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

    protected function generateExportArrayOfOperationalInstanceRisks(
        InstanceSuperClass $instance,
        bool $withEval,
        bool $withControls
    ): array {
        $result = [];

        /** @var InstanceRiskOpTable $instanceRiskOpTable */
        $instanceRiskOpTable = $this->get('instanceRiskOpTable');
        $operationalInstanceRisks = $instanceRiskOpTable->findByInstance($instance);
        foreach ($operationalInstanceRisks as $operationalInstanceRisk) {
            $operationalInstanceRiskId = $operationalInstanceRisk->getId();
            $result[$operationalInstanceRiskId] = [
                'id' => $operationalInstanceRiskId,
                'rolfRisk' => $operationalInstanceRisk->getRolfRisk()
                    ? $operationalInstanceRisk->getRolfRisk()->getId()
                    : null,
                'riskCacheLabel1' => $operationalInstanceRisk->getRiskCacheLabel(1),
                'riskCacheLabel2' => $operationalInstanceRisk->getRiskCacheLabel(2),
                'riskCacheLabel3' => $operationalInstanceRisk->getRiskCacheLabel(3),
                'riskCacheLabel4' => $operationalInstanceRisk->getRiskCacheLabel(4),
                'riskCacheDescription1' => $operationalInstanceRisk->getRiskCacheDescription(1),
                'riskCacheDescription2' => $operationalInstanceRisk->getRiskCacheDescription(2),
                'riskCacheDescription3' => $operationalInstanceRisk->getRiskCacheDescription(3),
                'riskCacheDescription4' => $operationalInstanceRisk->getRiskCacheDescription(4),
                'brutProb' => $withEval ? $operationalInstanceRisk->getBrutProb() : -1,
                'netProb' => $withEval ? $operationalInstanceRisk->getNetProb() : -1,
                'targetedProb' => $withEval ? $operationalInstanceRisk->getNetProb() : -1,
                'cacheBrutRisk' => $withEval ? $operationalInstanceRisk->getCacheBrutRisk() : -1,
                'cacheNetRisk' => $withEval ? $operationalInstanceRisk->getCacheNetRisk() : -1,
                'cacheTargetedRisk' => $withEval ? $operationalInstanceRisk->getCacheTargetedRisk() : -1,
                'kindOfMeasure' => $withEval
                    ? $operationalInstanceRisk->getKindOfMeasure()
                    : InstanceRiskOp::KIND_NOT_TREATED,
                'comment' => $withEval && $withControls ? $operationalInstanceRisk->getComment() : '',
                'mitigation' => $withEval ? $operationalInstanceRisk->getMitigation() : '',
                'specific' => $operationalInstanceRisk->getSpecific(),
                'context' => $operationalInstanceRisk->getContext(),
                'riskOwner' => $operationalInstanceRisk->getInstanceRiskOwner()
                    ? $operationalInstanceRisk->getInstanceRiskOwner()->getName()
                    : '',
            ];
            $result[$operationalInstanceRiskId]['scalesValues'] = [];
            if ($withEval) {
                foreach ($operationalInstanceRisk->getOperationalInstanceRiskScales() as $instanceRiskScale) {
                    $scaleType = $instanceRiskScale->getOperationalRiskScaleType();
                    $result[$operationalInstanceRiskId]['scalesValues'][$scaleType->getId()] = [
                        'operationalRiskScaleTypeId' => $scaleType->getId(),
                        'netValue' => $instanceRiskScale->getNetValue(),
                        'brutValue' => $instanceRiskScale->getBrutValue(),
                        'targetedValue' => $instanceRiskScale->getTargetedValue(),
                    ];
                }
            }
        }

        return $result;
    }

    private function generateExportArrayOfScales(AnrSuperClass $anr): array
    {
        $result = [];
        /** @var ScaleTable $scaleTable */
        $scaleTable = $this->get('scaleTable');
        $scales = $scaleTable->findByAnr($anr);
        foreach ($scales as $scale) {
            $result[$scale->getType()] = [
                'min' => $scale->getMin(),
                'max' => $scale->getMax(),
                'type' => $scale->getType(),
            ];
        }

        return $result;
    }

    protected function generateExportArrayOfRecommendations(
        InstanceSuperClass $instance,
        bool $withEval,
        bool $withRecommendations,
        bool $withUnlinkedRecommendations,
        array $riskIds,
        array $riskOpIds
    ): array {
        return [];
    }
}
