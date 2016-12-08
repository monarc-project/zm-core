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
    protected $forbiddenFields = ['anr', 'instance', 'object', 'scaleImpactType'];

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patchConsequence($id, $data, $patchInstance = true, $local = true, $fromInstance = false){

        $anrId = $data['anr'];

        if (count($data)) {

            $entity = $this->get('table')->getEntity($id);

            if (isset($data['isHidden'])) {
                if ($data['isHidden']) {
                    $data['c'] = -1;
                    $data['i'] = -1;
                    $data['d'] = -1;
                    if ($local) {
                        $data['locallyTouched'] = 1;
                    }
                } else {
                    if ($local) {
                        $data['locallyTouched'] = 0;
                    } else {
                        if ($entity->locallyTouched) {
                            $data['isHidden'] = 1;
                        }
                    }
                }
            } else {
                if ($entity->isHidden) {
                    $data['c'] = -1;
                    $data['i'] = -1;
                    $data['d'] = -1;
                }
            }

            $data = $this->updateConsequences($id, $data);

            $data['anr'] = $anrId;

            $this->verifyRates($anrId, $data, $this->getEntity($id));

            parent::patch($id,$data);

            if ($patchInstance) {
                $this->updateInstanceImpacts($id, $fromInstance);
            }
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
    public function update($id, $data){
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

        return $data;
    }

    /**
     * Update Instance Impacts
     *
     * @param $instanceConsequencesId
     */
    protected function updateInstanceImpacts($instanceConsequencesId, $fromInstance = false) {

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
                $instanceD[] = (int) $instanceConsequence->get('d');
            }
        }

        $anrId = $instanceCurrentConsequence->anr->id;
        $instanceId = $instanceCurrentConsequence->instance->id;

        $data = [
            'c' => max($instanceC),
            'i' => max($instanceI),
            'd' => max($instanceD),
        ];


        $parent = $instanceCurrentConsequence->get('instance')->get('parent');
        foreach($data as $k => $v){
            $data[$k.'h'] = ($v == -1)?1:0;
            if($data[$k.'h']){ // hérité: on prend la valeur du parent
                if(!empty($parent)){
                    $data[$k] = $parent->get($k);
                }
            }
        }

        $data['anr'] = $anrId;

        if( ! $fromInstance ){
            //if father instance exist, create instance for child
            $eventManager = new EventManager();
            $eventManager->setIdentifiers('instance');
            $sharedEventManager = $eventManager->getSharedManager();
            $eventManager->setSharedManager($sharedEventManager);
            $eventManager->trigger('patch', null, compact(['anrId', 'instanceId', 'data']));
        }
        else{
            $instance = $instanceCurrentConsequence->get('instance')->initialize();
            $instance->exchangeArray($data);
            $this->get('instanceTable')->save($instance);
        }
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

        $consequencesIds = [];

        $i = 1;
        foreach($instancesConsequences as $instanceConsequence) {
            $last = ($i == count($instancesConsequences)) ? true : false;
            $this->patchConsequence($instanceConsequence->id, $data, $last, false);
            $consequencesIds[$instanceConsequence->id] = $instanceConsequence->id;
            $i++;
        }

        if(!empty($consequencesIds)){
            foreach($consequencesIds as $idc){
                $this->updateInstanceImpacts($idc);
            }
        }
    }
}
