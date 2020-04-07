<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\InstanceRiskOp;
use Monarc\Core\Model\Entity\InstanceRiskOpSuperClass;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Entity\RolfTagSuperClass;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\RolfTagTable;

/**
 * Instance Risk Service Op
 *
 * Class InstanceRiskService
 * @package Monarc\Core\Service
 */
class InstanceRiskOpService extends AbstractService
{
    protected $dependencies = ['anr', 'instance', 'object', 'rolfRisk'];

    protected $anrTable;
    protected $userAnrTable;
    protected $modelTable;
    protected $instanceTable;
    protected $MonarcObjectTable;
    protected $rolfRiskTable;
    protected $rolfTagTable;
    protected $scaleTable;
    protected $forbiddenFields = ['anr', 'instance', 'object'];
    protected $recommandationTable;

    public function createInstanceRisksOp(InstanceSuperClass $instance, ObjectSuperClass $object)
    {
        if ($object->getAsset() !== null
            && $object->getRolfTag() !== null
            && $object->getAsset()->getType() === Asset::TYPE_PRIMARY
        ) {
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('instanceTable');
            $brotherInstances = $instanceTable->findByAnrAndObject($instance->getAnr(), $object);

            if ($object->getScope() === MonarcObject::SCOPE_GLOBAL && \count($brotherInstances) > 1) {
                /** @var InstanceRiskOpTable $instanceRiskOpTable */
                $instanceRiskOpTable = $this->get('table');
                foreach ($brotherInstances as $brotherInstance) {
                    if ($brotherInstance->getId() === $instance->getId()) {
                        continue;
                    }
                    // TODO: replace with the table method.
                    /** @var InstanceRiskOpSuperClass[] $instancesRisksOp */
                    $instancesRisksOp = $instanceRiskOpTable->getEntityByFields([
                        'instance' => $brotherInstance->getId()
                    ]);
                    foreach ($instancesRisksOp as $instanceRiskOp) {
                        $newInstanceRiskOp = clone $instanceRiskOp;
                        $newInstanceRiskOp->setId(null);
                        $newInstanceRiskOp->setInstance($instance);

                        $newInstanceRiskOp->setCreator(
                            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
                        );

                        $instanceRiskOpTable->save($newInstanceRiskOp);
                    }

                    break;
                }
            } else {
                /** @var RolfTagTable $rolfTagTable */
                $rolfTagTable = $this->get('rolfTagTable');
                /** @var RolfTagSuperClass $rolfTag */
                $rolfTag = $rolfTagTable->getEntity($object->getRolfTag()->getId());

                $rolfRisks = $rolfTag->risks;

                $nbRolfRisks = \count($rolfRisks);
                foreach ($rolfRisks as $i => $rolfRisk) {
                    $data = [
                        'anr' => $instance->getAnr() ? $instance->getAnr()->getId() : null,
                        'instance' => $instance->getId(),
                        'object' => (string)$object->getUuid(),
                        'rolfRisk' => $rolfRisk->id,
                        'riskCacheCode' => $rolfRisk->code,
                        'riskCacheLabel1' => $rolfRisk->label1,
                        'riskCacheLabel2' => $rolfRisk->label2,
                        'riskCacheLabel3' => $rolfRisk->label3,
                        'riskCacheLabel4' => $rolfRisk->label4,
                        'riskCacheDescription1' => $rolfRisk->description1,
                        'riskCacheDescription2' => $rolfRisk->description2,
                        'riskCacheDescription3' => $rolfRisk->description3,
                        'riskCacheDescription4' => $rolfRisk->description4,
                    ];
                    $this->create($data, ($i + 1) === $nbRolfRisks);
                }
            }
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteOperationalRisks(InstanceSuperClass $instance): void
    {
        /** @var InstanceRiskOpTable $operationalRiskTable */
        $operationalRiskTable = $this->get('table');
        $operationalRisks = $operationalRiskTable->findByInstance($instance);
        foreach ($operationalRisks as $operationalRisk) {
            $operationalRiskTable->deleteEntity($operationalRisk, false);
        }
        $operationalRiskTable->getDb()->flush();
    }

    /**
     * Retrieves and returns the instance's operational risks
     * @param int $instanceId The instance ID
     * @param int $anrId The ANR ID
     * @return array|bool An array of operational risks, or false in case of error
     */
    public function getInstanceRisksOp($instanceId, $anrId)
    {
        /** @var InstanceRiskOpTable $table */
        $table = $this->get('table');

        // TODO: getInstanceRisksOp by instance, anr is an extra. Use the table method directly (add findByInstance)!!!
        return $table->findByInstance(['anr' => $anrId, 'instance' => $instanceId]);
    }

    /**
     * Retrieves and returns the operational risks of multiple instances
     * @param int[] $instancesIds The IDs of instances
     * @param int $anrId The ANR ID
     * @return array The instances risks
     */
    public function getInstancesRisksOp($instancesIds, $anrId, $params = [])
    {
        /** @var InstanceRiskOpTable $table */
        $table = $this->get('table');
        return $table->getInstancesRisksOp($anrId, $instancesIds, $params);
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \Monarc\Core\Exception\Exception('Entity does not exist', 412);
        }

        $toFilter = [
            'brutProb',
            'brutR',
            'brutO',
            'brutL',
            'brutF',
            'brutP',
            'netProb',
            'netR',
            'netO',
            'netL',
            'netF',
            'netP'
        ];

        // CLean up the values to avoid empty values or dashes
        foreach ($toFilter as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim($data[$k]);
                if (empty($data[$k]) || $data[$k] == '-' || $data[$k] == -1) {
                    $data[$k] = -1;
                }
            }
        }

        // Filter out fields we don't want to update
        $this->filterPatchFields($data);

        return parent::patch($id, $data);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        /** @var InstanceRiskOp $risk */
        $risk = $this->get('table')->getEntity($id);

        if (!$risk) {
            throw new \Monarc\Core\Exception\Exception('Entity does not exist', 412);
        }

        $toFilter = ['brutProb', 'brutR', 'brutO', 'brutL', 'brutF', 'brutP', 'netProb', 'netR', 'netO', 'netL', 'netF', 'netP'];
        foreach ($toFilter as $k) {
            if (isset($data[$k])) {
                $data[$k] = trim($data[$k]);
                if (!isset($data[$k]) || $data[$k] == '-' || $data[$k] == -1) {
                    $data[$k] = -1;
                }
            }
        }

        $this->verifyRates($risk->getAnr()->getId(), $data, $risk);
        $risk->setDbAdapter($this->get('table')->getDb());
        $risk->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Monarc\Core\Exception\Exception('Data missing', 412);
        }

        $risk->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($risk, $dependencies);

        //Calculate risk values
        $datatype = ['brut', 'net', 'targeted'];
        $impacts = ['r', 'o', 'l', 'f', 'p'];

        foreach ($datatype as $type) {
            $max = -1;
            $prob = $type . 'Prob';
            if ($risk->$prob != -1) {
                foreach ($impacts as $i) {
                    $icol = $type . strtoupper($i);
                    if ($risk->$icol > -1 && ($risk->$prob * $risk->$icol > $max)) {
                        $max = $risk->$prob * $risk->$icol;
                    }
                }
            }

            $cache = 'cache' . ucfirst($type) . 'Risk';
            $risk->$cache = $max;
        }

        $risk->setUpdater($this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname());

        $this->get('table')->save($risk);

        return $risk->getJsonArray();
    }
}
