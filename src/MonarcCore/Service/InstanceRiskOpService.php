<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\RolfRiskTable;
use MonarcCore\Model\Table\RolfTagTable;

/**
 * Instance Risk Service Op
 *
 * Class InstanceRiskService
 * @package MonarcCore\Service
 */
class InstanceRiskOpService extends AbstractService
{
    protected $dependencies = ['anr', 'instance', 'object', 'rolfRisk'];

    protected $anrTable;
    protected $instanceTable;
    protected $objectTable;
    protected $rolfRiskTable;
    protected $rolfTagTable;

    /**
     * Create Instance Risks Op
     * 
     * @param $instanceId
     * @param $anrId
     * @param $object
     */
    public function createInstanceRisksOp($instanceId, $anrId, $object) {

        if (isset($object->asset)) {
            if ($object->asset->type == AssetService::ASSET_PRIMARY) {
                if (!is_null($object->rolfTag)) {

                    //retrieve rolf risks
                    /** @var RolfTagTable $rolfTagTable */
                    $rolfTagTable = $this->get('rolfTagTable');
                    $rolfTag = $rolfTagTable->getEntity($object->rolfTag->id);

                    $rolfRisks = $rolfTag->risks;

                    foreach ($rolfRisks as $rolfRisk) {

                        $data = [
                            'anr' => $anrId,
                            'instance' => $instanceId,
                            'object' => $object->id,
                            'rolfRisk' => $rolfRisk->id,
                        ];

                        $this->create($data);
                    }
                }
            }
        }
    }
}