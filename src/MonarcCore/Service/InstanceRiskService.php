<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\InstanceRisk;
use MonarcCore\Model\Table\AmvTable;
use MonarcCore\Model\Table\InstanceRiskTable;
use MonarcCore\Model\Table\ObjectTable;
use MonarcCore\Model\Table\ScaleTable;

/**
 * Instance Risk Service
 *
 * Class InstanceRiskService
 * @package MonarcCore\Service
 */
class InstanceRiskService extends AbstractService
{
    protected $dependencies = ['anr', 'amv', 'asset', 'instance', 'threat', 'vulnerability'];

    protected $anrTable;
    protected $amvTable;
    protected $assetTable;
    protected $instanceTable;
    protected $objectTable;
    protected $scaleTable;
    protected $threatTable;
    protected $vulnerabilityTable;
    protected $forbiddenFields = ['anr', 'amv', 'asset', 'threat', 'vulnerability'];

    /**
     * Create Instance Risk
     *
     * @param $instanceId
     * @param $anrId
     * @param $object
     */
    public function createInstanceRisks($instanceId, $anrId, $object) {

        /** @var AmvTable $amvTable */
        $amvTable = $this->get('amvTable');
        $amvs = $amvTable->getEntityByFields(['asset' => $object->asset->id]);

        $nbAmvs = count($amvs);
        $i = 1;
        foreach ($amvs as $amv) {

            $lastAmv = ($nbAmvs == $i) ? true : false;

            $data = [
                'anr' => $anrId,
                'amv' => $amv->id,
                'asset' => $amv->asset->id,
                'instance' => $instanceId,
                'threat' => $amv->threat->id,
                'vulnerability' => $amv->vulnerability->id,
            ];

            $instanceRiskLastId = $this->create($data, $lastAmv);

            $i++;
        }

        if ($nbAmvs) {
            for ($i = $instanceRiskLastId - $nbAmvs + 1; $i <= $instanceRiskLastId; $i++) {
                $lastRisk = ($i == $instanceRiskLastId) ? true : false;
                $this->updateRisks($i, $lastRisk);
            }
        }
    }

    /**
     * Get Instance Risks
     *
     * @param $instanceId
     * @param $anrId
     * @return array|bool
     */
    public function getInstanceRisks($instanceId, $anrId) {

        /** @var InstanceRiskTable $table */
        $table = $this->get('table');
        return $table->getEntityByFields(['anr' => $anrId, 'instance' => $instanceId]);
    }

    /**
     * Get Instances Risks
     *
     * @param $instancesIds
     * @param $anrId
     * @return array
     */
    public function getInstancesRisks($instancesIds, $anrId) {

        /** @var InstanceRiskTable $table */
        $table = $this->get('table');
        return $table->getInstancesRisks($anrId, $instancesIds);
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data){

        $anrId = $data['anr'];

        //security
        $this->filterPatchFields($data);

        $this->verifyRates($anrId, $data, $this->getEntity($id));

        parent::patch($id,$data);

        $this->updateRisks($id);

        return $id;
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id,$data){

        $anrId = $data['anr'];

        $this->verifyRates($anrId, $data, $this->getEntity($id));

        parent::update($id, $data);

        $this->updateRisks($id);

        return $id;
    }

    /**
     * Update Risks
     *
     * @param $instanceRisk
     * @param bool $last
     */
    public function updateRisks($instanceRisk, $last = true) {

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
        if ($instanceRisk->threat->c) {
            $risks[] = $riskC;
        }
        if ($instanceRisk->threat->i) {
            $risks[] = $riskI;
        }
        if ($instanceRisk->threat->d) {
            $risks[] = $riskD;
        }

        $instanceRisk->cacheMaxRisk = (count($risks)) ? max($risks) : -1;
        $instanceRisk->cacheTargetedRisk = $this->getTargetRisk($instance->c, $instance->i, $instance->d, $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate, $instanceRisk->reductionAmount);

        $instanceRiskTable->save($instanceRisk, $last);
    }
}