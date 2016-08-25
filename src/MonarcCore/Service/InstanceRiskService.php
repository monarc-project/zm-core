<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\InstanceRiskTable;
use MonarcCore\Model\Table\ObjectRiskTable;
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
    protected $objectRiskTable;
    protected $scaleTable;
    protected $threatTable;
    protected $vulnerabilityTable;

    /**
     * Create Instance Risk
     *
     * @param $instanceId
     * @param $anrId
     * @param $objectId
     */
    public function createInstanceRisks($instanceId, $anrId, $objectId) {

        /** @var ObjectRiskTable $objectRiskTable */
        $objectRiskTable = $this->get('objectRiskTable');
        $objectRisks = $objectRiskTable->getByAnrAndObject($anrId, $objectId);

        foreach ($objectRisks as $objectRisk) {
            $data = [
                'anr' => $anrId,
                'amv' => $objectRisk->amv->id,
                'asset' => $objectRisk->asset->id,
                'instance' => $instanceId,
                'threat' => $objectRisk->threat->id,
                'vulnerability' => $objectRisk->vulnerability->id,
            ];

            $this->create($data);
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
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data){

        $anrId = $data['anr'];
        unset($data['anr']);

        $this->verifyRates($anrId, $this->getEntity($id), $data);

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
        unset($data['anr']);

        $this->verifyRates($anrId, $this->getEntity($id), $data);

        parent::update($id, $data);

        $this->updateRisks($id);

        return $id;
    }

    /**
     * Verify Rates
     *
     * @param $anrId
     * @param $instanceRisk
     * @param $data
     * @throws \Exception
     */
    protected function verifyRates($anrId, $instanceRisk, $data) {

        $errors = [];

        if (array_key_exists('threatRate', $data)) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scale = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => ScaleService::TYPE_THREAT]);

            $scale = $scale[0];

            $prob = (int) $data['threatRate'];

            if (($prob < $scale->min) || ($prob > $scale->max)) {
                $errors[] = 'Value for probability is not valid';
            }
        }

        if (array_key_exists('vulnerabilityRate', $data)) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scale = $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => ScaleService::TYPE_VULNERABILITY]);

            $scale = $scale[0];

            $prob = (int) $data['vulnerabilityRate'];

            if (($prob < $scale->min) || ($prob > $scale->max)) {
                $errors[] = 'Value for qualification is not valid';
            }
        }

        if (array_key_exists('reductionAmount', $data)) {
            $reductionAmount = (int) $data['reductionAmount'];

            $vulnerabilityRate = (array_key_exists('vulnerabilityRate', $data)) ? (int) $data['vulnerabilityRate'] : $instanceRisk['vulnerabilityRate'];

            if (($reductionAmount < 0) || ($reductionAmount > $vulnerabilityRate)) {
                $errors[] = 'Value for reduction amount is not valid';
            }
        }

        if (count($errors)) {
            throw new \Exception(implode(', ', $errors), 412);
        }
    }

    protected function updateRisks($instanceRiskId) {

        //retrieve instance risk
        /** @var InstanceTable $instanceTable */
        $instanceRiskTable = $this->get('table');
        $instanceRisk = $instanceRiskTable->getEntity($instanceRiskId);

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
        $instanceRisk->cacheMaxRisk = max([$riskC, $riskI, $riskD]);
        $instanceRisk->cacheTargetedRisk = $this->getTargetRisk($instance->c, $instance->i, $instance->d, $instanceRisk->threatRate, $instanceRisk->vulnerabilityRate, $instanceRisk->reductionAmount);

        $instanceRiskTable->save($instanceRisk);
    }
}