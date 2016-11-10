<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Object;
use MonarcCore\Model\Table\InstanceRiskOpTable;
use MonarcCore\Model\Table\InstanceRiskTable;
use MonarcCore\Model\Table\ModelTable;
use MonarcCore\Model\Entity\Model;
use MonarcCore\Model\Table\ObjectTable;

/**
 * Model Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class ModelService extends AbstractService
{
    protected $dependencies = ['anr'];
    protected $anrService;
    protected $anrTable;
    protected $instanceRiskTable;
    protected $instanceService;
    protected $instanceRiskOpTable;
    protected $objectTable;
    protected $amvTable;
    protected $forbiddenFields = ['anr'];

    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true) {
        $entity = $this->get('entity');
        $entity->setLanguage($this->getLanguage());

        //anr
        $dataAnr = [
            'label1'        => $data['label1'],
            'label2'        => $data['label2'],
            'label3'        => $data['label3'],
            'label4'        => $data['label4'],
            'description1'  => $data['description1'],
            'description2'  => $data['description2'],
            'description3'  => $data['description3'],
            'description4'  => $data['description4'],
        ];
        /** @var AnrService $anrService */
        $anrService = $this->get('anrService');
        $anrId = $anrService->create($dataAnr);

        $data['anr'] = $anrId;

        //model
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        // If we reached here, our object is ready to be saved.
        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getModelWithAnr($id){


        $model = $this->get('table')->get($id);

        $anrId = $model['anr']->id;

        $anrModel = $model['anr']->getJsonArray();
        unset($anrModel['__initializer__']);
        unset($anrModel['__cloner__']);
        unset($anrModel['__isInitialized__']);

        /** @var InstanceService $instanceService */
        $instanceService = $this->get('instanceService');
        $anrModel['risksop'] = $instanceService->getRisksOp($anrId);

        $model['anr'] = $anrModel;

        return $model;
    }

    /**
     * Can accept object
     *
     * @param $modelId
     * @param $object
     * @param $context
     * @throws \Exception
     */
    public function canAcceptObject($modelId, $object, $context)
    {

        //retrieve data
        $data = $this->getEntity($modelId);

        //retrieve object model
        $model = $this->get('entity');
        $model->setDbAdapter($this->get('table')->getDb());
        $model->setLanguage($this->getLanguage());
        $model->exchangeArray($data);

        $asset = $object->asset;

        $authorized = false;

        if ($model->isGeneric) {
            if ($object->mode == Model::MODE_GENERIC) {
                $authorized = true;
            }
        } else {
            if ($model->isRegulator) { //model is specific and regulated
                if ($asset->mode == Model::MODE_SPECIFIC) {
                    if (count($asset->models)) {
                        $authorized = true;
                    }
                }
            } else { //can receive generic or specifi to himself
                if ($asset->mode == Model::MODE_SPECIFIC) {
                    if (count($asset->models)) {
                        $authorized = true;
                    }
                } else {
                    if ($object->mode == Model::MODE_SPECIFIC) { //aïe, l'objet est spécifique, il faut qu'on sache s'il l'est pour moi
                        //la difficulté c'est que selon le type de l'objet (bdc / anr) on va devoir piocher l'info de manière un peu différente
                        $objectType = 'bdc';
                        foreach($object->anrs as $anr) {
                            if ($anr->id == $model->anr->id) {
                                $objectType = 'anr';
                            }
                        }
                        if ($objectType == 'bdc') { //dans ce cas on vérifie que l'objet a des réplicats pour ce modèle
                            if ($context == Model::BACK_OFFICE) {
                                $authorized = true;
                            } else {
                                if (!is_null($object->id)) {
                                    $authorized = false;
                                    $objectsSource = $this->get('objectTable')->getEntityByFields(['source' => $object->id]);
                                    foreach($objectsSource as $source) {
                                        foreach($source->anrs as $anr) {
                                            if ($anr->id == $model->anr->id) {
                                                $authorized = true;
                                            }
                                        }
                                    }
                                }
                            }
                        } else { //l'objet est de type anr
                            if ($context == Model::BACK_OFFICE) { //si on est en back on laisse passé
                                $authorized = true;
                            }

                        }
                    } else {
                        $authorized = true;
                    }
                }
            }
        }

        if (!$authorized) {
            throw new \Exception('Bad mode for this object or models attached to asset incoherent with this object', 412);
        }
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

        $model = $this->get('table')->getEntity($id);
        if (!$model) {
            throw new \Exception('Entity does not exist', 412);
        }

        $this->verifyBeforeUpdate($model, $data);

        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        $this->filterPostFields($data, $model);

        $model->setDbAdapter($this->get('table')->getDb());
        $model->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \Exception('Data missing', 412);
        }

        $model->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($model, $dependencies);

        return $this->get('table')->save($model);
    }

    /**
     * Verify before update
     *
     * @param $model
     * @param $data
     * @throws \Exception
     */
    public function verifyBeforeUpdate($model, $data) {
        if (isset($data['isRegulator']) && isset($data['isGeneric']) &&
            $data['isRegulator'] && $data['isGeneric']) {
            throw new \Exception("A regulator model may not be generic", 412);
        }

        $modeObject = null;

        if(isset($data['isRegulator']) && $data['isRegulator'] && !$model->isRegulator){ // change to regulator
            //retrieve assets
            $assetsIds = [];
            foreach($model->assets as $asset) {
                $assetsIds[] = $asset->id;
            }
            if(!empty($assetsIds)){
                $amvs = $this->get('amvTable')->getEntityByFields(['asset'=>$assetsIds]);
                foreach($amvs as $amv){
                    if($amv->get('asset')->get('mode') == Object::MODE_SPECIFIC && $amv->get('threat')->get('mode') == Object::MODE_GENERIC && $amv->get('vulnerability')->get('mode') == Object::MODE_GENERIC){
                        throw new \Exception('You can not make this change. The level of integrity between the model and its objects would corrupt', 412);
                        return false;
                    }
                }
            }

            $modeObject = Object::MODE_GENERIC;
        }elseif(isset($data['isGeneric']) && $data['isGeneric'] && !$model->isGeneric){ // change to generic
            $modeObject = Object::MODE_SPECIFIC;
        }

        if(!is_null($modeObject)){
            $objects = $model->get('anr')->get('objects');
            if(!empty($objects)){
                foreach($objects as $o){
                    if($o->get('mode') == $modeObject){
                        throw new \Exception('You can not make this change. The level of integrity between the model and its objects would corrupt', 412);
                        return false;
                    }
                }
            }
        }

        return true;
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

    /**
     * Reset Current Default
     */
    protected function resetCurrentDefault() {
        $this->get('table')->resetCurrentDefault();
    }

    /**
     * Unset Specific Models
     *
     * @param $data
     */
    public function unsetSpecificModels(&$data) {
        /** @var ModelTable $modelTable */
        $modelTable = $this->get('table');
        foreach($data['models'] as $key => $modelId) {
            $model = $modelTable->getEntity($modelId);
            if (!$model->isGeneric) {
                unset($data['models'][$key]);
            }
        }
    }

    /**
     * Duplicate
     *
     * @param $modelId
     * @return mixed|null
     */
    public function duplicate($modelId) {
        //retrieve model
        /** @var ModelTable $modelTable */
        $modelTable = $this->get('table');
        $model = $modelTable->getEntity($modelId);

        //duplicate model
        $newModel = clone $model;
        $newModel->set('id',null);
        $suffix = ' (copié le '.date('m/d/Y à H:i').')';
        for($i=1;$i<=4;$i++){
            $newModel->set('label'.$i,$newModel->get('label'.$i).$suffix);
        }

        //duplicate anr
        /** @var AnrService $anrService */
        $anrService = $this->get('anrService');
        $newAnr = $anrService->duplicate($newModel->anr);

        $newModel->setAnr($newAnr);


        $id = $modelTable->save($newModel);

        return $id;
    }
}
