<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Table\InstanceRiskOpTable;
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
    protected $forbiddenFields = ['anr', 'instance', 'object'];

    /**
     * Create Instance Risks Op
     * 
     * @param $instanceId
     * @param $anrId
     * @param $object
     */
    public function createInstanceRisksOp($instanceId, $anrId, $object) {

        if (isset($object->asset)) {
            if ($object->asset->type == Asset::ASSET_PRIMARY) {
                if (!is_null($object->rolfTag)) {

                    //retrieve rolf risks
                    /** @var RolfTagTable $rolfTagTable */
                    $rolfTagTable = $this->get('rolfTagTable');
                    $rolfTag = $rolfTagTable->getEntity($object->rolfTag->id);

                    $rolfRisks = $rolfTag->risks;

                    $nbRolfRisks = count($rolfRisks);
                    $i = 1;
                    foreach ($rolfRisks as $rolfRisk) {

                        $lastRolfRisks = ($nbRolfRisks == $i) ? true : false;

                        $data = [
                            'anr' => $anrId,
                            'instance' => $instanceId,
                            'object' => $object->id,
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

                        $this->create($data, $lastRolfRisks);

                        $i++;
                    }
                }
            }
        }
    }

    /**
     * Get Instance Risks Op
     *
     * @param $instanceId
     * @param $anrId
     * @return array|bool
     */
    public function getInstanceRisksOp($instanceId, $anrId) {

        /** @var InstanceRiskOpTable $table */
        $table = $this->get('table');
        return $table->getEntityByFields(['anr' => $anrId, 'instance' => $instanceId]);
    }

    /**
     * Get Instances Risks Op
     *
     * @param $instancesIds
     * @param $anrId
     * @return array
     */
    public function getInstancesRisksOp($instancesIds, $anrId) {

        /** @var InstanceRiskOpTable $table */
        $table = $this->get('table');
        return $table->getInstancesRisksOp($anrId, $instancesIds);
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data)
    {
        //security
        $this->filterPatchFields($data);

        parent::patch($id, $data);
    }
}