<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\ScaleImpactType;
use MonarcCore\Model\Table\InstanceConsequenceTable;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ScaleImpactTypeTable;
use Zend\EventManager\EventManager;

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
    protected $forbiddenFields = ['anr', 'instance', 'object', 'scaleImpactType', 'ch', 'ih', 'dh'];

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id, $data){

        $anrId = $data['anr'];

        if (count($data)) {

            if (isset($data['isHidden'])) {
                if ($data['isHidden']) {
                    $data['c'] = -1;
                    $data['i'] = -1;
                    $data['d'] = -1;
                }
            }

            $data = $this->updateConsequences($id, $data);

            $data['anr'] = $anrId;

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

        /** @var InstanceConsequenceTable $table */
        $table = $this->get('table');
        $entity = $table->getEntity($id);

        $this->filterPostFields($data, $entity, ['anr', 'instance', 'object', 'scaleImpactType', 'ch', 'ih', 'dh']);

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
        $instanceConsequences = $table->getEntityByFields(['instance' => $instanceCurrentConsequence->get('instance')->get('id')]);
        foreach ($instanceConsequences as $instanceConsequence) {
            if (in_array($instanceConsequence->scaleImpactType->type, $rolfpTypes)) {
                $instanceC[] = (int) $instanceConsequence->get('c');
                $instanceI[] = (int) $instanceConsequence->get('i');
                $instanceD[] = (int) $instanceConsequence->get('D');
            }
        }

        $anrId = $instanceCurrentConsequence->anr->id;
        $instanceId = $instanceCurrentConsequence->instance->id;

        $data = [
            'c' => max($instanceC),
            'i' => max($instanceI),
            'd' => max($instanceD),
            'anr' => $anrId
        ];


        //if father instance exist, create instance for child
        $eventManager = new EventManager();
        $eventManager->setIdentifiers('instance');

        $sharedEventManager = $eventManager->getSharedManager();
        $eventManager->setSharedManager($sharedEventManager);
        $eventManager->trigger('patch', null, compact(['anrId', 'instanceId', 'data']));
    }

    /**
     * Patch by Scale Impact Type
     *
     * @param $scaleImpactTypeId
     * @param $data
     */
    public function patchByScaleImpactType($scaleImpactTypeId, $data) {
        /** @var InstanceConsequenceTable $instanceConsequenceTable */
        $instanceConsequenceTable = $this->get('table');
        $instancesConsequences = $instanceConsequenceTable->getEntityByFields(['scaleImpactType' => $scaleImpactTypeId]);

        foreach($instancesConsequences as $instanceConsequence) {
            $this->patch($instanceConsequence->id, $data);
        }
    }
}