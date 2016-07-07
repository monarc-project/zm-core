<?php
namespace MonarcCore\Service;

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

        $models = isset($data['models']) ? $data['models'] : array();
        unset($data['models']);

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());
        $entity->exchangeArray($data);
        $entity->get('models')->initialize();

        if (!$this->get('amvService')->checkAMVIntegrityLevel($id, null, null)) {
            throw new \Exception('Integrity AMV links violation', 412);
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
        }

        return $this->get('table')->save($entity);
    }
}