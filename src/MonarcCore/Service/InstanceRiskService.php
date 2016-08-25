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

        $this->verifyRates($anrId, $data);

        return parent::patch($id,$data);
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

        $this->verifyRates($anrId, $data);

        return parent::update($id, $data);
    }

    /**
     * Verify Rates
     *
     * @param $anrId
     * @param $data
     * @throws \Exception
     */
    protected function verifyRates($anrId, $data) {

        $errors = [];

        if (array_key_exists('threatRate', $data)) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scale =  $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => ScaleService::TYPE_THREAT]);

            $scale = $scale[0];

            $prob = (int) $data['threatRate'];

            if (($prob < $scale->min) || ($prob > $scale->max)) {
                $errors[] = 'Value for probability is not valid';
            }
        }

        if (array_key_exists('vulnerabilityRate', $data)) {
            /** @var ScaleTable $scaleTable */
            $scaleTable = $this->get('scaleTable');
            $scale =  $scaleTable->getEntityByFields(['anr' => $anrId, 'type' => ScaleService::TYPE_VULNERABILITY]);

            $scale = $scale[0];

            $prob = (int) $data['vulnerabilityRate'];

            if (($prob < $scale->min) || ($prob > $scale->max)) {
                $errors[] = 'Value for qualification is not valid';
            }
        }

        if (count($errors)) {
            throw new \Exception(implode(', ', $errors), 412);
        }
    }
}