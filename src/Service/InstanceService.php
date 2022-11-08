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
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceConsequenceTable;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceRiskTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\ScaleImpactTypeTable;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Mapping\MappingException;
use Monarc\Core\Model\Table\ScaleTable;
use Monarc\Core\Table\MonarcObjectTable;

/**
 * TODO: this service cant work as far as the 'asset' and 'object' dependencies can't set up.
 *`
 * Instance Service
 *
 * Class InstanceService
 * @package Monarc\Core\Service
 */
class InstanceService extends AbstractService
{
    protected $dependencies = ['anr', '[parent](instance)', '[root](instance)'];
    protected $filterColumns = ['label1', 'label2', 'label3', 'label4'];

    // Tables & Entities
    protected $anrTable;
    protected $objectTable;
    protected $scaleTable;
    protected $scaleImpactTypeTable;
    protected $instanceConsequenceTable;
    protected $instanceConsequenceEntity;
    protected $instanceRiskTable;
    protected $instanceRiskOpTable;

    // Services
    protected $instanceConsequenceService;
    protected $instanceRiskService;
    protected $instanceRiskOpService;
    protected $translateService;
    protected $configService;
    protected $operationalRiskScalesExportService;
    protected $instanceMetadataExportService;

    // Export (Services)
    protected $objectExportService;
    protected $amvService;

    protected $forbiddenFields = ['anr', 'asset', 'object', 'ch', 'dh', 'ih'];

    // TODO: refactor all the service along with the method
    public function instantiateObjectToAnr(
        int $anrId,
        array $data,
        bool $managePosition = true,
        bool $rootLevel = false,
        int $mode = Instance::MODE_CREA_NODE
    ) {
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');
        /** @var MonarcObjectTable $objectTable */
        $objectTable = $this->get('objectTable');
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->findById($anrId);
        try {
            /** @var ObjectSuperClass $object */
            $object = $objectTable->findByUuid($data['object']);
        } catch (MappingException | QueryException $e) {
            $object = $objectTable->findByUuidAndAnr($data['object'], $anr);
        }

        if (!$object->hasAnrLink($anr)) {
            throw new Exception('The object is not linked to the anr', 412);
        }

        $data['anr'] = $anr;
        $data['name1'] = $object->getName(1);
        $data['name2'] = $object->getName(2);
        $data['name3'] = $object->getName(3);
        $data['name4'] = $object->getName(4);
        $data['label1'] = $object->getLabel(1);
        $data['label2'] = $object->getLabel(2);
        $data['label3'] = $object->getLabel(3);
        $data['label4'] = $object->getLabel(4);
        $data['asset'] = $object->getAsset();
        $data['object'] = $object;
        if (!empty($data['parent'])) {
            $data['parent'] = $data['parent'] instanceof InstanceSuperClass
                ? $data['parent']
                : $instanceTable->findById($data['parent']);
        } else {
            $data['parent'] = null;
        }

        $this->updateImpactsInherited($anrId, $data['parent'], $data);

        //manage position
        if (!$managePosition) {
            unset($data['implicitPosition'], $data['previous']);
        } elseif (isset($data['position'])) {
            $data['position']++;
            if ($data['position'] <= 1) {
                $data['implicitPosition'] = 1;
            } else {
                $return = $instanceTable->getRepository()->createQueryBuilder('t')
                    ->select('COUNT(t.id)');
                if (!empty($data['parent'])) {
                    $return = $return->where('t.parent = :parent')
                        ->setParameter(':parent', $data['parent']);
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
                    $return = $instanceTable->getRepository()->createQueryBuilder('t')
                        ->select('t.id');
                    if (!empty($data['parent'])) {
                        $return = $return->where('t.parent = :parent')
                            ->setParameter(':parent', $data['parent']);
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
            $instance->setDbAdapter($instanceTable->getDb());
            $instance->setLanguage($this->getLanguage());
            $instance->initParametersChanges();
        }
        $instance->exchangeArray($data, false);

        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($instance, $dependencies);

        $this->updateInstanceLevels($rootLevel, $instance, $mode);

        $instance->setCreator($this->getConnectedUser()->getEmail());

        $instanceTable->saveEntity($instance);

        //instanceMetadata, fetch value for global instance
        $this->updateInstanceMetadataFromBrothers($instance);

        //instances risk
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        $instanceRiskService->createInstanceRisks($instance, $instance->getAnr(), $object);

        //instances risks op
        /** @var InstanceRiskOpService $instanceRiskOpService */
        $instanceRiskOpService = $this->get('instanceRiskOpService');
        $instanceRiskOpService->createInstanceRisksOp($instance, $object);

        //instances consequences
        $instanceConsequence = $this->createInstanceConsequences($instance, $anr, $object);
        if ($instanceConsequence !== null) {
            $this->get('instanceConsequenceService')->updateInstanceImpacts($instanceConsequence);
        }

        // Check if the root element is not the same as current child element to avoid a circular dependency.
        if ($rootLevel
            || !$instance->hasParent()
            || $instance->getParent()->getRoot() === null
            || $instance->getParent()->getRoot()->getObject()->getUuid() !== $instance->getObject()->getUuid()
        ) {
            $this->createChildren($instance);
        }

        return $instance->getId();
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
                        ->setParameter(
                            ':pos',
                            $data['position'] + ($data['position'] < $instance->get('position') ? -1 : 0)
                        )
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

        if (isset($data['parent']) && (int)$id === $data['parent']) {
            throw new Exception('Instance can not be parent to itself.', 412);
        }

        $this->filterPatchFields($data);

        //retrieve instance
        /** @var InstanceTable $table */
        $table = $this->get('table');
        /** @var Instance $instance */
        $instance = $table->getEntity($id);
        if (!$instance) {
            throw new Exception('Instance does not exist.', 412);
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
                        ->setParameter(
                            ':pos',
                            $data['position'] + ($data['position'] < $instance->getPosition() || $data['parent']
                            != $instance->getParent() ? -1 : 0)
                        )
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
        $data['name1'] = $instance->getName(1);
        $data['label1'] = $instance->getLabel(1);

        unset($data['implicitPosition'], $data['previous'], $data['position']);

        $this->updateBrothers($anrId, $instance, $data, $historic);

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
     * Creates instances for each child.
     */
    protected function createChildren(InstanceSuperClass $parentInstance): void
    {
        foreach ($parentInstance->getObject()->getChildren() as $childObject) {
            $data = [
                'anr' => $parentInstance->getAnr(),
                'object' => $childObject->getUuid(),
                'parent' => $parentInstance,
                'position' => $childObject->getPosition(),
                'c' => -1,
                'i' => -1,
                'd' => -1,
            ];
            $this->instantiateObjectToAnr($parentInstance->getAnr()->getId(), $data, false);
        }
    }

    protected function updateInstanceLevels(bool $rootLevel, InstanceSuperClass $instance, int $mode)
    {
        if ($rootLevel || $mode === Instance::MODE_CREA_ROOT) {
            $instance->setLevel(Instance::LEVEL_ROOT);
        } elseif ($instance->getObject()->hasChildren()) {
            $instance->setLevel(Instance::LEVEL_INTER);
        } else {
            $instance->setLevel(Instance::LEVEL_LEAF);
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
        if (!$instance instanceof InstanceSuperClass) {
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
    protected function updateBrothers($anrId, InstanceSuperClass $instance, $data, &$historic)
    {
        $fieldsToDelete = ['parent', 'createdAt', 'creator', 'risks', 'oprisks', 'instances', 'position'];
        //if source object is global, reverberate to other instance with the same source object
        if ($instance->getObject()->isScopeGlobal()) {
            //retrieve instance with same object source
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('table');
            $brothers = $instanceTable->findByObject($instance->getObject());

            foreach ($brothers as $brother) {
                if ($brother->getId() !== $instance->getId() && !\in_array($brother->getId(), $historic, true)) {
                    foreach ($fieldsToDelete as $fieldToDelete) {
                        if (isset($data[$fieldToDelete])) {
                            unset($data[$fieldToDelete]);
                        }
                    }
                    $data['id'] = $brother->getId();
                    $data['c'] = $brother->getConfidentiality();
                    $data['i'] = $brother->getIntegrity();
                    $data['d'] = $brother->getAvailability();
                    //Unproper FIX to issue#31 to be reviewed when #7 fixed
                    $data['name' . $instance->getLanguage()] = $brother->getName($instance->getLanguage());
                    $data['label' . $instance->getLanguage()] = $brother->getLabel($instance->getLanguage());

                    if (isset($data['consequences'])) {
                        //retrieve instance consequence id for the brother instance id ans scale impact type
                        /** @var InstanceConsequenceTable $instanceConsequenceTable */
                        $instanceConsequenceTable = $this->get('instanceConsequenceTable');
                        $instanceConsequences = $instanceConsequenceTable->findByInstance($brother);
                        foreach ($instanceConsequences as $instanceConsequence) {
                            $scaleImpactType = $instanceConsequence->getScaleImpactType()->getType();
                            foreach ($data['consequences'] as $key => $dataConsequence) {
                                if ((int)$dataConsequence['scaleImpactType'] === $scaleImpactType) {
                                    $data['consequences'][$key]['id'] = $instanceConsequence->getId();
                                }
                            }
                        }
                    }

                    unset($data['parent']);

                    $this->updateInstance($anrId, $brother->getId(), $data, $historic);
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
                $instanceConsequenceService
                    ->patchConsequence($consequence['id'], $dataConsequences, $patchInstance, $fromInstance);

                $i++;
            }
        }
    }

    public function updateRisks(InstanceSuperClass $instance)
    {
        /** @var InstanceRiskService $instanceRiskService */
        $instanceRiskService = $this->get('instanceRiskService');
        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('instanceRiskTable');
        $instanceRisks = $instanceRiskTable->findByInstance($instance);

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

    public function getInstanceData($id, $anrId): array
    {
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');
        $instance = $instanceTable->findById($id);
        if ($instance->getAnr()->getId() !== $anrId) {
            throw new Exception(sprintf('The instance ID "%d" belongs to a different analysis.', $id));
        }

        $result = $instance->getJsonArray();

        /** @var InstanceConsequenceService $instanceConsequenceService */
        $instanceConsequenceService = $this->get('instanceConsequenceService');
        $result['consequences'] = $instanceConsequenceService->getConsequences($instance);
        $result['instances'] = $this->getOtherInstances($instance);

        return $result;
    }

    public function getInstancesData(int $anrId): array
    {
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->findById($anrId);
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');
        $allInstances = $instanceTable->findByAnrAndOrderByParams(
            $anr,
            ['i.parent' => 'DESC', 'i.position' => 'ASC']
        );

        $instances = $temp = [];
        foreach ($allInstances as $instance) {
            $instanceArray = $instance->getJsonArray();
            $instanceArray['scope'] = $instance->getObject()->getScope();
            $instanceArray['child'] = [];
            $instanceArray['parent'] = $instance->getParent() === null ? 0 : $instance->getParent()->getId();

            $instances[$instanceArray['parent']][$instanceArray['id']] = $instanceArray;
            if ($instance->getParent() === null) {
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
                        $instances[$current['parent']][$current['id']]['child'] = array_values(
                            $instances[$current['parent']][$current['id']]['child']
                        );
                    }
                }
            }
        }
        return isset($instances[0]) ? array_values($instances[0]) : [];
    }

    /**
     * TODO: move to InstanceConsequenceService.
     */
    public function createInstanceConsequences(
        InstanceSuperClass $instance,
        AnrSuperClass $anr,
        ObjectSuperClass $object
    ): ?InstanceConsequenceSuperClass {
        $instanceConsequenceEntity = null;

        if ($object->isScopeGlobal()) {
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('table');
            $brothers = $instanceTable->findByAnrAndObject($anr, $object);
        }

        if (!empty($brothers) && $object->isScopeGlobal()) {
            $refInstance = null;
            foreach ($brothers as $brother) {
                if ($brother->getId() !== $instance->getId()) {
                    $refInstance = $brother;
                    break;
                }
            }

            if ($refInstance !== null) {
                /** @var InstanceConsequenceTable $instanceConsequenceTable */
                $instanceConsequenceTable = $this->get('instanceConsequenceTable');
                $instancesConsequences = $instanceConsequenceTable->findByAnrInstance($anr, $refInstance);

                foreach ($instancesConsequences as $instanceConsequence) {
                    $data = [
                        'anr' => $anr,
                        'instance' => $instance,
                        'object' => $object,
                        'scaleImpactType' => $instanceConsequence->getScaleImpactType(),
                        'isHidden' => $instanceConsequence->isHidden(),
                        'locallyTouched' => $instanceConsequence->getLocallyTouched(),
                        'c' => $instanceConsequence->getConfidentiality(),
                        'i' => $instanceConsequence->getIntegrity(),
                        'd' => $instanceConsequence->getAvailability(),
                    ];

                    $class = $this->get('instanceConsequenceEntity');
                    /** @var InstanceConsequenceSuperClass $instanceConsequenceEntity */
                    $instanceConsequenceEntity = new $class();
                    $instanceConsequenceEntity->exchangeArray($data);
                    $instanceConsequenceEntity->setCreator($this->getConnectedUser()->getEmail());

                    $instanceConsequenceTable->save($instanceConsequenceEntity, false);
                }
                $instanceConsequenceTable->getDb()->flush();
            }
        } else {
            /** @var ScaleImpactTypeTable $scaleImpactTypeTable */
            $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
            $scalesImpactTypes = $scaleImpactTypeTable->findByAnr($anr);

            /** @var InstanceConsequenceTable $instanceConsequenceTable */
            $instanceConsequenceTable = $this->get('instanceConsequenceTable');

            foreach ($scalesImpactTypes as $scalesImpactType) {
                $data = [
                    'anr' => $anr,
                    'instance' => $instance,
                    'object' => $object,
                    'scaleImpactType' => $scalesImpactType,
                    'isHidden' => $scalesImpactType->isHidden(),
                ];
                $class = $this->get('instanceConsequenceEntity');
                /** @var InstanceConsequenceSuperClass $instanceConsequenceEntity */
                $instanceConsequenceEntity = new $class();
                $instanceConsequenceEntity->exchangeArray($data);
                $instanceConsequenceEntity->setCreator($this->getConnectedUser()->getEmail());

                $instanceConsequenceTable->save($instanceConsequenceEntity, false);
            }
            $instanceConsequenceTable->getDb()->flush();
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

        $filename = preg_replace("/[^a-z0-9\._-]+/i", '', $instance->getName($this->getLanguage()));

        // TODO: ObjectExportService can be a class from client or core.
        /** @var ObjectExportService $objectExportService */
        $objectExportService = $this->get('objectExportService');
        $return = [
            'type' => 'instance',
            'monarc_version' => $this->get('configService')->getAppVersion()['appVersion'],
            'with_eval' => $withEval,
            'instance' => [
                'id' => $instance->getId(),
                'name1' => $instance->getName(1),
                'name2' => $instance->getName(2),
                'name3' => $instance->getName(3),
                'name4' => $instance->getName(4),
                'label1' => $instance->getLabel(1),
                'label2' => $instance->getLabel(2),
                'label3' => $instance->getLabel(3),
                'label4' => $instance->getLabel(4),
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

        $instanceMetadataExportService = $this->get('instanceMetadataExportService');
        $return['instanceMetadata'] = $instanceMetadataExportService->generateExportArray($instance->getAnr());
        if ($withEval) {
            $return['instanceMetadata'] = $this->generateExportArrayOfInstanceMetadata($instance);
        }

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
        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('instanceRiskTable');
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
                [$amv, $threats, $vulns, $themes, $measures] = $this->get('amvService')->generateExportArray(
                    $instanceRisk->getAmv(),
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
                    // TODO: we can't do getJsonArray anymore.
                    $return['threats'][$instanceRisk->getThreat()->getUuid()] =
                        $instanceRisk->get('threat')->getJsonArray($treatsObj);
                }
                $return['risks'][$instanceRisk->get('id')]['threat'] = $instanceRisk->getThreat()->getUuid();
            } else {
                $return['risks'][$instanceRisk->get('id')]['threat'] = null;
            }

            $vulnerability = $instanceRisk->get('vulnerability');
            if (!empty($vulnerability)) {
                // TODO: we can't do getJsonArray anymore.
                if (empty($return['vuls'][$instanceRisk->getVulnerability()->getUuid()])) {
                    $return['vuls'][$instanceRisk->getVulnerability()->getUuid()] =
                        $instanceRisk->get('vulnerability')->getJsonArray($vulsObj);
                }
                $return['risks'][$instanceRisk->get('id')]['vulnerability'] =
                    $instanceRisk->getVulnerability()->getUuid();
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
                $return['consequences'][$ic->get('id')]['scaleImpactType'] =
                    $ic->get('scaleImpactType')->getJsonArray($scaleTypeArray);
                $return['consequences'][$ic->get('id')]['scaleImpactType']['scale'] =
                    $ic->get('scaleImpactType')->get('scale')->get('id');
            }
        }

        /** @var InstanceSuperClass[] $childrenInstances */
        $childrenInstances = $instanceTable->getRepository()
            ->createQueryBuilder('t')
            ->where('t.parent = :p')
            ->setParameter(':p', $instance->get('id'))
            ->orderBy('t.position', 'ASC')->getQuery()->getResult();
        $return['children'] = [];
        $f = '';
        foreach ($childrenInstances as $i) {
            $return['children'][$i->get('id')] = $this->generateExportArray(
                $i->getId(),
                $f,
                $withEval,
                false,
                $withControls,
                $withRecommendations,
                $withUnlinkedRecommendations
            );
        }

        return $return;
    }

    protected function getOtherInstances(InstanceSuperClass $instance): array
    {
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('table');
        $otherInstances = $instanceTable->findByAnrAndObject($instance->getAnr(), $instance->getObject());
        $names = [
            'name1' => $instance->getAnr()->getLabelByLanguageIndex(1),
            'name2' => $instance->getAnr()->getLabelByLanguageIndex(2),
            'name3' => $instance->getAnr()->getLabelByLanguageIndex(3),
            'name4' => $instance->getAnr()->getLabelByLanguageIndex(4),
        ];
        $instances = [];
        foreach ($otherInstances as $otherInstance) {
            foreach ($otherInstance->getHierarchyArray() as $instanceFromTheTree) {
                $names['name1'] .= ' > ' . $instanceFromTheTree['name1'];
                $names['name2'] .= ' > ' . $instanceFromTheTree['name2'];
                $names['name3'] .= ' > ' . $instanceFromTheTree['name3'];
                $names['name4'] .= ' > ' . $instanceFromTheTree['name4'];
            }

            $names['id'] = $otherInstance->getId();
            $instances[] = $names;
        }

        return $instances;
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
                'targetedProb' => $withEval ? $operationalInstanceRisk->getTargetedProb() : -1,
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

    protected function generateExportArrayOfInstanceMetadata(InstanceSuperClass $instance): array
    {
        return [];
    }

    protected function updateInstanceMetadataFromBrothers(InstanceSuperClass $instance): void
    {
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
