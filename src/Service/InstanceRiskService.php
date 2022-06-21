<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\InstanceRiskOwner;
use Monarc\Core\Model\Entity\InstanceRiskOwnerSuperClass;
use Monarc\Core\Model\Entity\InstanceRiskSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Table\AmvTable;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceRiskTable;
use Monarc\Core\Table\InstanceRiskOwnerTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Traits\RiskTrait;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Mapping\MappingException;

/**
 * Instance Risk Service
 *
 * Class InstanceRiskService
 * @package Monarc\Core\Service
 */
class InstanceRiskService extends AbstractService
{
    use RiskTrait;

    protected $dependencies = ['anr', 'amv', 'asset', 'instance', 'threat', 'vulnerability'];

    protected $anrTable;
    protected $userAnrTable;
    protected $amvTable;
    protected $instanceTable;
    protected $instanceRiskOwnerTable;

    // only for setDependencies (deprecated)
    protected $assetTable;
    protected $monarcObjectTable;
    protected $scaleTable;
    protected $threatTable;

    protected $forbiddenFields = ['anr', 'amv', 'asset', 'threat', 'vulnerability'];

    protected array $cachedData = [];

    public function createInstanceRisks(
        InstanceSuperClass $instance,
        AnrSuperClass $anr,
        ObjectSuperClass $object,
        array $params = []
    ): void {
        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');

        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $otherInstance = $instanceTable->findOneByAnrAndObjectExcludeInstance($anr, $object, $instance);

        if ($otherInstance !== null && $object->isScopeGlobal()) {
            foreach ($instanceRiskTable->findByInstance($otherInstance) as $instanceRisk) {
                $newInstanceRisk = (clone $instanceRisk)
                    ->setId(null)
                    ->setAnr($instance->getAnr())
                    ->setInstance($instance)
                    ->setCreator(
                        $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
                    );
                $instanceRiskTable->saveEntity($newInstanceRisk, false);

                $this->duplicateRecommendationRisk($instanceRisk, $newInstanceRisk);
            }
        } else {
            /** @var AmvTable $amvTable */
            $amvTable = $this->get('amvTable');
            $amvs = $amvTable->findByAsset($object->getAsset());
            foreach ($amvs as $amv) {
                $instanceRiskEntityClassName = $instanceRiskTable->getEntityClass();
                /** @var InstanceRiskSuperClass $instanceRisk */
                $instanceRisk = new $instanceRiskEntityClassName();
                $instanceRisk->setAnr($anr)
                    ->setAmv($amv)
                    ->setAsset($amv->getAsset())
                    ->setInstance($instance)
                    ->setThreat($amv->getThreat())
                    ->setVulnerability($amv->getVulnerability());

                /* Set risk owner and context during the import. */
                if (!empty($params['risks'])) {
                    $riskKey = array_search($amv->getUuid(), array_column($params['risks'], 'amv'), true);
                    if ($riskKey !== false) {
                        $instanceRiskData = array_values($params['risks'])[$riskKey];
                        $instanceRisk->setContext($instanceRiskData['context'] ?? '');
                        if (!empty($instanceRiskData['riskOwner'])) {
                            $instanceRiskOwner = $this->getOrCreateInstanceRiskOwner(
                                $anr,
                                $instanceRiskData['riskOwner']
                            );
                            $instanceRisk->setInstanceRiskOwner($instanceRiskOwner);
                        }
                    }
                }

                $instanceRiskTable->saveEntity($instanceRisk, false);

                $this->updateRisks($instanceRisk, false);
            }
        }

        $instanceRiskTable->getDb()->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteInstanceRisks(InstanceSuperClass $instance): void
    {
        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');
        $instanceRisks = $instanceRiskTable->findByInstance($instance);
        foreach ($instanceRisks as $instanceRisk) {
            $instanceRiskTable->deleteEntity($instanceRisk, false);
        }
        $instanceRiskTable->getDb()->flush();
    }

    public function getInstanceRisks(int $anrId, ?int $instanceId, array $params = []): array
    {
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->findById($anrId);

        if ($instanceId !== null) {
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('instanceTable');
            $instance = $instanceTable->findById($instanceId);

            if ($instance->getAnr()->getId() !== $anrId) {
                throw new Exception('Anr ID and instance anr ID are different', 412);
            }

            $instanceTable->initTree($instance);
            $params['instanceIds'] = $this->extractInstancesAndTheirChildrenIds([$instance->getId() => $instance]);
        }

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');

        $languageIndex = $this->getLanguageIndex($anr);

        $instanceRisks = $instanceRiskTable
            ->findInstancesRisksByParams($anr, $languageIndex, $params);

        $result = [];
        foreach ($instanceRisks as $instanceRisk) {
            $object = $instanceRisk->getInstance()->getObject();
            $threat = $instanceRisk->getThreat();
            $vulnerability = $instanceRisk->getVulnerability();
            $key = 'r' . $instanceRisk->getId();
            $isInstanceRiskHasToBeSet = true;
            if ($object->isScopeGlobal()) {
                $key = 'o' . $object->getUuid() . '-' . $threat->getUuid() . '-' . $vulnerability->getUuid();
                if (isset($result[$key])) {
                    $isInstanceRiskHasToBeSet = $this->shouldInstanceRiskBeAddedToResults($instanceRisk, $result[$key]);
                }
            }
            if (!$object->isScopeGlobal() || $isInstanceRiskHasToBeSet) {
                $result[$key] = $this->addCustomFieldsToInstanceRiskResult($instanceRisk, [
                    'id' => $instanceRisk->getId(),
                    'oid' => $object->getUuid(),
                    'instance' => $instanceRisk->getInstance()->getId(),
                    'amv' => $instanceRisk->getAmv() ? $instanceRisk->getAmv()->getUuid() : null,
                    'asset' => $instanceRisk->getAsset()->getUuid(),
                    'assetLabel' . $languageIndex => $instanceRisk->getAsset()->getLabel($languageIndex),
                    'assetDescription' . $languageIndex => $instanceRisk->getAsset()->getDescription($languageIndex),
                    'threat' => $threat->getUuid(),
                    'threatCode' => $threat->getCode(),
                    'threatLabel' . $languageIndex => $threat->getLabel($languageIndex),
                    'threatDescription' . $languageIndex => $threat->getDescription($languageIndex),
                    'threatRate' => $instanceRisk->getThreatRate(),
                    'vulnerability' => $vulnerability->getUuid(),
                    'vulnCode' => $vulnerability->getCode(),
                    'vulnLabel' . $languageIndex => $vulnerability->getLabel($languageIndex),
                    'vulnDescription' . $languageIndex => $vulnerability->getDescription($languageIndex),
                    'vulnerabilityRate' => $instanceRisk->getVulnerabilityRate(),
                    'context' => $instanceRisk->getContext(),
                    'owner' => $instanceRisk->getInstanceRiskOwner()
                        ? $instanceRisk->getInstanceRiskOwner()->getName()
                        : '',
                    'specific' => $instanceRisk->getSpecific(),
                    'reductionAmount' => $instanceRisk->getReductionAmount(),
                    'c_impact' => $instanceRisk->getInstance()->getConfidentiality(),
                    'c_risk' => $instanceRisk->getRiskConfidentiality(),
                    'c_risk_enabled' => $threat->getConfidentiality(),
                    'i_impact' => $instanceRisk->getInstance()->getIntegrity(),
                    'i_risk' => $instanceRisk->getRiskIntegrity(),
                    'i_risk_enabled' => $threat->getIntegrity(),
                    'd_impact' => $instanceRisk->getInstance()->getAvailability(),
                    'd_risk' => $instanceRisk->getRiskAvailability(),
                    'd_risk_enabled' => $threat->getAvailability(),
                    'target_risk' => $instanceRisk->getCacheTargetedRisk(),
                    'max_risk' => $instanceRisk->getCacheMaxRisk(),
                    'comment' => $instanceRisk->getComment(),
                    'scope' => $object->getScope(),
                    'kindOfMeasure' => $instanceRisk->getKindOfMeasure(),
                    't' => $instanceRisk->isTreated(),
                    'tid' => $threat->getUuid(),
                    'vid' => $vulnerability->getUuid(),
                    'instanceName' . $languageIndex => $instanceRisk->getInstance()->getName($languageIndex),
                ]);
            }
        }

        return array_values($result);
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data, $manageGlobal = true)
    {
        $initialData = $data;
        $anrId = $data['anr'];

        if (isset($data['threatRate'])) {
            $data['threatRate'] = trim($data['threatRate']);
            if (empty($data['threatRate']) || $data['threatRate'] === '-' || (int)$data['threatRate'] === -1) {
                $data['threatRate'] = -1;
            }
        }
        if (isset($data['vulnerabilityRate'])) {
            $data['vulnerabilityRate'] = trim($data['vulnerabilityRate']);
            if (empty($data['vulnerabilityRate'])
                || $data['vulnerabilityRate'] === '-'
                || (int)$data['vulnerabilityRate'] === -1
            ) {
                $data['vulnerabilityRate'] = -1;
            }
        }

        //security
        $this->filterPatchFields($data);

        $this->verifyRates($anrId, $data, $this->getEntity($id));

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');
        $instanceRisk = $instanceRiskTable->findById($id);

        //if object is global, impact modifications to brothers
        if ($manageGlobal) {
            $object = $instanceRisk->getInstance()->getObject();
            if ($object->getScope() === MonarcObject::SCOPE_GLOBAL) {
                //retrieve brothers instances
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                try {
                    $instances = $instanceTable->getEntityByFields([
                        'anr' => $instanceRisk->getAnr()->getId(),
                        'object' => $object->getUuid(),
                    ]);
                } catch (QueryException | MappingException $e) {
                    $instances = $instanceTable->getEntityByFields([
                        'anr' => $instanceRisk->getAnr()->getId(),
                        'object' => [
                            'anr' => $instanceRisk->getAnr()->getId(),
                            'uuid' => $object->getUuid(),
                        ]
                    ]);
                }

                /** @var Instance $instance */
                foreach ($instances as $instance) {
                    if ($instance != $instanceRisk->getInstance()) {
                        $instancesRisks = $instanceRiskTable->getEntityByFields([
                            'amv' => [
                                'anr' => $instanceRisk->getAnr()->getId(),
                                'uuid' => $instanceRisk->getAmv()->getUuid()
                            ],
                            'instance' => $instance->getId()
                        ]);
                        foreach ($instancesRisks as $instanceRisk) {
                            $initialData['id'] = $instanceRisk->getId();
                            $initialData['instance'] = $instance->getId();
                            $this->patch($instanceRisk->getId(), $initialData, false);
                        }
                    }
                }
            }
        }

        $instanceRisk->setLanguage($this->getLanguage());

        foreach ($this->dependencies as $dependency) {
            if (!isset($data[$dependency])) {
                $data[$dependency] = $instanceRisk->$dependency->id;
            }
        }

        $instanceRisk->exchangeArray($data, true);

        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($instanceRisk, $dependencies);

        $instanceRisk->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $instanceRiskTable->saveEntity($instanceRisk);

        $this->updateRisks($instanceRisk);

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data, $manageGlobal = true)
    {
        $initialData = $data;
        $anrId = $data['anr'] ?? null;

        if (isset($data['threatRate'])) {
            $data['threatRate'] = trim($data['threatRate']);
            if (!isset($data['threatRate']) || $data['threatRate'] == '-' || $data['threatRate'] == -1) {
                $data['threatRate'] = -1;
            }
        }
        if (isset($data['vulnerabilityRate'])) {
            $data['vulnerabilityRate'] = trim($data['vulnerabilityRate']);
            if ($data['vulnerabilityRate'] == '-' || $data['vulnerabilityRate'] == -1) {
                $data['vulnerabilityRate'] = -1;
            }
        }

        $this->verifyRates($anrId, $data, $this->getEntity($id));

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');
        $instanceRisk = $instanceRiskTable->findById($id);

        //if object is global, impact modifications to brothers
        if ($manageGlobal) {
            $object = $instanceRisk->getInstance()->getObject();
            if ($object->getScope() === MonarcObject::SCOPE_GLOBAL) {
                //retrieve brothers instances
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                try {
                    $instances = $instanceTable->getEntityByFields([
                        'anr' => $instanceRisk->getAnr()->getId(),
                        'object' => $object->getUuid(),
                    ]);
                } catch (QueryException | MappingException $e) {
                    $instances = $instanceTable->getEntityByFields([
                        'anr' => $instanceRisk->getAnr()->getId(),
                        'object' => [
                            'anr' => $instanceRisk->getAnr()->getId(),
                            'uuid' => $object->getUuid(),
                        ]
                    ]);
                }

                /** @var Instance $instance */
                foreach ($instances as $instance) {
                    $instancesRisks = $instanceRiskTable->findByInstanceAndInstanceRiskRelations(
                        $instance,
                        $instanceRisk
                    );

                    foreach ($instancesRisks as $instanceRisk) {
                        $initialData['id'] = $instanceRisk->getId();
                        $initialData['instance'] = $instance->getId();
                        $this->update($instanceRisk->getId(), $initialData, false);
                    }
                }
            }
        }

        $this->filterPostFields($data, $instanceRisk);

        $instanceRisk->setDbAdapter($instanceRiskTable->getDb());
        $instanceRisk->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new Exception('Data missing', 412);
        }
        if (\array_key_exists('owner', $data)) {
            $this->processRiskOwnerName((string)$data['owner'], $instanceRisk);
            unset($data['owner']);
        }

        unset($data['instance']);
        $instanceRisk->exchangeArray($data);


        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($instanceRisk, $dependencies);

        $instanceRisk->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $instanceRiskTable->saveEntity($instanceRisk);

        $this->updateRisks($instanceRisk);

        return $id;
    }

    /**
     * Update the specified instance risk
     * @param bool $last If set to false, database flushes will be suspended until a call to this method with "true"
     */
    public function updateRisks(InstanceRiskSuperClass $instanceRisk, bool $last = true)
    {
        $instance = $instanceRisk->getInstance();

        $riskConfidentiality = $this->getRiskC(
            $instance->getConfidentiality(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );
        $riskIntegrity = $this->getRiskI(
            $instance->getIntegrity(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );
        $riskAvailability = $this->getRiskD(
            $instance->getAvailability(),
            $instanceRisk->getThreatRate(),
            $instanceRisk->getVulnerabilityRate()
        );

        $instanceRisk
            ->setRiskConfidentiality($riskConfidentiality)
            ->setRiskIntegrity($riskIntegrity)
            ->setRiskAvailability($riskAvailability);

        $risks = [];
        $impacts = [];

        if ($instanceRisk->getThreat()->getConfidentiality()) {
            $risks[] = $riskConfidentiality;
            $impacts[] = $instance->getConfidentiality();
        }
        if ($instanceRisk->getThreat()->getIntegrity()) {
            $risks[] = $riskIntegrity;
            $impacts[] = $instance->getIntegrity();
        }
        if ($instanceRisk->getThreat()->getAvailability()) {
            $risks[] = $riskAvailability;
            $impacts[] = $instance->getAvailability();
        }

        $instanceRisk->setCacheMaxRisk(!empty($risks) ? max($risks) : -1);
        $instanceRisk->setCacheTargetedRisk(
            $this->getTargetRisk(
                $impacts,
                $instanceRisk->getThreatRate(),
                $instanceRisk->getVulnerabilityRate(),
                $instanceRisk->getReductionAmount()
            )
        );

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');
        $instanceRiskTable->saveEntity($instanceRisk, $last);
    }

    public function getOrCreateInstanceRiskOwner(AnrSuperClass $anr, string $ownerName): InstanceRiskOwnerSuperClass
    {
        if (!isset($this->cachedData['instanceRiskOwners'][$ownerName])) {
            $instanceRiskOwner = $this->instanceRiskOwnerTable->findByAnrAndName($anr, $ownerName);
            if ($instanceRiskOwner === null) {
                $instanceRiskOwner = $this->createInstanceRiskOwnerObject($anr, $ownerName);

                $this->instanceRiskOwnerTable->save($instanceRiskOwner, false);
            }

            $this->cachedData['instanceRiskOwners'][$ownerName] = $instanceRiskOwner;
        }

        return $this->cachedData['instanceRiskOwners'][$ownerName];
    }

    protected function duplicateRecommendationRisk(
        InstanceRiskSuperClass $instanceRisk,
        InstanceRiskSuperClass $newInstanceRisk
    ): void {
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function processRiskOwnerName(
        string $ownerName,
        InstanceRiskSuperClass $instanceRisk
    ): void {
        if (empty($ownerName)) {
            $instanceRisk->setInstanceRiskOwner(null);
        } else {
            /** @var InstanceRiskOwnerTable $instanceRiskOwnerTable */
            $instanceRiskOwnerTable = $this->get('instanceRiskOwnerTable');

            $instanceRiskOwner = $instanceRiskOwnerTable->findByAnrAndName(
                $instanceRisk->getAnr(),
                $ownerName
            );
            if ($instanceRiskOwner === null) {
                $instanceRiskOwner = $this->createInstanceRiskOwnerObject($instanceRisk->getAnr(), $ownerName);

                $this->instanceRiskOwnerTable->save($instanceRiskOwner, false);

                $instanceRisk->setInstanceRiskOwner($instanceRiskOwner);
            } elseif ($instanceRisk->getInstanceRiskOwner() === null
                || $instanceRisk->getInstanceRiskOwner()->getId() !== $instanceRiskOwner->getId()
            ) {
                $instanceRisk->setInstanceRiskOwner($instanceRiskOwner);
            }
        }
    }

    protected function createInstanceRiskOwnerObject(AnrSuperClass $anr, string $ownerName): InstanceRiskOwnerSuperClass
    {
        return (new InstanceRiskOwner())
            ->setAnr($anr)
            ->setName($ownerName)
            ->setCreator($this->getConnectedUser()->getEmail());
    }

    protected function getLanguageIndex(AnrSuperClass $anr): int
    {
        return $this->getConnectedUser()->getLanguage();
    }

    protected function addCustomFieldsToInstanceRiskResult(
        InstanceRiskSuperClass $instanceRisk,
        array $instanceRiskResult
    ): array {
        return $instanceRiskResult;
    }

    /**
     * @param Instance[] $instances
     *
     * @return array
     */
    private function extractInstancesAndTheirChildrenIds(array $instances): array
    {
        $instancesIds = [];
        foreach ($instances as $instanceId => $instance) {
            $instancesIds[] = $instanceId;
            $instancesIds = array_merge(
                $instancesIds,
                $this->extractInstancesAndTheirChildrenIds($instance->getParameterValues('children'))
            );
        }

        return $instancesIds;
    }

    /**
     * Determines whether the instance risk should be added to the list result in case. Only for global objects.
     *
     * @param InstanceRiskSuperClass $instanceRisk
     * @param array $valuesToCompare
     *
     * @return bool
     */
    private function shouldInstanceRiskBeAddedToResults(
        InstanceRiskSuperClass $instanceRisk,
        array $valuesToCompare
    ): bool {
        $instance = $instanceRisk->getInstance();
        $isMaxRiskSet = false;
        foreach ($instance->getInstanceRisks() as $instanceRiskToValidate) {
            if ($instanceRiskToValidate->getCacheMaxRisk() !== -1) {
                $isMaxRiskSet = true;
                break;
            }
        }
        if ($isMaxRiskSet) {
            return $valuesToCompare['max_risk'] < $instanceRisk->getCacheMaxRisk();
        }

        /* We compare CIA criteria in case if max risk value is not set. */
        $maxExistedCia = max($valuesToCompare['c_impact'], $valuesToCompare['i_impact'], $valuesToCompare['d_impact']);
        $maxCurrentCia = max($instance->getConfidentiality(), $instance->getIntegrity(), $instance->getAvailability());
        if ($maxExistedCia === $maxCurrentCia) {
            $sumExistedCia = $valuesToCompare['c_impact'] + $valuesToCompare['i_impact'] + $valuesToCompare['d_impact'];
            $sumCurrentCia = $instance->getConfidentiality() + $instance->getIntegrity() + $instance->getAvailability();

            return $sumExistedCia < $sumCurrentCia;
        }

        return $maxExistedCia < $maxCurrentCia;
    }
}
