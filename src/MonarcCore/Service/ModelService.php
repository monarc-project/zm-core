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
    protected $instanceRiskOpTable;
    protected $objectTable;

    protected $filterColumns = array(
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
    );

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {
        $entity = $this->get('entity');
        $entity->setLanguage($this->getLanguage());

        //anr
        $dataAnr = [
            'label1' => 'ANR',
            'label2' => 'ANR',
            'label3' => 'ANR',
            'label4' => 'ANR',
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

        /** @var InstanceRiskTable $instanceRiskTable */
        $instanceRiskTable= $this->get('instanceRiskTable');
        $anrRisks = $instanceRiskTable->getEntityByFields(['anr' => $anrId]);

        if ($anrRisks) {
            $risksDepencendies = ['asset', 'threat', 'vulnerability'];
            foreach ($anrRisks as $anrRisk) {
                $anrRiskArray = $anrRisk->getJsonArray();
                foreach($risksDepencendies as $risksDepencendy) {
                    $dependencyArray = $anrRiskArray[$risksDepencendy]->getJsonArray();
                    unset($dependencyArray['__initializer__']);
                    unset($dependencyArray['__cloner__']);
                    unset($dependencyArray['__isInitialized__']);
                    $anrRiskArray[$risksDepencendy] = $dependencyArray;
                }
                unset($anrRiskArray['id']);
                unset($anrRiskArray['anr']);
                unset($anrRiskArray['amv']);
                unset($anrRiskArray['instance']);
                $anrModel['risks'][$anrRisk->threat->id] = $anrRiskArray;
            }

            $anrModel['risks'] = array_values($anrModel['risks']);
        }

        /** @var InstanceRiskOpTable $instanceRiskOpTable */
        $instanceRiskOpTable= $this->get('instanceRiskOpTable');
        $anrRisksOp = $instanceRiskOpTable->getEntityByFields(['anr' => $anrId]);

        if ($anrRisksOp) {

            $risksOpDepencendies = ['rolfRisk'];
            foreach ($anrRisksOp as $anrRiskOp) {
                $anrRiskOpArray = $anrRiskOp->getJsonArray();
                foreach($risksOpDepencendies as $risksOpDepencendy) {
                    $dependencyArray = $anrRiskOpArray[$risksOpDepencendy]->getJsonArray();
                    unset($dependencyArray['__initializer__']);
                    unset($dependencyArray['__cloner__']);
                    unset($dependencyArray['__isInitialized__']);
                    $anrRiskOpArray[$risksOpDepencendy] = $dependencyArray;
                }
                unset($anrRiskOpArray['id']);
                unset($anrRiskOpArray['anr']);
                unset($anrRiskOpArray['instance']);
                unset($anrRiskOpArray['object']);
                $anrModel['risksop'][$anrRisk->rolfRisk->id] = $anrRiskOpArray;
            }

            $anrModel['risksop'] = array_values($anrModel['risksop']);

        }

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
            if ($object->mode == Model::IS_GENERIC) {
                $authorized = true;
            }
        } else {
            if ($model->isRegulator) { //model is specific and regulated
                if ($asset->mode == Model::IS_SPECIFIC) {
                    if (count($asset->models)) {
                        $authorized = true;
                    }
                }
            } else { //can receive generic or specifi to himself
                if ($asset->mode == Model::IS_SPECIFIC) {
                    if (count($asset->models)) {
                        $authorized = true;
                    }
                } else {
                    if ($object->mode == Model::IS_SPECIFIC) { //aïe, l'objet est spécifique, il faut qu'on sache s'il l'est pour moi
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
        if (isset($data['isRegulator']) && isset($data['isGeneric']) &&
            $data['isRegulator'] && $data['isGeneric']) {
            throw new \Exception("A regulator model may not be generic", 412);
        }

        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        parent::update($id, $data);
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
}