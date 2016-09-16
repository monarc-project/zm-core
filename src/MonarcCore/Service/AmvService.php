<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\AbstractEntity;
use MonarcCore\Model\Table\InstanceTable;
use MonarcCore\Model\Table\ModelTable;

/**
 * Amv Service
 *
 * Class AmvService
 * @package MonarcCore\Service
 */
class AmvService extends AbstractService
{
    protected $assetTable;
    protected $instanceTable;
    protected $measureTable;
    protected $modelTable;
    protected $threatTable;
    protected $vulnerabilityTable;

    protected $modelService;
    protected $historicalService;

    protected $errorMessage;

    protected $filterColumns = ['status'];

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null){
        $filterJoin = array(
            array(
                'as' => 'a',
                'rel' => 'asset',
            ),
            array(
                'as' => 'th',
                'rel' => 'threat',
            ),
            array(
                'as' => 'v',
                'rel' => 'vulnerability',
            ),
        );
        $filterLeft = array(
            array(
                'as' => 'm1',
                'rel' => 'measure1',
            ),
            array(
                'as' => 'm2',
                'rel' => 'measure2',
            ),
            array(
                'as' => 'm3',
                'rel' => 'measure3',
            ),
        );
        $filtersCol = array();
        $filtersCol[] = 'a.code';
        $filtersCol[] = 'a.label1';
        $filtersCol[] = 'a.label2';
        $filtersCol[] = 'a.label3';
        $filtersCol[] = 'a.description1';
        $filtersCol[] = 'a.description2';
        $filtersCol[] = 'a.description3';
        $filtersCol[] = 'th.code';
        $filtersCol[] = 'th.label1';
        $filtersCol[] = 'th.label2';
        $filtersCol[] = 'th.label3';
        $filtersCol[] = 'th.description1';
        $filtersCol[] = 'th.description2';
        $filtersCol[] = 'th.description3';
        $filtersCol[] = 'v.code';
        $filtersCol[] = 'v.label1';
        $filtersCol[] = 'v.label2';
        $filtersCol[] = 'v.label3';
        $filtersCol[] = 'v.description1';
        $filtersCol[] = 'v.description2';
        $filtersCol[] = 'v.description3';
        $filtersCol[] = 'm1.code';
        $filtersCol[] = 'm1.description1';
        $filtersCol[] = 'm1.description2';
        $filtersCol[] = 'm1.description3';
        $filtersCol[] = 'm2.code';
        $filtersCol[] = 'm2.description1';
        $filtersCol[] = 'm2.description2';
        $filtersCol[] = 'm2.description3';
        $filtersCol[] = 'm3.code';
        $filtersCol[] = 'm3.description1';
        $filtersCol[] = 'm3.description2';
        $filtersCol[] = 'm3.description3';

        return $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $filtersCol),
            $filterAnd,
            $filterJoin,
            $filterLeft
        );
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

    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null){
        $filterJoin = array(
            array(
                'as' => 'a',
                'rel' => 'asset',
            ),
            array(
                'as' => 'th',
                'rel' => 'threat',
            ),
            array(
                'as' => 'v',
                'rel' => 'vulnerability',
            ),
        );
        $filterLeft = array(
            array(
                'as' => 'm1',
                'rel' => 'measure1',
            ),
            array(
                'as' => 'm2',
                'rel' => 'measure2',
            ),
            array(
                'as' => 'm3',
                'rel' => 'measure3',
            ),
        );
        $filtersCol = array();
        $filtersCol[] = 'a.code';
        $filtersCol[] = 'a.label1';
        $filtersCol[] = 'a.label2';
        $filtersCol[] = 'a.label3';
        $filtersCol[] = 'a.description1';
        $filtersCol[] = 'a.description2';
        $filtersCol[] = 'a.description3';
        $filtersCol[] = 'th.code';
        $filtersCol[] = 'th.label1';
        $filtersCol[] = 'th.label2';
        $filtersCol[] = 'th.label3';
        $filtersCol[] = 'th.description1';
        $filtersCol[] = 'th.description2';
        $filtersCol[] = 'th.description3';
        $filtersCol[] = 'v.code';
        $filtersCol[] = 'v.label1';
        $filtersCol[] = 'v.label2';
        $filtersCol[] = 'v.label3';
        $filtersCol[] = 'v.description1';
        $filtersCol[] = 'v.description2';
        $filtersCol[] = 'v.description3';
        $filtersCol[] = 'm1.code';
        $filtersCol[] = 'm1.description1';
        $filtersCol[] = 'm1.description2';
        $filtersCol[] = 'm1.description3';
        $filtersCol[] = 'm2.code';
        $filtersCol[] = 'm2.description1';
        $filtersCol[] = 'm2.description2';
        $filtersCol[] = 'm2.description3';
        $filtersCol[] = 'm3.code';
        $filtersCol[] = 'm3.description1';
        $filtersCol[] = 'm3.description2';
        $filtersCol[] = 'm3.description3';

        return $this->get('table')->countFiltered(
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $filtersCol),
            $filterAnd,
            $filterJoin,
            $filterLeft
        );
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data) {

        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        $dependencies =  (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $authorized = $this->compliesRequirement($entity);

        if (!$authorized) {
            throw new \Exception($this->errorMessage);
        }

        $id = $this->get('table')->save($entity);

        //historisation
        $newEntity = $this->getEntity($id);

        //virtual name for historisation
        $label = [];
        for($i =1; $i<=4; $i++) {
            $name = [];
            $lab = 'label' . $i;
            if ($newEntity['asset']->$lab) {
                $name[] = $newEntity['asset']->$lab;
            }
            if ($newEntity['threat']->$lab) {
                $name[] = $newEntity['threat']->$lab;
            }
            if ($newEntity['vulnerability']->$lab) {
                $name[] = $newEntity['vulnerability']->$lab;
            }
            $label[] = implode('-', $name);
        }
        $this->label = $label;

        $this->historizeCreate('amv', $newEntity);

        return $id;
    }

    /**
     *
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function update($id, $data){

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());

        //clone current entity for retrieve difference with new
        $oldEntity = clone $entity;

        //virtual name for historisation
        $label = [];
        for($i =1; $i<=4; $i++) {
            $name = [];
            $lab = 'label' . $i;
            if ($entity->asset->$lab) {
                $name[] = $entity->asset->$lab;
            }
            if ($entity->threat->$lab) {
                $name[] = $entity->threat->$lab;
            }
            if ($entity->vulnerability->$lab) {
                $name[] = $entity->vulnerability->$lab;
            }
            $label[] = implode('-', $name);
        }
        $this->label = $label;

        $entity->exchangeArray($data);

        $newEntity = clone $entity;

        foreach($this->dependencies as $dependency) {
            $fieldValue = isset($data[$dependency]) ? $data[$dependency] : array();

            if (!empty($fieldValue)) {
                $tableName = preg_replace("/[0-9]/", "", $dependency)  . 'Table';
                $method = 'set' . ucfirst($dependency);
                $dependencyEntity = $this->get($tableName)->getEntity($fieldValue);
                $entity->$method($dependencyEntity);
            }
        }

        $authorized = $this->compliesRequirement($entity);

        if (!$authorized) {
            throw new \Exception($this->errorMessage);
        }

        //historisation
        $this->historizeUpdate('amv', $newEntity, $oldEntity);

        return $this->get('table')->save($entity);
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id) {

        //historisation
        $entity = $this->getEntity($id);

        if ($entity) {

            //virtual name for historisation
            $label = [];
            for ($i = 1; $i <= 4; $i++) {
                $name = [];
                $lab = 'label' . $i;
                if ($entity['asset']->$lab) {
                    $name[] = $entity['asset']->$lab;
                }
                if ($entity['threat']->$lab) {
                    $name[] = $entity['threat']->$lab;
                }
                if ($entity['vulnerability']->$lab) {
                    $name[] = $entity['vulnerability']->$lab;
                }
                $label[] = implode('-', $name);
            }
            $this->label = $label;

            $this->historizeDelete('amv', $entity);

            $this->get('table')->delete($id);
        }
    }

    /**
     * Complies Requirement
     *
     * @param $amv
     * @param null $asset
     * @param null $assetModels
     * @param null $threat
     * @param null $threatModels
     * @param null $vulnerability
     * @param null $vulnerabilityModels
     * @return bool
     * @throws \Exception
     */
    public function compliesRequirement($amv, $asset = null, $assetModels = null, $threat = null, $threatModels = null, $vulnerability = null, $vulnerabilityModels = null) {

        //asset
        $assetMode = (is_null($asset)) ? $amv->getAsset()->mode : $asset->mode;
        $assetModels = (is_null($assetModels)) ? $amv->getAsset()->getModels() : $assetModels;
        $assetModelsIds = [];
        $assetModelsIsRegulator = [];
        foreach ($assetModels as $model) {
            if (!is_object($model)) {
                $model = $this->get('modelService')->getEntity($model);
                $assetModelsIds[] = $model['id'];
                $assetModelsIsRegulator[] = $model['isRegulator'];
            } else {
                $assetModelsIds[] = $model->id;
                $assetModelsIsRegulator[] = $model->isRegulator;
            }
        }

        //threat
        $threatMode = (is_null($threat)) ? $amv->getThreat()->mode : $threat->mode;
        $threatModels = (is_null($threatModels)) ? $amv->getThreat()->getModels() : $threatModels;
        $threatModelsIds = [];
        foreach ($threatModels as $model) {
            $threatModelsIds[] = (is_object($model)) ? $model->id : $model;
        }

        //vulnerability
        $vulnerabilityMode = (is_null($vulnerability)) ? $amv->getVulnerability()->mode : $vulnerability->mode;
        $vulnerabilityModels = (is_null($vulnerabilityModels)) ? $amv->getVulnerability()->getModels() : $vulnerabilityModels;
        $vulnerabilityModelsIds = [];
        foreach ($vulnerabilityModels as $model) {
            $vulnerabilityModelsIds[] = (is_object($model)) ? $model->id : $model;
        }

        $result = $this->compliesControl($assetMode, $threatMode, $vulnerabilityMode, $assetModelsIds, $threatModelsIds, $vulnerabilityModelsIds, $assetModelsIsRegulator);

        if (strlen($this->errorMessage)) {
            throw new \Exception($this->errorMessage, 412);
        }

        return $result;
    }


    /**
     * Complies control
     *
     * @param $assetMode
     * @param $threatMode
     * @param $vulnerabilityMode
     * @param $assetModelsIds
     * @param $threatModelsIds
     * @param $vulnerabilityModelsIds
     * @param $assetModelsIsRegulator
     * @return bool
     * @throws \Exception
     */
    public function compliesControl($assetMode, $threatMode, $vulnerabilityMode, $assetModelsIds, $threatModelsIds, $vulnerabilityModelsIds, $assetModelsIsRegulator) {

        if (!is_array($assetModelsIds)) {
            $assetModelsIds = [$assetModelsIds];
        }
        if (!is_array($threatModelsIds)) {
            $threatModelsIds = [$threatModelsIds];
        }
        if (!is_array($vulnerabilityModelsIds)) {
            $vulnerabilityModelsIds = [$vulnerabilityModelsIds];
        }
        if (!is_array($assetModelsIsRegulator)) {
            $assetModelsIsRegulator = [$assetModelsIsRegulator];
        }

        $this->errorMessage = '';

        if ((!$assetMode) && (!$threatMode) && (!$vulnerabilityMode)) {
            return true;
        } else if (is_null($assetMode)) {
            $this->errorMessage = 'Asset mode can\'t be null';
            return false;
        } else  if ($assetMode && $threatMode && $vulnerabilityMode) {
            foreach ($assetModelsIds as $modelId) {
                if ((in_array($modelId, $threatModelsIds)) && (in_array($modelId, $vulnerabilityModelsIds))) {
                    return true;
                }
            }
            $this->errorMessage = 'One model must be common to asset, threat and vulnerability';
            return false;
        } else {
            foreach ($assetModelsIsRegulator as $modelIsRegulator) {
                if ($modelIsRegulator) {
                    $this->errorMessage = 'All asset models must\'nt be regulator';
                    return false;
                }
            }
            return true;
        }
    }

    /**
     * Check AMV Integrity Level
     *
     * @param $models
     * @param null $asset
     * @param null $threat
     * @param null $vulnerability
     * @param bool $follow
     * @return bool
     */
    public function checkAMVIntegrityLevel($models, $asset = null, $threat = null, $vulnerability = null, $follow = false) {
        $amvs = $this->get('table')->findByAMV($asset, $threat, $vulnerability);

        foreach($amvs as $amv){

            $amv = $this->get('table')->getEntity($amv['id']);
            $assetModels = ($asset || $follow) ? $models : null;
            $threatsModels = ($threat || $follow) ? $models : null;
            $vulnerabilityModels = ($vulnerability || $follow) ? $models : null;
            if(!$this->compliesRequirement($amv, $asset, $assetModels, $threat, $threatsModels, $vulnerability, $vulnerabilityModels)){
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure Assets Integrity If Enforced
     *
     * @param $models
     * @param null $asset
     * @param null $threat
     * @param null $vulnerability
     * @return bool
     */
    public function ensureAssetsIntegrityIfEnforced($models, $asset = null, $threat = null, $vulnerability = null) {
        $amvs = $this->get('table')->findByAMV($asset, $threat, $vulnerability);

        if (count($amvs)) {
            $amvAssetsIds = array();
            foreach ($amvs as $amv) {
                $amvAssetsIds[$amv['assetId']] = $amv['assetId'];
            }

            if (!empty($amvAssetsIds)) {
                $amvAssets = [];
                foreach ($amvAssetsIds as $assetId) {

                    $entity = $this->get('assetTable')->getEntity($assetId);
                    $entity->setDbAdapter($this->get('assetTable')->getDb());
                    $entity->setLanguage($this->getLanguage());
                    $entity->get('models')->initialize();

                    $amvAssets[] = $entity;
                }
                if (!empty($amvAssets)) {
                    foreach ($amvAssets as $amvAsset) {
                        if (!$this->checkModelsInstantiation($amvAsset, $models)) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check Models Instantiation
     *
     * @param $asset
     * @param $newModelsIds
     * @return bool
     */
    public function checkModelsInstantiation($asset, $newModelsIds)
    {
        $modelsIds = array_combine($newModelsIds, $newModelsIds);//clefs = valeurs

        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instances = $instanceTable->getEntityByFields(['asset' => $asset->id]);

        if (!empty($instances)) {
            $anrs = [];
            foreach ($instances as $instance) {
                $anrs[$instance->anr->id] = $instance->anr->id;
            }

            foreach ($anrs as $anrId) {
                /** @var ModelTable $modelTable */
                $modelTable = $this->get('modelTable');
                $models = $modelTable->getEntityByFields(['anr' => $anrId]);
                foreach ($models as $model) {
                    if (!isset($modelsIds[$model->id])) {
                        return false;
                        //ne pas supprimer à un asset un modele specifique, si celui-ci est lié à l’asset via une instance dans une anr (via l’objet)
                    }
                }
            }
        }

        return true;
    }

    /**
     * Enforce Amv To Follow
     *
     * @param $models
     * @param null $asset
     * @param null $threat
     * @param null $vulnerability
     */
    public function enforceAMVtoFollow($models, $asset = null, $threat = null, $vulnerability = null)
    {
        $amvs = $this->get('table')->findByAMV($asset, $threat, $vulnerability);

        if (!count($amvs)) {

            $amvAssetsIds = array();
            $amvThreatsIds = array();
            $amvVulnerabilitiesIds = array();

            foreach ($amvs as $amv) {
                if (is_null($asset)) $amvAssetsIds[$amv['assetId']] = $amv['assetId'];
                if (is_null($threat)) $amvThreatsIds[$amv['threatId']] = $amv['threatId'];
                if (is_null($vulnerability)) $amvVulnerabilitiesIds[$amv['vulnerabilityId']] = $amv['vulnerabilityId'];
            }

            if (count($amvAssetsIds)) $this->enforceToFollow($amvAssetsIds, $models, 'asset');
            if (count($amvThreatsIds)) $this->enforceToFollow($amvThreatsIds, $models, 'threat');
            if (count($amvVulnerabilitiesIds)) $this->enforceToFollow($amvVulnerabilitiesIds, $models, 'vulnerability');
        }
    }

    /**
     * Enforce To Follow
     *
     * @param $entitiesIds
     * @param $models
     * @param $type
     */
    public function enforceToFollow($entitiesIds, $models, $type) {

        $tableName = $type . 'Table';
        $serviceName = ucfirst($type) . 'Service';

        foreach($entitiesIds as $entitiesId) {
            $entity = $this->get($tableName)->getEntity($entitiesId);
            $entity->setDbAdapter($this->get($tableName)->getDb());
            $entity->setLanguage($this->getLanguage());
            $entity->get('models')->initialize();

            if ($entity->mode == AbstractEntity::IS_SPECIFIC) { //petite sécurité pour pas construire de la daube

                foreach($entity->get('models') as $model){
                    $entity->get('models')->removeElement($model);
                }

                foreach($models as $modelId) {
                    $model = $this->get('modelTable')->getEntity($modelId);
                    $entity->setModel($modelId, $model);
                }

                $this->get($tableName)->save($entity);
            }
        }
    }
}