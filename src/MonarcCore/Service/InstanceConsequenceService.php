<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\ScaleImpactType;
use MonarcCore\Model\Table\InstanceConsequenceTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ScaleImpactTypeTable;

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
    protected $forbiddenFields = ['anr', 'instance', 'object', 'scaleImpactType', 'ch', 'ih', 'dh'];

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id,$data){

        //security
        $this->filterPatchFields($data);

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

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        $anrId = $data['anr'];
        $data = $this->updateConsequences($id, $data);
        $data['anr'] = $anrId;

        $entity = $this->get('table')->getEntity($id);

        $this->filterPostFields($data, ['anr', 'instance', 'object', 'scaleImpactType', 'ch', 'ih', 'dh'], $entity);

        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());


        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);



        $id = $this->get('table')->save($entity);

        $this->updateInstanceImpacts($id);

        return $id;
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

        $rolfpTypes = ScaleImpactType::getScaleImpactTypeRolfp();

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