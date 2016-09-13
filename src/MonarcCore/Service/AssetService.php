<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Asset;

/**
 * Asset Service
 *
 * Class AssetService
 * @package MonarcCore\Service
 */
class AssetService extends AbstractService
{
    protected $modelTable;
    protected $amvService;
    protected $modelService;
    protected $objectService;

    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
        'code',
    ];

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        $models = $entity->get('models');
        if (!empty($models)) {
            $modelTable = $this->get('modelTable');
            foreach ($models as $key => $modelId) {
                if (!empty($modelId)) {
                    $model = $modelTable->getEntity($modelId);
                    $entity->setModel($key, $model);
                }
            }
        }

        return $this->get('table')->save($entity);
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

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (($entity->mode == Asset::IS_SPECIFIC) && ($data['mode'] == Asset::IS_GENERIC)) {
            if (isset($data['models'])) {
                //delete specific model
                /** @var ModelService $modelService */
                $modelService = $this->get('modelService');
                $modelService->unsetSpecificModels($data);
            }
        }

        $models = isset($data['models']) ? $data['models'] : array();
        $follow = isset($data['follow']) ? $data['follow'] : null;
        unset($data['models']);
        unset($data['follow']);

        $entity->exchangeArray($data);
        $entity->get('models')->initialize();

        /** @var AmvService $amvService */
        $amvService  = $this->get('amvService');
        if (!$amvService->checkAMVIntegrityLevel($models, $entity, null, null, $follow)) {
            throw new \Exception('Integrity AMV links violation', 412);
        }

        if ($entity->mode == Asset::IS_SPECIFIC) {
            $associateObjects = $this->get('objectService')->getGenericByAsset($entity);
            if (count($associateObjects)) {
                throw new \Exception('Integrity AMV links violation', 412);
            }
        }

        if (!$amvService->checkModelsInstantiation($entity, $models)) {
            throw new \Exception('Asset exist in another model', 412);
        }

        foreach($entity->get('models') as $model){
            if (in_array($model->get('id'), $models)){
                unset($models[array_search($model->get('id'), $models)]);
            } else {
                $entity->get('models')->removeElement($model);
            }
        }

        if (!empty($models)){
            $modelTable = $this->get('modelTable');
            foreach ($models as $key => $modelId) {
                if(!empty($modelId)){
                    $model = $modelTable->getEntity($modelId);
                    $entity->setModel($key, $model);
                }
            }

            if ($follow) {
                $amvService->enforceAMVtoFollow($models, null, null, $entity);
            }
        }

        return $this->get('table')->save($entity);
    }
}