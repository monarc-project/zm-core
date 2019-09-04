<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\InstanceRiskOp;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\RolfTagTable;

use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Mapping\MappingException;

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

    /**
     * Creates a new instance operational risk
     * @param int $instanceId The ID of the instance
     * @param int $anrId The ANR ID
     * @param Object $object The affected object
     */
    public function createInstanceRisksOp($instanceId, $anrId, $object)
    {
        if (isset($object->asset) &&
            $object->asset->type == Asset::TYPE_PRIMARY &&
            !is_null($object->rolfTag)
        ) {
            //retrieve brothers instances
            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('instanceTable');
            try{
              $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $object->uuid->toString()]);
            }catch(QueryException $e){
              $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => ['anr' => $anrId ,'uuid' => $object->uuid->toString()]]);
            } catch (MappingException $e) {
                $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => ['anr' => $anrId ,'uuid' => $object->uuid->toString()]]);
            }

            if ($object->scope == MonarcObject::SCOPE_GLOBAL && count($instances) > 1) {

                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                $currentInstance = $instanceTable->getEntity($instanceId);

                /** @var InstanceRiskOpTable $instanceRiskOpTable */
                $instanceRiskOpTable = $this->get('table');
                foreach ($instances as $instance) {
                    if ($instance->id != $instanceId) {
                        $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['instance' => $instance->id]);
                        foreach ($instancesRisksOp as $instanceRiskOp) {
                            $newInstanceRiskOp = clone $instanceRiskOp;
                            $newInstanceRiskOp->setId(null);
                            $newInstanceRiskOp->setInstance($currentInstance);
                            $instanceRiskOpTable->save($newInstanceRiskOp);
                        }
                    }
                    break;
                }
            } else {

                //retrieve rolf risks
                /** @var RolfTagTable $rolfTagTable */
                $rolfTagTable = $this->get('rolfTagTable');
                $rolfTag = $rolfTagTable->getEntity($object->rolfTag->id);

                $rolfRisks = $rolfTag->risks;

                $nbRolfRisks = count($rolfRisks);
                $i = 1;
                $nbRolfRisks = count($rolfRisks);
                foreach ($rolfRisks as $rolfRisk) {
                    $data = [
                        'anr' => $anrId,
                        'instance' => $instanceId,
                        'object' => $object->uuid->toString(),
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
                    $this->create($data, ($i == $nbRolfRisks));
                    $i++;
                }
            }
        }
    }

    /**
     * Deletes the operational risk from the instance
     * @param int $instanceId The instance ID
     * @param int $anrId The anr ID
     */
    public function deleteInstanceRisksOp($instanceId, $anrId)
    {
        $risks = $this->getInstanceRisksOp($instanceId, $anrId);
        $table = $this->get('table');
        $nb = count($risks);
        $i = 1;
        foreach ($risks as $r) {
            $r->set('kindOfMeasure', -1);
            $this->updateRecoRisksOp($r);
            $table->delete($r->id, ($i == $nb));
            $i++;
        }
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
        return $table->getEntityByFields(['anr' => $anrId, 'instance' => $instanceId]);
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

        $toFilter = ['brutProb', 'brutR', 'brutO', 'brutL', 'brutF', 'brutP', 'netProb', 'netR', 'netO', 'netL', 'netF', 'netP'];

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

        $r = parent::patch($id, $data);
        $this->updateRecoRisksOp($entity);
        return $r;
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
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

        $this->get('table')->save($risk);
        $this->updateRecoRisksOp($risk);
        return $risk->getJsonArray();
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        /** @var InstanceRiskOpTable $instanceRiskOpTable */
        $InstanceRiskOpTable = $this->get('table');
        $instanceRisk = $InstanceRiskOpTable->getEntity($id);
        $this->updateRecoRisksOp($instanceRisk);
        return parent::delete($id);
    }

    /**
     * Updates recommandation operational risk position
     * @param $entity InstanceRiskOp
     */
    public function updateRecoRisksOp($entity)
    {
        if (!empty($this->get('recommandationTable'))) {
            switch ($entity->get('kindOfMeasure')) {
                case \Monarc\Core\Model\Entity\InstanceRiskOp::KIND_REDUCTION:
                case \Monarc\Core\Model\Entity\InstanceRiskOp::KIND_REFUS:
                case \Monarc\Core\Model\Entity\InstanceRiskOp::KIND_ACCEPTATION:
                case \Monarc\Core\Model\Entity\InstanceRiskOp::KIND_PARTAGE:
                    $sql = "SELECT recommandation_id
                            FROM recommandations_risks
                            WHERE instance_risk_op_id = :id
                            GROUP BY recommandation_id";
                    $res = $this->get('table')->getDb()->getEntityManager()->getConnection()
                        ->fetchAll($sql, [':id' => $entity->get('id')]);
                    $ids = [];
                    foreach ($res as $r) {
                        $ids[$r['recommandation_id']] = $r['recommandation_id'];
                    }
                    $recos = $this->get('recommandationTable')->getEntityByFields(['anr' => $entity->get('anr')->get('id')], ['position' => 'ASC', 'importance' => 'DESC', 'code' => 'ASC']);
                    $i = 0;
                    $hasSave = false;
                    foreach ($recos as &$r) {
                        if (($r->get('position') == null || $r->get('position') <= 0) && isset($ids[$r->get('uuid')])) {
                            $i++;
                            $r->set('position', $i);
                            $this->get('recommandationTable')->save($r, false);
                            $hasSave = true;
                        } elseif ($i > 0 && $r->get('position') > 0) {
                            $r->set('position', $r->get('position') + $i);
                            $this->get('recommandationTable')->save($r, false);
                            $hasSave = true;
                        }
                    }
                    if ($hasSave && !empty($r)) {
                        $this->get('recommandationTable')->save($r);
                    }
                    break;
                case -1: // cas particulier, on supprime l'instanceRiskOp
                    $sql = "SELECT rr.recommandation_id
                            FROM recommandations_risks rr
                            LEFT JOIN instances_risks ir
                            ON ir.id = rr.instance_risk_id
                            LEFT JOIN instances_risks_op iro
                            ON iro.id = rr.instance_risk_op_id
                            WHERE rr.anr_id = :anr
                            AND (rr.instance_risk_op_id IS NOT NULL OR rr.instance_risk_id IS NOT NULL)
                            AND rr.instance_id != :id
                            GROUP BY rr.recommandation_id";
                    $res = $this->get('table')->getDb()->getEntityManager()->getConnection()
                        ->fetchAll($sql, [':anr' => $entity->get('anr')->get('id'), ':id' => $entity->get('instance')->get('id')]);
                    $ids = [];
                    foreach ($res as $r) {
                        $ids[$r['recommandation_id']] = $r['recommandation_id'];
                    }
                    $recos = $this->get('recommandationTable')->getEntityByFields(['anr' => $entity->get('anr')->get('id')], ['position' => 'ASC']);
                    $i = 0;
                    $hasSave = false;
                    $last = null;
                    foreach ($recos as &$r) {
                        if (!isset($ids[$r->get('uuid')])) {
                            if ($r->get('position') == null || $r->get('position') <= 0) {
                            } else {
                                $i++;
                            }
                            $hasSave = true;
                            $this->get('recommandationTable')->delete(['anr'=> $r->get('anr'), 'uuid' => $r->get('uuid')], false);
                        } elseif ($i > 0 && $r->get('position') > 0) {
                            $r->set('position', $r->get('position') - $i);
                            $this->get('recommandationTable')->save($r, false);
                            $hasSave = true;
                            $last = $r;
                        }
                    }
                    if ($hasSave && !empty($last)) {
                        $this->get('recommandationTable')->save($last);
                    }
                    break;
                case \Monarc\Core\Model\Entity\InstanceRiskOp::KIND_NOT_TREATED:
                default:
                    $sql = "SELECT rr.recommandation_id
                            FROM recommandations_risks rr
                            LEFT JOIN instances_risks ir
                            ON ir.id = rr.instance_risk_id
                            LEFT JOIN instances_risks_op iro
                            ON iro.id = rr.instance_risk_op_id
                            AND rr.instance_risk_op_id != :id
                            WHERE ((ir.kind_of_measure IS NOT NULL AND ir.kind_of_measure < " . \Monarc\Core\Model\Entity\InstanceRisk::KIND_NOT_TREATED . ")
                                OR (iro.kind_of_measure IS NOT NULL AND iro.kind_of_measure < " . \Monarc\Core\Model\Entity\InstanceRiskOp::KIND_NOT_TREATED . "))
                            AND (rr.instance_risk_op_id IS NOT NULL OR rr.instance_risk_id IS NOT NULL)
                            AND rr.anr_id = :anr
                            GROUP BY rr.recommandation_id";
                    $res = $this->get('table')->getDb()->getEntityManager()->getConnection()
                        ->fetchAll($sql, [':anr' => $entity->get('anr')->get('id'), ':id' => $entity->get('id')]);
                    $ids = [];
                    foreach ($res as $r) {
                        $ids[$r['recommandation_id']] = $r['recommandation_id'];
                    }
                    $recos = $this->get('recommandationTable')->getEntityByFields(['anr' => $entity->get('anr')->get('id'), 'position' => ['op' => 'IS NOT', 'value' => null]], ['position' => 'ASC']);
                    $i = 0;
                    $hasSave = false;
                    foreach ($recos as &$r) {
                        if ($r->get('position') > 0 && !isset($ids[$r->get('uuid')])) {
                            $i++;
                            $r->set('position', null);
                            $this->get('recommandationTable')->save($r, false);
                            $hasSave = true;
                        } elseif ($i > 0 && $r->get('position') > 0) {
                            $r->set('position', $r->get('position') - $i);
                            $this->get('recommandationTable')->save($r, false);
                            $hasSave = true;
                        }
                    }
                    if ($hasSave && !empty($r)) {
                        $this->get('recommandationTable')->save($r);
                    }
                    break;
            }
        }
    }
}
