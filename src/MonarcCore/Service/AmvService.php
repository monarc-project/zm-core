<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

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
    protected $anrTable;
    protected $assetTable;
    protected $instanceTable;
    protected $measureTable;
    protected $modelTable;
    protected $threatTable;
    protected $vulnerabilityTable;
    protected $historicalService;
    protected $errorMessage;
    protected $filterColumns = ['status'];
    protected $dependencies = ['anr', 'asset', 'threat', 'vulnerability', 'measure[1]()', 'measure[2]()', 'measure[3]()'];
    protected $forbiddenFields = ['anr'];

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        list($filterJoin,$filterLeft,$filtersCol) = $this->get('entity')->getFiltersForService();

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
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        $entity = $this->get('table')->getEntity($id);
        $entity->exchangeArray($data, true);

        $this->setDependencies($entity, $this->dependencies);

        parent::patch($id, $data);
    }

    /**
     * Get Filtered Count
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param null $filterAnd
     * @return mixed
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        list($filterJoin,$filterLeft,$filtersCol) = $this->get('entity')->getFiltersForService();

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
     * @param bool $last
     * @return mixed
     * @throws \MonarcCore\Exception\Exception
     */
    public function create($data, $last = true)
    {
        $entity = $this->get('entity');
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $authorized = $this->compliesRequirement($entity);

        if (!$authorized) {
            throw new \MonarcCore\Exception\Exception($this->errorMessage);
        }

        $id = $this->get('table')->save($entity);

        //historisation
        $newEntity = $this->getEntity($id);

        //virtual name for historisation
        $name = [];
        $lab = 'code';
        if ($newEntity['asset']->$lab) {
            $name[] = $newEntity['asset']->$lab;
        }
        if ($newEntity['threat']->$lab) {
            $name[] = $newEntity['threat']->$lab;
        }
        if ($newEntity['vulnerability']->$lab) {
            $name[] = $newEntity['vulnerability']->$lab;
        }
        $name = implode(' - ', $name);
        $this->label = [$name, $name, $name, $name];

        //details
        $fields = [
            'anr' => 'code',
            'asset' => 'code',
            'threat' => 'code',
            'vulnerability' => 'code',
            'measure1' => 'code',
            'measure2' => 'code',
            'measure3' => 'code'
        ];
        $details = [];
        foreach ($fields as $key => $field) {
            if (!empty($newEntity[$key])) {
                $details[] = $key . ' => ' . $newEntity[$key]->$field;
            }
        }

        $this->historizeCreate('amv', $newEntity, $details);

        return $id;
    }

    /**
     *
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \MonarcCore\Exception\Exception
     */
    public function update($id, $data)
    {
        $this->filterPatchFields($data);

        if (empty($data)) {
            throw new \MonarcCore\Exception\Exception('Data missing', 412);
        }

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());

        //clone current entity for retrieve difference with new
        $oldEntity = clone $entity;

        //virtual name for historisation
        $name = [];
        if ($entity->get('asset')->get('code')) {
            $name[] = $entity->get('asset')->get('code');
        }
        if ($entity->get('threat')->get('code')) {
            $name[] = $entity->get('threat')->get('code');
        }
        if ($entity->get('vulnerability')->get('code')) {
            $name[] = $entity->get('vulnerability')->get('code');
        }
        $name = implode(' - ', $name);
        $this->label = [$name, $name, $name, $name];

        $entity->exchangeArray($data);

        $this->setDependencies($entity, $this->dependencies);

        $authorized = $this->compliesRequirement($entity);

        if (!$authorized) {
            throw new \MonarcCore\Exception\Exception($this->errorMessage);
        }

        //historisation
        $this->historizeUpdate('amv', $entity, $oldEntity);

        return $this->get('table')->save($entity);
    }

    /**
     * Compare Entities
     *
     * @param $newEntity
     * @param $oldEntity
     * @return array
     */
    public function compareEntities($newEntity, $oldEntity)
    {
        $deps = [];
        foreach ($this->dependencies as $dep) {
            $propertyname = $dep;
            $matching = [];
            if (preg_match("/(\[([a-z0-9]*)\])\(([a-z0-9]*)\)$/", $dep, $matching)) {//si c'est 0 c'est pas bon non plus
                $propertyname = str_replace($matching[0], $matching[2], $dep);
                $dep = str_replace($matching[0], $matching[3], $dep);
            }
            $deps[$propertyname] = $propertyname;
        }

        $exceptions = ['creator', 'created_at', 'updater', 'updated_at', 'inputFilter', 'dbadapter', 'parameters', 'language'];

        $diff = [];
        foreach ($newEntity->getJsonArray() as $key => $value) {
            if (!in_array($key, $exceptions)) {
                if (isset($deps[$key])) {
                    $oldValue = $oldEntity->get($key);
                    if (!empty($oldValue) && is_object($oldValue)) {
                        $oldValue = $oldValue->get('code');
                    }
                    if (!empty($value) && is_object($value)) {
                        $value = $value->get('code');
                    }
                    if ($oldValue != $value) {
                        if (empty($oldValue)) {
                            $oldValue = '-';
                        }
                        if (empty($value)) {
                            $value = '-';
                        }
                        $diff[] = $key . ': ' . $oldValue . ' => ' . $value;
                    }
                } elseif ($oldEntity->get($key) != $value) {
                    $diff[] = $key . ': ' . $oldEntity->get($key) . ' => ' . $value;
                }
            }
        }

        return $diff;
    }

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id)
    {
        //historisation
        $entity = $this->get('table')->getEntity($id);

        if ($entity) {

            //virtual name for historisation
            $name = [];

            if ($entity->get('asset')->get('code')) {
                $name[] = $entity->get('asset')->get('code');
            }
            if ($entity->get('threat')->get('code')) {
                $name[] = $entity->get('threat')->get('code');
            }
            if ($entity->get('vulnerability')->get('code')) {
                $name[] = $entity->get('vulnerability')->get('code');
            }

            $name = implode(' - ', $name);
            $this->label = [$name, $name, $name, $name];

            //details
            $fields = [
                'anr' => 'code',
                'asset' => 'code',
                'threat' => 'code',
                'vulnerability' => 'code',
                'measure1' => 'code',
                'measure2' => 'code',
                'measure3' => 'code'
            ];
            $details = [];
            foreach ($fields as $key => $field) {
                if ($entity->$key) {
                    $details[] = $key . ' => ' . $entity->$key->$field;
                }
            }

            $this->historizeDelete('amv', $entity, $details);

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
     * @throws \MonarcCore\Exception\Exception
     */
    public function compliesRequirement($amv, $asset = null, $assetModels = null, $threat = null, $threatModels = null, $vulnerability = null, $vulnerabilityModels = null)
    {
        //asset
        $asset = (is_null($asset)) ? $amv->getAsset() : $asset;
        if ($asset->get('type') == 1) {
            throw new \MonarcCore\Exception\Exception('Asset can\'t be primary', 412);
        }

        $assetMode = $asset->mode;
        $assetModels = (is_null($assetModels)) ? $amv->getAsset()->getModels() : $assetModels;
        $assetModelsIds = [];
        $assetModelsIsRegulator = false;
        foreach ($assetModels as $model) {
            if (!is_object($model)) {
                $model = $this->get('modelTable')->get($model);
                $assetModelsIds[] = $model['id'];
                if ($model['isRegulator']) {
                    $assetModelsIsRegulator = true;
                }
            } else {
                $assetModelsIds[] = $model->id;
                if ($model->isRegulator) {
                    $assetModelsIsRegulator = true;
                }
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
            throw new \MonarcCore\Exception\Exception($this->errorMessage, 412);
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
     * @throws \MonarcCore\Exception\Exception
     */
    public function compliesControl($assetMode, $threatMode, $vulnerabilityMode, $assetModelsIds, $threatModelsIds, $vulnerabilityModelsIds, $assetModelsIsRegulator)
    {
        $this->errorMessage = '';

        if (!$assetMode && !$threatMode && !$vulnerabilityMode) { // 0 0 0
            return true;
        } elseif (!$assetMode && ($threatMode || $vulnerabilityMode)) { // 0 0 1 || 0 1 0 || 0 1 1
            $this->errorMessage = 'The tuple asset / threat / vulnerability is invalid';
            return false;
        } elseif ($assetMode && (!$threatMode || !$vulnerabilityMode)) { // 1 0 0 || 1 0 1 || 1 1 0
            if (!$assetModelsIsRegulator) { // & si et seulement s'il n'y a aucun modèle régulateur pour l'asset
                if (!$threatMode && !$vulnerabilityMode) { // 1 0 0
                    return true;
                } else { // & on doit tester les modèles
                    if (empty($assetModelsIds)) {
                        $assetModelsIds = [];
                    } elseif (!is_array($assetModelsIds)) {
                        $assetModelsIds = [$assetModelsIds];
                    }
                    $toTest = [];
                    if ($vulnerabilityMode) { // 1 0 1
                        $toTest = $vulnerabilityModelsIds;
                        if (empty($toTest)) {
                            $toTest = [];
                        } elseif (!is_array($toTest)) {
                            $toTest = [$toTest];
                        }
                    } else { // 1 1 0
                        $toTest = $threatModelsIds;
                        if (empty($toTest)) {
                            $toTest = [];
                        } elseif (!is_array($toTest)) {
                            $toTest = [$toTest];
                        }
                    }
                    $diff1 = array_diff($assetModelsIds, $toTest);
                    if (empty($diff2)) {
                        $diff2 = array_diff($toTest, $assetModelsIds);
                        if (empty($diff2)) {
                            return true;
                        }
                    }
                    $this->errorMessage = 'All models must be common to asset and ' . $vulnerabilityMode ? 'vulnerability' : 'threat';
                    return false;
                }
            } else {
                $this->errorMessage = 'Asset\'s model must not be regulator';
                return false;
            }
        } elseif ($assetMode && $threatMode && $vulnerabilityMode) { // 1 1 1 & on doit tester les modèles
            if (empty($assetModelsIds)) {
                $assetModelsIds = [];
            } elseif (!is_array($assetModelsIds)) {
                $assetModelsIds = [$assetModelsIds];
            }
            if (empty($threatModelsIds)) {
                $threatModelsIds = [];
            } elseif (!is_array($threatModelsIds)) {
                $threatModelsIds = [$threatModelsIds];
            }
            if (empty($vulnerabilityModelsIds)) {
                $vulnerabilityModelsIds = [];
            } elseif (!is_array($vulnerabilityModelsIds)) {
                $vulnerabilityModelsIds = [$vulnerabilityModelsIds];
            }
            $diff1 = array_diff($assetModelsIds, $threatModelsIds);
            $diff15 = array_diff($threatModelsIds, $assetModelsIds);
            if (empty($diff1) && empty($diff15)) {
                $diff2 = array_diff($assetModelsIds, $vulnerabilityModelsIds);
                $diff25 = array_diff($vulnerabilityModelsIds, $assetModelsIds);
                if (empty($diff2) && empty($diff25)) {
                    $diff3 = array_diff($threatModelsIds, $vulnerabilityModelsIds);
                    $diff35 = array_diff($vulnerabilityModelsIds, $threatModelsIds);
                    if (empty($diff3) && empty($diff35)) {
                        return true;
                    }
                }
            }
            $this->errorMessage = 'All models must be common to asset, threat and vulnerability';
            return false;
        } else {
            $this->errorMessage = 'Missing datas';
            return false;
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
    public function checkAMVIntegrityLevel($models, $asset = null, $threat = null, $vulnerability = null, $follow = false)
    {
        $amvs = $this->get('table')->findByAMV($asset, $threat, $vulnerability);

        foreach ($amvs as $amv) {

            $amv = $this->get('table')->getEntity($amv['id']);
            $assetModels = ($asset || $follow) ? $models : null;
            $threatsModels = ($threat || $follow) ? $models : null;
            $vulnerabilityModels = ($vulnerability || $follow) ? $models : null;
            if (!$this->compliesRequirement($amv, $asset, $assetModels, $threat, $threatsModels, $vulnerability, $vulnerabilityModels)) {
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
    public function ensureAssetsIntegrityIfEnforced($models, $asset = null, $threat = null, $vulnerability = null)
    {
        $amvs = $this->get('table')->findByAMV($asset, $threat, $vulnerability);

        if (count($amvs)) {
            $amvAssetsIds = [];
            foreach ($amvs as $amv) {
                $amvAssetsIds[$amv['assetId']] = $amv['assetId'];
            }

            if (!empty($amvAssetsIds)) {
                $amvAssets = [];
                foreach ($amvAssetsIds as $assetId) {

                    $entity = $this->get('assetTable')->getEntity($assetId);
                    $entity->setDbAdapter($this->get('assetTable')->getDb());
                    $entity->setLanguage($this->getLanguage());
                    if ($entity->get('models')) {
                        $entity->get('models')->initialize();
                    }

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
                        return false; //don't remove to an asset of specific model if it is linked to asset by an instance in an anr (by object)
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

        if (count($amvs) > 0) {

            $amvAssetsIds = [];
            $amvThreatsIds = [];
            $amvVulnerabilitiesIds = [];

            foreach ($amvs as $amv) {
                $amvAssetsIds[$amv['assetId']] = $amv['assetId'];
                $amvThreatsIds[$amv['threatId']] = $amv['threatId'];
                $amvVulnerabilitiesIds[$amv['vulnerabilityId']] = $amv['vulnerabilityId'];
            }
            if (!is_null($asset)) {
                unset($amvAssetsIds[$asset->get('id')]);
            }
            if (!is_null($threat)) {
                unset($amvThreatsIds[$threat->get('id')]);
            }
            if (!is_null($vulnerability)) {
                unset($amvVulnerabilitiesIds[$vulnerability->get('id')]);
            }

            if (count($amvAssetsIds)) {
                $this->enforceToFollow($amvAssetsIds, $models, 'asset');
            }
            if (count($amvThreatsIds)) {
                $this->enforceToFollow($amvThreatsIds, $models, 'threat');
            }
            if (count($amvVulnerabilitiesIds)) {
                $this->enforceToFollow($amvVulnerabilitiesIds, $models, 'vulnerability');
            }
        }
    }

    /**
     * Enforce To Follow
     *
     * @param $entitiesIds
     * @param $models
     * @param $type
     */
    public function enforceToFollow($entitiesIds, $models, $type)
    {
        $tableName = $type . 'Table';

        foreach ($entitiesIds as $entitiesId) {
            $entity = $this->get($tableName)->getEntity($entitiesId);
            if ($entity->mode == AbstractEntity::MODE_SPECIFIC) { //petite sécurité pour pas construire de la daube
                $entity->set('models', $models);

                $this->get($tableName)->save($entity);
            }
        }
    }

    /**
     * Generate Export Array
     *
     * @param $amv
     * @return array
     */
    public function generateExportArray($amv)
    {
        $amvObj = [
            'id' => 'v',
            'threat' => 'o',
            'vulnerability' => 'o',
            'measure1' => 'o',
            'measure2' => 'o',
            'measure3' => 'o',
            'status' => 'v',
        ];
        $treatsObj = [
            'id' => 'id',
            'theme' => 'theme',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'c' => 'c',
            'i' => 'i',
            'd' => 'd',
            'status' => 'status',
            'trend' => 'trend',
            'comment' => 'comment',
            'qualification' => 'qualification',
        ];
        $vulsObj = [
            'id' => 'id',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
        ];
        $themesObj = [
            'id' => 'id',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
        ];
        $measuresObj = [
            'id' => 'id',
            'code' => 'code',
            'status' => 'status',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
        ];

        $amvs = $threats = $vulns = $themes = $measures = [];

        foreach ($amvObj as $k => $v) {
            switch ($v) {
                case 'v':
                    $amvs[$k] = $amv->get($k);
                    break;
                case 'o':
                    $o = $amv->get($k);
                    if (empty($o)) {
                        $amvs[$k] = null;
                    } else {
                        $o = $amv->get($k)->getJsonArray();
                        $amvs[$k] = $o['id'];
                        switch ($k) {
                            case 'threat':
                                $threats[$o['id']] = $amv->get($k)->getJsonArray($treatsObj);
                                if (!empty($threats[$o['id']]['theme'])) {
                                    $threats[$o['id']]['theme'] = $threats[$o['id']]['theme']->getJsonArray($themesObj);
                                    $themes[$threats[$o['id']]['theme']['id']] = $threats[$o['id']]['theme'];
                                    $threats[$o['id']]['theme'] = $threats[$o['id']]['theme']['id'];
                                }
                                break;
                            case 'vulnerability':
                                $vulns[$o['id']] = $amv->get($k)->getJsonArray($vulsObj);
                                break;
                            case 'measure1':
                            case 'measure2':
                            case 'measure3':
                                $measures[$o['id']] = $amv->get($k)->getJsonArray($measuresObj);
                                break;
                        }
                    }
                    break;
            }
        }

        return [
            $amvs,
            $threats,
            $vulns,
            $themes,
            $measures,
        ];
    }
}