<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Amv;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\InstanceRisk;
use Monarc\Core\Model\Entity\InstanceRiskSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Table\AmvTable;
use Monarc\Core\Model\Table\InstanceRiskTable;
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
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $otherInstances = $instanceTable->findByAnrAndObject($anr, $object);

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');

        if ($object->getScope() === MonarcObject::SCOPE_GLOBAL && \count($otherInstances) > 1) {
            foreach ($otherInstances as $otherInstance) {
                if ($otherInstance->getId() === $instance->getId()) {
                    continue;
                }

                $instancesRisks = $instanceRiskTable->getEntityByFields(['instance' => $otherInstance->getId()]);
                foreach ($instancesRisks as $instanceRisk) {
                    /** @var InstanceRiskSuperClass $newInstanceRisk */
                    $newInstanceRisk = (clone $instanceRisk)
                        ->setId(null)
                        ->setAnr($instance->getAnr())
                        ->setInstance($instance)
                        ->setCreator(
                            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
                        );
                    $instanceRiskTable->saveEntity($newInstanceRisk, false);

                    // This part of the code is related to the FO. Needs to be extracted.
                    if ($this->get('recommandationRiskTable') !== null) {
                        /** @var RecommandationRiskTable $recommandationRiskTable */
                        $recommandationRiskTable = $this->get('recommandationRiskTable');
                        $recoRisks = $this->get('recommandationRiskTable')->getEntityByFields([
                            'anr' => $anr->getId(),
                            'instanceRisk' => $instanceRisk->id
                        ]);
                        if (\count($recoRisks)) {
                            foreach ($recoRisks as $recoRisk) {
                                /** @var RecommandationRisk $newRecoRisk */
                                $newRecoRisk = clone $recoRisk;
                                $newRecoRisk->setId(null);
                                $newRecoRisk->setInstance($instance);
                                $newRecoRisk->setInstanceRisk($newInstanceRisk);
                                $recommandationRiskTable->saveEntity($newRecoRisk, false);
                            }
                        }
                    }
                }

                break;
            }
        } else {
            /** @var AmvTable $amvTable */
            $amvTable = $this->get('amvTable');
            if (in_array('anr', $this->get('assetTable')->getClassMetadata()->getIdentifierFieldNames())) {
                $amvs = $amvTable->getEntityByFields([
                    'asset' => [
                        'uuid' => $object->getAsset()->getUuid(),
                        'anr' => $anr->getId(),
                    ]
                ]);
            } else {
                $amvs = $amvTable->getEntityByFields(['asset' => $object->getAsset()->getUuid()]);
            }

            /** @var Amv $amv */
            foreach ($amvs as $amv) {
                $data = [
                    'anr' => $anr,
                    'amv' => $amv,
                    'asset' => $amv->getAsset(),
                    'instance' => $instance,
                    'threat' => $amv->getThreat(),
                    'vulnerability' => $amv->getVulnerability(),
                ];
                $instanceRiskEntityClassName = $this->get('table')->getEntityClass();
                $instanceRisk = new $instanceRiskEntityClassName($data);
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
}
