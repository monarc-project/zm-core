<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\InstanceRisk;
use MonarcCore\Model\Entity\MonarcObject;
use MonarcCore\Model\Table\AmvTable;
use MonarcCore\Model\Table\InstanceRiskTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Traits\RiskTrait;

/**
 * Instance Risk Service
 *
 * Class InstanceRiskService
 * @package MonarcCore\Service
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
        $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $object->id]);
        if ($object->scope == MonarcObject::SCOPE_GLOBAL && count($instances) > 1) {

            $currentInstance = $instanceTable->getEntity($instanceId);

            /** @var InstanceRiskTable $instanceRiskTable */
            $instanceRiskTable = $this->get('table');

            foreach($instances as $instance) {
                if ($instance->id != $instanceId) {
                    $instancesRisks = $instanceRiskTable->getEntityByFields(['instance' => $instance->id]);
                    foreach($instancesRisks as $instanceRisk) {
                        $newInstanceRisk = clone $instanceRisk;
                        $newInstanceRisk->setId(null);
                        $newInstanceRisk->setInstance($currentInstance);
                        $instanceRiskTable->save($newInstanceRisk);
                    }
                }
                break;
            }
        } else {

            /** @var AmvTable $amvTable */
            $amvTable = $this->get('amvTable');
            $amvs = $amvTable->getEntityByFields(['asset' => $object->asset->id]);

            $nbAmvs = count($amvs);
            $i = 1;
            foreach ($amvs as $amv) {
                $data = [
                    'anr' => $anrId,
                    'amv' => $amv->id,
                    'asset' => $amv->asset->id,
                    'instance' => $instanceId,
                    'threat' => $amv->threat->id,
                    'vulnerability' => $amv->vulnerability->id,
                ];
                $instanceRiskLastId = $this->create($data, ($nbAmvs == $i));
                $i++;
            }

            if ($nbAmvs) {
                for ($i = $instanceRiskLastId - $nbAmvs + 1; $i <= $instanceRiskLastId; $i++) {
                    $lastRisk = ($i == $instanceRiskLastId);
                    $this->updateRisks($i, $lastRisk);
                }
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
        $nb = count($risks);
        $i = 1;
        foreach ($risks as $r) {
            $r->set('kindOfMeasure',-1);
            $this->updateRecoRisks($r);
            $table->delete($r->id,($i == $nb));
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
     * Retrieves and returns the risks of multiple instances
     * @param int[] $instancesIds An array of instance IDs
     * @param int $anrId The ANR ID
     * @return array The instances risks
     */
    public function getInstancesRisks($instancesIds, $anrId)
    {
        /** @var InstanceRiskTable $table */
        $table = $this->get('table');
        return $table->getInstancesRisks($anrId, $instancesIds);
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

        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
        }

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');

        //if object is global, impact modifications to brothers
        if ($manageGlobal) {
            $object = $entity->instance->object;
            if ($object->scope == MonarcObject::SCOPE_GLOBAL) {

                //retrieve brothers instances
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                $instances = $instanceTable->getEntityByFields(['anr' => $entity->anr->id, 'object' => $object->id]);

                foreach ($instances as $instance) {
                    if ($instance != $entity->instance) {
                        $instancesRisks = $instanceRiskTable->getEntityByFields(['instance' => $instance->id, 'amv' => $entity->amv->id]);
                        foreach ($instancesRisks as $instanceRisk) {
                            $initialData['id'] = $instanceRisk->id;
                            $initialData['instance'] = $instance->id;
                            $this->patch($instanceRisk->id, $initialData, false);
                        }
                    }
                }
            }
        }

        $entity->setLanguage($this->getLanguage());

        foreach ($this->dependencies as $dependency) {
            if (!isset($data[$dependency])) {
                $data[$dependency] = $entity->$dependency->id;
            }
        }

        $entity->exchangeArray($data, true);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $instanceRiskTable->save($entity);

        $this->updateRisks($id);
        $this->updateRecoRisks($entity);

        return $id;
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data, $manageGlobal = true)
    {
        $initialData = $data;
        $anrId = (isset($data['anr']))? $data['anr'] : null;

        if(isset($data['threatRate'])){
            $data['threatRate'] = trim($data['threatRate']);
            if(!isset($data['threatRate']) || $data['threatRate'] == '-' || $data['threatRate'] == -1){
                $data['threatRate'] = -1;
            }
        }
        if(isset($data['vulnerabilityRate'])){
            $data['vulnerabilityRate'] = trim($data['vulnerabilityRate']);
            if(!isset($data['vulnerabilityRate']) || $data['vulnerabilityRate'] == '-' || $data['vulnerabilityRate'] == -1){
                $data['vulnerabilityRate'] = -1;
            }
        }

        $this->verifyRates($anrId, $data, $this->getEntity($id));

        $entity = $this->get('table')->getEntity($id);
        if (!$entity) {
            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
        }

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');

        //if object is global, impact modifications to brothers
        if ($manageGlobal) {
            $object = $entity->instance->object;
            if ($object->scope == MonarcObject::SCOPE_GLOBAL) {

                //retrieve brothers instances
                /** @var InstanceTable $instanceTable */
                $instanceTable = $this->get('instanceTable');
                $instances = $instanceTable->getEntityByFields(['anr' => $entity->anr->id, 'object' => $object->id]);

                foreach ($instances as $instance) {
                    if ($instance !== $entity->instance) {
                        if ($entity->specific == 0) {
                            if ($entity->amv) {
                                $instancesRisks = $instanceRiskTable->getEntityByFields(['instance' => $instance->id, 'amv' => $entity->amv->id]);
                            } else {
                                $instancesRisks = $instanceRiskTable->getEntityByFields(['instance' => $instance->id, 'threat' => $entity->threat->id, 'vulnerability' => $entity->vulnerability->id]);
                            }
                        } else {
                            $instancesRisks = $instanceRiskTable->getEntityByFields(['instance' => $instance->id, 'specific' => 1, 'threat' => $entity->threat->id, 'vulnerability' => $entity->vulnerability->id]);
                        }
                        foreach ($instancesRisks as $instanceRisk) {
                            $initialData['id'] = $instanceRisk->id;
                            $initialData['instance'] = $instance->id;
                            $this->update($instanceRisk->id, $initialData, false);
                        }
                    }
                }
            }
        }

        $this->filterPostFields($data, $entity);

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \MonarcCore\Exception\Exception('Data missing', 412);
        }

        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $instanceRiskTable->save($entity);

        $this->updateRisks($id);
        $this->updateRecoRisks($entity);

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
     * Updates this risk  from the Risk Table
     * @param int $id The ID
     * @param array $data The new data to update
     * @return mixed
     */
    public function updateFromRiskTable($id, $data)
    {
        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable = $this->get('table');
        $instanceRisk = $instanceRiskTable->getEntity($id);

        //security
        $data['specific'] = $instanceRisk->get('specific');

        if ($instanceRisk->threatRate != $data['threatRate']) {
            $data['mh'] = 0;
        }

        return $this->update($id, $data);
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
     * Update recommandation risk position
     * @param InstanceRisk $entity The entity to update
     */
    public function updateRecoRisks($entity){
        if(!empty($this->get('recommandationTable'))){
            switch($entity->get('kindOfMeasure')){
                case InstanceRisk::KIND_REDUCTION:
                case InstanceRisk::KIND_REFUS:
                case InstanceRisk::KIND_ACCEPTATION:
                case InstanceRisk::KIND_PARTAGE:
                    $sql = "SELECT recommandation_id
                            FROM recommandations_risks
                            WHERE instance_risk_id = :id
                            GROUP BY recommandation_id";
                    $res = $this->get('table')->getDb()->getEntityManager()->getConnection()
                        ->fetchAll($sql, [':id'=>$entity->get('id')]);
                    $ids = [];
                    foreach($res as $r){
                        $ids[$r['recommandation_id']] = $r['recommandation_id'];
                    }
                    $recos = $this->get('recommandationTable')->getEntityByFields(['anr'=>$entity->get('anr')->get('id')],['position'=>'ASC','importance'=>'DESC','code'=>'ASC']);
                    $i = 0;
                    $hasSave = false;
                    foreach($recos as &$r){
                        if(($r->get('position') == null || $r->get('position') <= 0) && isset($ids[$r->get('id')])){
                            $i++;
                            $r->set('position',$i);
                            $this->get('recommandationTable')->save($r,false);
                            $hasSave = true;
                        }elseif($i > 0 && $r->get('position') > 0){
                            $r->set('position',$r->get('position')+$i);
                            $this->get('recommandationTable')->save($r,false);
                            $hasSave = true;
                        }
                    }
                    if($hasSave && !empty($r)){
                        $this->get('recommandationTable')->save($r);
                    }
                    break;
                case -1: // cas particulier, on supprime l'instanceRisk
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
                        ->fetchAll($sql, [':anr'=>$entity->get('anr')->get('id'), ':id'=>$entity->get('instance')->get('id')]);
                    $ids = [];
                    foreach($res as $r){
                        $ids[$r['recommandation_id']] = $r['recommandation_id'];
                    }
                    $recos = $this->get('recommandationTable')->getEntityByFields(['anr'=>$entity->get('anr')->get('id')],['position'=>'ASC']);
                    $i = 0;
                    $hasSave = false;
                    $last = null;
                    foreach($recos as &$r){
                        if(!isset($ids[$r->get('id')])){
                            if($r->get('position') == null || $r->get('position') <= 0){
                            }else{
                                $i++;
                            }
                            $hasSave = true;
                            $this->get('recommandationTable')->delete($r->get('id'));
                        }elseif($i > 0 && $r->get('position') > 0){
                            $r->set('position',$r->get('position')-$i);
                            $this->get('recommandationTable')->save($r,false);
                            $hasSave = true;
                            $last = $r;
                        }
                    }
                    if($hasSave && !empty($last)){
                        $this->get('recommandationTable')->save($last);
                    }
                    break;
                case InstanceRisk::KIND_NOT_TREATED:
                default:
                    $sql = "SELECT rr.recommandation_id
                            FROM recommandations_risks rr
                            LEFT JOIN instances_risks ir
                            ON ir.id = rr.instance_risk_id
                            AND rr.instance_risk_id != :id
                            LEFT JOIN instances_risks_op iro
                            ON iro.id = rr.instance_risk_op_id
                            WHERE ((ir.kind_of_measure IS NOT NULL AND ir.kind_of_measure < ".InstanceRisk::KIND_NOT_TREATED.")
                                OR (iro.kind_of_measure IS NOT NULL AND iro.kind_of_measure < ".\MonarcCore\Model\Entity\InstanceRiskOp::KIND_NOT_TREATED."))
                            AND (rr.instance_risk_op_id IS NOT NULL OR rr.instance_risk_id IS NOT NULL)
                            AND rr.anr_id = :anr
                            GROUP BY rr.recommandation_id";
                    $res = $this->get('table')->getDb()->getEntityManager()->getConnection()
                        ->fetchAll($sql, [':anr'=>$entity->get('anr')->get('id'), ':id'=>$entity->get('id')]);
                    $ids = [];
                    foreach($res as $r){
                        $ids[$r['recommandation_id']] = $r['recommandation_id'];
                    }
                    $recos = $this->get('recommandationTable')->getEntityByFields(['anr'=>$entity->get('anr')->get('id'), 'position' => ['op'=>'IS NOT', 'value'=>null]],['position'=>'ASC']);
                    $i = 0;
                    $hasSave = false;
                    foreach($recos as &$r){
                        if($r->get('position') > 0 && !isset($ids[$r->get('id')])){
                            $i++;
                            $r->set('position',null);
                            $this->get('recommandationTable')->save($r,false);
                            $hasSave = true;
                        }elseif($i > 0 && $r->get('position') > 0){
                            $r->set('position',$r->get('position')-$i);
                            $this->get('recommandationTable')->save($r,false);
                            $hasSave = true;
                        }
                    }
                    if($hasSave && !empty($r)){
                        $this->get('recommandationTable')->save($r);
                    }
                    break;
            }
        }
    }
}
