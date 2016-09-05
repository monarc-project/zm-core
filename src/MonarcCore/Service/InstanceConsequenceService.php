<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\ScaleType;
use MonarcCore\Model\Table\InstanceConsequenceTable;
use MonarcCore\Model\Table\InstanceTable;
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
    protected $scaleTable;
    protected $scaleImpactTypeTable;
    protected $instanceService;

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data){

        //security
        $this->filterPatchFields($data, ['instance', 'object', 'scaleImpactType', 'ch', 'ih', 'dh']);

        if (count($data)) {

            if (isset($data['isHidden'])) {
                if ($data['isHidden']) {
                    $data['c'] = -1;
                    $data['i'] = -1;
                    $data['d'] = -1;
                }
            }

            $data = $this->updateConsequences($id, $data);

            parent::patch($id,$data);

            $this->updateInstanceImpacts($id);
        }

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

        $data = $this->updateConsequences($id, $data);

        parent::update($id,$data);

        $this->updateInstanceImpacts($id);
    }

    /**
     * Update consequences
     * @param $id
     * @param $data
     */
    protected function updateConsequences($id, $data) {
        $anrId = $data['anr'];
        unset($data['anr']);

        $this->verifyRates($anrId, $data, $this->getEntity($id));

        //values
        if (isset($data['c'])) {
            $data['ch'] = ($data['c'] == -1) ? 1 : 0;
        }
        if (isset($data['i'])) {
            $data['ih'] = ($data['i'] == -1) ? 1 : 0;
        }
        if (isset($data['d'])) {
            $data['dh'] = ($data['d'] == -1) ? 1 : 0;
        }

        return $data;
    }

    /**
     * Update Instance Impacts
     *
     * @param $instanceConsequencesId
     */
    protected function updateInstanceImpacts($instanceConsequencesId) {

        $rolfpTypes = ScaleType::getSclaeTypeRolfp();

        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        $instanceCurrentConsequence = $table->getEntity($instanceConsequencesId);

        $instanceC = [];
        $instanceI = [];
        $instanceD = [];
        $instanceConsequences = $table->getEntityByFields(['instance' => $instanceCurrentConsequence->instance->id]);
        foreach ($instanceConsequences as $instanceConsequence) {
            if (in_array($instanceConsequence->scaleImpactType->type, $rolfpTypes)) {
                $instanceC[] = (int) $instanceConsequence->c;
                $instanceI[] = (int) $instanceConsequence->i;
                $instanceD[] = (int) $instanceConsequence->D;
            }
        }

        $data = [
            'c' => max($instanceC),
            'i' => max($instanceI),
            'd' => max($instanceD)
        ];

        /** @var InstanceService $instanceService */
        $instanceService = $this->get('instanceService');
        $instanceService->patchInstance($instanceCurrentConsequence->anr->id, $instanceCurrentConsequence->instance->id, $data);
    }
}