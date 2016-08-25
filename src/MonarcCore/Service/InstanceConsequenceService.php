<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Table\InstanceConsequenceTable;
use MonarcCore\Model\Table\ScaleTypeTable;

/**
 * Instance Consequence Service
 *
 * Class InstanceConsequenceService
 * @package MonarcCore\Service
 */
class InstanceConsequenceService extends AbstractService
{
    protected $dependencies = ['anr', 'instance', 'object', 'scaleImpactType'];

    protected $anrTable;
    protected $instanceTable;
    protected $objectTable;
    protected $scaleImpactTypeTable;

    /**
     * Create Instance Consequences
     *
     * @param $instanceId
     * @param $anrId
     * @param $object
     */
    public function createInstanceConsequences($instanceId, $anrId, $object) {

        //retrieve scale impact types
        /** @var ScaleTypeTable $scaleImpactTypeTable */
        $scaleImpactTypeTable = $this->get('scaleImpactTypeTable');
        $scalesImpactTypes = $scaleImpactTypeTable->getEntityByFields(['anr' => $anrId, 'isHidden' => 0]);

        foreach($scalesImpactTypes as $scalesImpactType) {
            $data = [
                'anr' => $anrId,
                'instance' => $instanceId,
                'object' => $object->id,
                'scaleImpactType' => $scalesImpactType,
            ];

            $this->create($data);
        }
    }

    /**
     * Get Instance Consequences
     *
     * @param $instanceId
     * @param $anrId
     * @return array|bool
     */
    public function getInstanceConsequences($instanceId, $anrId) {

        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        return $table->getEntityByFields(['anr' => $anrId, 'instance' => $instanceId]);
    }
}