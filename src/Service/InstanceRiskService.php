<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Amv;
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

    /**
     * Creates a new Instance Risk
     * @param int $instanceId The instance ID
     * @param int $anrId The ANR ID
     * @param Object $object The object
     */
    public function createInstanceRisks($instanceId, $anrId, $object)
    {
        //retrieve brothers instances
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        try {
            $instances = $instanceTable->getEntityByFields([
                'anr' => $anrId,
                'object' => (string)$object->uuid
            ]);
        } catch (MappingException | QueryException $e) {
            $instances = $instanceTable->getEntityByFields([
                'anr' => $anrId,
                'object' => [
                    'uuid' => (string)$object->uuid,
                    'anr' => $anrId
                ]
            ]);
        }

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');
        /** @var InstanceSuperClass $currentInstance */
        $currentInstance = $instanceTable->getEntity($instanceId);

        if ($object->scope === MonarcObject::SCOPE_GLOBAL && \count($instances) > 1) {
            /** @var InstanceSuperClass $instance */
            foreach ($instances as $instance) {
                if ($instance->getId() === $instanceId) {
                    continue;
                }

                $instancesRisks = $instanceRiskTable->getEntityByFields(['instance' => $instance->getId()]);
                foreach ($instancesRisks as $instanceRisk) {
                    /** @var InstanceRisk $newInstanceRisk */
                    $newInstanceRisk = clone $instanceRisk;
                    $newInstanceRisk->setId(null);
                    $newInstanceRisk->setInstance($currentInstance);
                    $newInstanceRisk->setCreator(
                        $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
                    );

                    $instanceRiskTable->save($newInstanceRisk);

                    // This part of the code is related to the FO. Needs to be extracted.
                    if ($this->get('recommandationRiskTable') !== null) {
                        $recoRisks = $this->get('recommandationRiskTable')->getEntityByFields(['anr' => $anrId, 'instanceRisk' => $instanceRisk->id]);
                        if (\count($recoRisks) > 0) {
                            foreach ($recoRisks as $recoRisk) {
                                $newRecoRisk = clone $recoRisk;
                                $newRecoRisk->set('id', null);
                                $newRecoRisk->set('instance', $currentInstance);
                                $newRecoRisk->set('instanceRisk', $newInstanceRisk);
                                $this->get('recommandationRiskTable')->save($newRecoRisk);
                            }
                        }
                    }

                    break;
                }
            }
        } else {
            /** @var AmvTable $amvTable */
            $amvTable = $this->get('amvTable');
            if (in_array('anr', $this->get('assetTable')->getClassMetadata()->getIdentifierFieldNames())) {
                $amvs = $amvTable->getEntityByFields([
                    'asset' => [
                        'uuid' => (string)$object->asset->uuid,
                        'anr' => $anrId
                    ]
                ]);
            } else {
                $amvs = $amvTable->getEntityByFields(['asset' => (string)$object->asset->uuid]);
            }

            /** @var Amv $amv */
            foreach ($amvs as $amv) {
                $data = [
                    'anr' => $amv->getAnr(),
                    'amv' => $amv,
                    'asset' => $amv->getAsset(),
                    'instance' => $currentInstance,
                    'threat' => $amv->getThreat(),
                    'vulnerability' => $amv->getVulnerability(),
                ];
                $instanceRiskEntityClassName = $this->get('table')->getEntityClass();
                $instanceRisk = new $instanceRiskEntityClassName($data);
                $instanceRiskTable->save($instanceRisk);

                $this->updateRisks($instanceRisk);
            }
        }
    }

    /**
     * Deletes an Instance Risk
     * @param int $instanceId The instance ID
     * @param int $anrId The ANR ID
     */
    public function deleteInstanceRisks($instanceId, $anrId)
    {
        $risks = $this->getInstanceRisks($instanceId, $anrId);
        $table = $this->get('table');
        $nb = \count($risks);
        $i = 1;
        foreach ($risks as $r) {
            $table->delete($r->id, $i === $nb);
            $i++;
        }
    }

    /**
     * Retrieves and returns the risks of the specified instance ID
     * @param int $instanceId The instance ID
     * @param int $anrId The ANR ID
     * @return array|bool
     */
    public function getInstanceRisks($instanceId, $anrId)
    {
        /** @var InstanceRiskTable $table */
        $table = $this->get('table');
        return $table->getEntityByFields(['anr' => $anrId, 'instance' => $instanceId]);
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

        /** @var InstanceRisk $instanceRisk */
        $instanceRisk = $this->get('table')->getEntity($id);
        if (!$instanceRisk) {
            throw new Exception('Entity does not exist', 412);
        }

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');

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
                        'object' => (string)$object->getUuid(),
                    ]);
                } catch (QueryException | MappingException $e) {
                    $instances = $instanceTable->getEntityByFields([
                        'anr' => $instanceRisk->getAnr()->getId(),
                        'object' => [
                            'anr' => $instanceRisk->getAnr()->getId(),
                            'uuid' => (string)$object->getUuid(),
                        ]
                    ]);
                }

                /** @var Instance $instance */
                foreach ($instances as $instance) {
                    if ($instance != $instanceRisk->getInstance()) {
                        $instancesRisks = $instanceRiskTable->getEntityByFields([
                            'amv' => [
                                'anr' => $instanceRisk->getAnr()->getId(),
                                'uuid' => (string)$instanceRisk->getAmv()->getUuid()
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

        $instanceRiskTable->save($instanceRisk);

        $this->updateRisks($id);

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

        /** @var InstanceRisk $instanceRisk */
        $instanceRisk = $this->get('table')->getEntity($id);
        if (!$instanceRisk) {
            throw new Exception('Entity does not exist', 412);
        }

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');

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
                        'object' => (string)$object->getUuid(),
                    ]);
                } catch (QueryException | MappingException $e) {
                    $instances = $instanceTable->getEntityByFields([
                        'anr' => $instanceRisk->getAnr()->getId(),
                        'object' => [
                            'anr' => $instanceRisk->getAnr()->getId(),
                            'uuid' => (string)$object->getUuid(),
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

        $instanceRisk->setDbAdapter($this->get('table')->getDb());
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

        $instanceRiskTable->save($instanceRisk);

        $this->updateRisks($id);

        return $id;
    }

    /**
     * Update the specified instance risk
     * @param InstanceRisk|int $instanceRisk The instance risk object, or its ID
     * @param bool $last If set to false, database flushes will be suspended until a call to this method with "true"
     */
    public function updateRisks($instanceRisk, $last = true)
    {
        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');

        if (!$instanceRisk instanceof InstanceRisk) {
            //retrieve instance risk
            $instanceRisk = $instanceRiskTable->getEntity($instanceRisk);
        }

        //retrieve instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instance = $instanceTable->getEntity($instanceRisk->instance->id);

        $riskC = $this->getRiskC($instance->c, $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate);
        $riskI = $this->getRiskI($instance->i, $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate);
        $riskD = $this->getRiskD($instance->d, $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate);

        $instanceRisk->riskC = $riskC;
        $instanceRisk->riskI = $riskI;
        $instanceRisk->riskD = $riskD;

        $risks = [];
        $impacts = [];

        if ($instanceRisk->threat->c) {
            $risks[] = $riskC;
            $impacts[] = $instance->c;
        }
        if ($instanceRisk->threat->i) {
            $risks[] = $riskI;
            $impacts[] = $instance->i;
        }
        if ($instanceRisk->threat->a) {
            $risks[] = $riskD;
            $impacts[] = $instance->d;
        }

        $instanceRisk->cacheMaxRisk = (count($risks)) ? max($risks) : -1;
        $instanceRisk->cacheTargetedRisk = $this->getTargetRisk($impacts, $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate, $instanceRisk->reductionAmount);

        $instanceRiskTable->save($instanceRisk, $last);

        $this->updateRecoRisks($instanceRisk);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');
        $instanceRisk = $instanceRiskTable->getEntity($id);
        $this->updateRecoRisks($instanceRisk);

        return parent::delete($id);
    }

    /**
     * TODO: This method is used only on FO side. Has to be removed from core together with refactoring of the service.
     *
     * Updates recommandation risk position.
     */
    public function updateRecoRisks(InstanceRiskSuperClass $instanceRisk): void
    {
    }
}
