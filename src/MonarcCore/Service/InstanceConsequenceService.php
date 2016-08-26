<?php
namespace MonarcCore\Service;
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
            $data = $this->updateConsequences($id, $data);

            parent::patch($id,$data);

            $this->updateChildren($id, ['ch' => $data['ch'], 'ih' => $data['ih'],'dh' => $data['dh']]);
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

        $this->updateChildren($id, ['ch' => $data['ch'], 'ih' => $data['ih'],'dh' => $data['dh']]);
    }

    /**
     * Update consequences
     * @param $id
     * @param $data
     */
    protected function updateConsequences($id, $data) {
        $anrId = $data['anr'];
        unset($data['anr']);

        $this->verifyRates($anrId, $this->getEntity($id), $data);

        if (array_key_exists('c', $data)) {
            if (($data['c'] == -1) || ($data['i'] == -1) || ($data['d'] == -1))  {
                $parentConsequences = $this->getParentConsequences($id);

                if ($data['c'] == -1) {
                    $data['ch'] = ($parentConsequences) ? (int) $parentConsequences['c'] : -1;
                } else {
                    $data['ch'] = (int) $data['c'];
                }

                if ($data['i'] == -1) {
                    $data['ih'] = ($parentConsequences) ? (int) $parentConsequences['i'] : -1;
                } else {
                    $data['ih'] = (int) $data['i'];
                }

                if ($data['d'] == -1) {
                    $data['dh'] = ($parentConsequences) ? (int) $parentConsequences['d'] : -1;
                } else {
                    $data['dh'] = (int) $data['d'];
                }
            }
        }

        return $data;
    }

    /**
     * Get Parent Consequences
     *
     * @param $instanceConsequenceId
     * @return array|bool
     */
    protected function getParentConsequences($instanceConsequenceId) {

        //retrieve instance consequences
        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        $instanceConsequence = $table->getEntity($instanceConsequenceId);

        //retrieve instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instance = $instanceTable->getEntity($instanceConsequence->instance->id);

        if (is_null($instance->parent)) {
            return false;
        } else {
            //retrieve instance consequences for the parent
            $parentInstanceConsequence = $table->getEntityByFields([
                'anr' => $instanceConsequence->anr->id,
                'instance' => $instance->parent->id,
                'scaleImpactType' => $instanceConsequence->scaleImpactType->id
            ]);

            if ($parentInstanceConsequence[0]->c == -1) {
                return [
                    'c' => $parentInstanceConsequence[0]->ch,
                    'i' => $parentInstanceConsequence[0]->ih,
                    'd' => $parentInstanceConsequence[0]->dh,
                ];
            } else {
                return [
                    'c' => $parentInstanceConsequence[0]->c,
                    'i' => $parentInstanceConsequence[0]->i,
                    'd' => $parentInstanceConsequence[0]->d,
                ];
            }
        }
    }

    /**
     * Update children
     *
     * @param $instanceConsequenceId
     * @param $data
     */
    protected function updateChildren($instanceConsequenceId, $data) {

        //retrieve instance consequence
        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        $instanceConsequence = $table->getEntity($instanceConsequenceId);

        //retrieve children instance
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instancesChildren = $instanceTable->getEntityByFields(['parent' => $instanceConsequence->instance->id]);

        foreach($instancesChildren as $instance) {

            //retrieve instance consequences for the child
            $childInstanceConsequence = $table->getEntityByFields([
                'anr' => $instanceConsequence->anr->id,
                'instance' => $instance->id,
                'scaleImpactType' => $instanceConsequence->scaleImpactType->id
            ]);

            if ($childInstanceConsequence[0]->c == -1) {
                $childInstanceConsequence[0]->ch = $data['ch'];
            }
            if ($childInstanceConsequence[0]->i == -1) {
                $childInstanceConsequence[0]->ih = $data['ih'];
            }
            if ($childInstanceConsequence[0]->d == -1) {
                $childInstanceConsequence[0]->dh = $data['dh'];
            }

            $table->save($childInstanceConsequence[0]);

            //update children
            $childrenData = [
                'ch' => $childInstanceConsequence[0]->ch,
                'ih' => $childInstanceConsequence[0]->ih,
                'dh' => $childInstanceConsequence[0]->dh
            ];
            $this->updateChildren($childInstanceConsequence[0]->id, $childrenData);
        }
    }
}