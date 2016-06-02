<?php
namespace MonarcCore\Service;

/**
 * Threat Service
 *
 * Class ThreatService
 * @package MonarcCore\Service
 */
class ThreatService extends AbstractService
{
    protected $modelTable;
    protected $themeTable;

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

        $themeId = $entity->get('theme');
        if (!empty($themeId)) {
            $theme = $this->get('themeTable')->getEntity($themeId);
            $entity->setTheme($theme);
        }

        return $this->get('table')->save($entity);
    }


    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id,$data){

        $models = isset($data['models']) ? $data['models'] : array();
        unset($data['models']);

        $entity = $this->get('table')->getEntity($id);
        $entity->exchangeArray($data);
        $entity->get('models')->initialize();

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