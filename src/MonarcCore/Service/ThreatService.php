<?php
namespace MonarcCore\Service;
use MonarcCore\Model\Entity\Threat;

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
    protected $amvService;

    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
        'code',
    ];
    protected $dependencies = ['theme'];

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
     * @throws \Exception
     */
    public function update($id,$data){

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (($entity->mode == Threat::IS_SPECIFIC) && ($data['mode'] == Threat::IS_GENERIC)) {
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

        if (!$this->get('amvService')->checkAMVIntegrityLevel($models, null, $entity, null, $follow)) {
            throw new \Exception('Integrity AMV links violation', 412);
        }

        if (($follow) && (!$this->get('amvService')->ensureAssetsIntegrityIfEnforced($models, null, $entity, null))) {
            throw new \Exception('Assets Integrity', 412);
        }

        foreach($entity->get('models') as $model){
            if (in_array($model->get('id'), $models)){
                unset($models[array_search($model->get('id'), $models)]);
            } else {
                $entity->get('models')->removeElement($model);
            }
        }

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        if (!empty($models)){
            $modelTable = $this->get('modelTable');
            foreach ($models as $key => $modelId) {
                if(!empty($modelId)){
                    $model = $modelTable->getEntity($modelId);
                    $entity->setModel($key, $model);
                }
            }


            if ($follow) {
                $this->get('amvService')->enforceAMVtoFollow($models, null, null, $entity);
            }
        }

        return $this->get('table')->save($entity);
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
        $this->filterPatchFields($data, ['anr']);

        parent::patch($id, $data);
    }
}