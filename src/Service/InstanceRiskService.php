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
use Monarc\Core\Model\Entity\InstanceRiskSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Table\AmvTable;
use Monarc\Core\Model\Table\InstanceRiskTable;
use Monarc\Core\Model\Table\InstanceRiskOwnerTable;
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
    protected $recommandationTable;
    protected $recommandationRiskTable;

    // only for setDependencies (deprecated)
    protected $assetTable;
    protected $MonarcObjectTable;
    protected $scaleTable;
    protected $threatTable;
    protected $vulnerabilityTable;

    protected $forbiddenFields = ['anr', 'amv', 'asset', 'threat', 'vulnerability'];

    public function createInstanceRisks(
        InstanceSuperClass $instance,
        AnrSuperClass $anr,
        ObjectSuperClass $object
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

    /**
     * @return InstanceRiskSuperClass[]
     */
    public function getInstanceRisks(InstanceSuperClass $instance)
    {
        /** @var InstanceRiskTable $table */
        $table = $this->get('table');

        return $table->findByInstance($instance);
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data, $manageGlobal = true)
    {
        $initialData = $data;
        $anrId = $data['anr'];

        if(isset($data['threatRate'])){
            $data['threatRate'] = trim($data['threatRate']);
            if(empty($data['threatRate']) || $data['threatRate'] == '-' || $data['threatRate'] == -1){
                $data['threatRate'] = -1;
            }
        }
        if(isset($data['vulnerabilityRate'])){
            $data['vulnerabilityRate'] = trim($data['vulnerabilityRate']);
            if(empty($data['vulnerabilityRate']) || $data['vulnerabilityRate'] == '-' || $data['vulnerabilityRate'] == -1){
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
            /** @var ObjectSuperClass $object */
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

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
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
         if (isset($data['owner'])) {
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

    protected function duplicateRecommendationRisk(
        InstanceRiskSuperClass $instanceRisk,
        InstanceRiskSuperClass $newInstanceRisk
    ): void {}

    /**
     * @param string $riskOwnerName
     * @param InstanceRisk $instanceRisk
     *
     * @return string
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function processRiskOwnerName(string $riskOwnerName, InstanceRisk $instanceRisk): void
    {

        $this->instanceRiskOwnerTable = $this->get('instanceRiskOwnerTable');

        if (empty($riskOwnerName)) {
                // delete the InstanceRiskOwner object
                if ($instanceRisk->getOwner()) {
                    $this->instanceRiskOwnerTable->remove($instanceRisk->getOwner());
                }
                // unset the owner of the instance risk
                $instanceRisk->setOwner(null);

        } else {

            $instanceRiskOwner = $this->instanceRiskOwnerTable->findByAnrAndName(
                $instanceRisk->getAnr(),
                $riskOwnerName
            );
            if ($instanceRiskOwner === null) {
                $instanceRiskOwner = (new InstanceRiskOwner())
                    ->setAnr($instanceRisk->getAnr())
                    ->setName($riskOwnerName)
                    ->setCreator($this->connectedUser->getEmail());

                $this->instanceRiskOwnerTable->save($instanceRiskOwner, false);

                $instanceRisk->setOwner($instanceRiskOwner);
            } elseif ($instanceRisk->getOwner() !== $instanceRiskOwner) {
                $instanceRisk->setOwner($instanceRiskOwner);
            }
        }
    }
}
