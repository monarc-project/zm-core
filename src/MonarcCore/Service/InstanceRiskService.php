<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Table\ObjectRiskTable;

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
}