<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Entity\Object;
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
    protected $modelTable;
    protected $instanceTable;
    protected $objectTable;
    protected $rolfRiskTable;
    protected $rolfTagTable;
    protected $scaleTable;
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

                    //retrieve brothers instances
                    /** @var InstanceTable $instanceTable */
                    $instanceTable = $this->get('instanceTable');
                    $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $object->id]);

                    if ($object->scope == Object::SCOPE_GLOBAL && count($instances) > 1) {

                        /** @var InstanceTable $instanceTable */
                        $instanceTable = $this->get('instanceTable');
                        $currentInstance = $instanceTable->getEntity($instanceId);

                        /** @var InstanceRiskOpTable $instanceRiskOpTable */
                        $instanceRiskOpTable = $this->get('table');
                        foreach($instances as $instance) {
                            if ($instance->id != $instanceId) {
                                $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['instance' => $instance->id]);
                                foreach($instancesRisksOp as $instanceRiskOp) {
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
    }

    /**
     * Delete Instance Risk
     *
     * @param $instanceId
     * @param $anrId
     */
    public function deleteInstanceRisksOp($instanceId, $anrId){
        $risks = $this->getInstanceRisksOp($instanceId, $anrId);
        $table = $this->get('table');
        $nb = count($risks);
        $i = 1;
        foreach($risks as $r){
            $table->delete($r->id,($i == $nb));
            $i++;
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

    public function update($id, $data){

        $risk = $this->get('table')->getEntity($id);

        if (!$risk) {
            throw new \Exception('Entity does not exist', 412);
        }
        $this->verifyRates($risk->getAnr()->getId(), $data, $risk);
        $risk->setDbAdapter($this->get('table')->getDb());
        $risk->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        $risk->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($risk, $dependencies);

        //Calculate risk values
        $datatype = ['brut', 'net', 'targeted'];
        $impacts = ['r', 'o', 'l', 'f', 'p'];

        foreach($datatype as $type){
            $max = -1;
            $prob = $type.'Prob';
            if($risk->$prob != -1){
                foreach($impacts as $i){
                    $icol = $type.strtoupper($i);
                    if($risk->$icol > -1 && ( $risk->$prob * $risk->$icol > $max)){
                        $max = $risk->$prob * $risk->$icol;
                    }
                }
            }

            $cache = 'cache'.ucfirst($type).'Risk';
            $risk->$cache = $max;
        }

        $this->get('table')->save($risk);
        return $risk->getJsonArray();
    }
}
