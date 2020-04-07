<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Model\Entity\Amv;
use Monarc\Core\Model\Entity\AmvSuperClass;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\ThemeSuperClass;
use Monarc\Core\Model\Entity\Threat;
use Monarc\Core\Model\Entity\Vulnerability;
use Monarc\Core\Model\Table\AmvTable;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\MeasureTable;
use Monarc\Core\Model\Table\ModelTable;
use Monarc\Core\Model\Table\ThemeTable;

/**
 * Amv Service
 *
 * Class AmvService
 * @package Monarc\Core\Service
 */
class AmvService extends AbstractService
{
    protected $anrTable;
    protected $assetTable;
    protected $assetService;
    protected $instanceTable;
    protected $measureTable;
    protected $referentialTable;
    protected $modelTable;
    protected $threatTable;
    protected $threatService;
    protected $themeTable;
    protected $vulnerabilityTable;
    protected $vulnerabilityService;
    protected $historicalService;
    protected $errorMessage;
    protected $filterColumns = ['status'];
    protected $dependencies = ['anr', 'asset', 'threat', 'vulnerability', 'measures'];
    protected $forbiddenFields = ['anr'];

    /** @var array List of Amv items (asset, threat, vulnerability) grouped by code. */
    private $amvItemsByCode = [];

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null)
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
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        /** @var Amv $amv */
        $amv = $this->get('table')->getEntity($id);

        //manage the measures separatly because it's the slave of the relation amv<-->measures
        foreach ($data['measures'] as $measure) {
            $measureEntity =  $this->get('measureTable')->getEntity($measure);
            $measureEntity->addAmv($amv);
        }

        foreach ($amv->measures as $m) {
            if (!\in_array($m->uuid->toString(), $data['measures'], true)) {
                $m->removeAmv($amv);
            }
        }
        unset($data['measures']);

        if ($this->isThreatChanged($data, $amv) || $this->isVulnerabilityChanged($data, $amv)) {
            $data['uuid'] = Uuid::uuid4()->toString();
        }
        // TODO: check getInputFilter if we are gonna remove exchangeArray.
        $amv->exchangeArray($data, true);

        $this->setDependencies($amv, $this->dependencies);

        parent::patch($id, $data);
    }

    /**
     * @inheritdoc
     */
    public function getFilteredCount($filter = null, $filterAnd = null)
    {
        list($filterJoin,$filterLeft,$filtersCol) = $this->get('entity')->getFiltersForService();

        return $this->get('table')->countFiltered(
            $this->parseFrontendFilter($filter, $filtersCol),
            $filterAnd,
            $filterJoin,
            $filterLeft
        );
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        /** @var AmvTable $amvTable */
        $amvTable = $this->get('table');
        $entityClass = $amvTable->getEntityClass();

        /** @var Amv $amv */
        $amv = new $entityClass();
        $amv->setLanguage($this->getLanguage());
        $amv->setDbAdapter($amvTable->getDb());

        //manage the measures separately because it's the slave of the relation amv<-->measures
        if (!empty($data['measures'])) {
            foreach ($data['measures'] as $measure) {
                $measureEntity = $this->get('measureTable')->getEntity($measure);
                $measureEntity->addAmv($amv);
            }
            unset($data['measures']);
        }

        $amv->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($amv, $dependencies);

        $authorized = $this->compliesRequirement($amv);

        if (!$authorized) {
            throw new Exception($this->errorMessage);
        }

        $amv->setCreator($this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname());

        $id = $this->get('table')->save($amv);

        //historisation
        $newEntity = $this->getEntity($id);

        $this->prepareHistoryRecordLabel($amv);

        //details
        $fields = [
            'anr' => 'code',
            'asset' => 'code',
            'threat' => 'code',
            'vulnerability' => 'code'
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
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $this->filterPatchFields($data);

        if (empty($data)) {
            throw new Exception('Data missing', 412);
        }

        /** @var AmvTable $amvTable */
        /** @var Amv $amv */
        $amvTable = $this->get('table');
        $amvs = $amvTable->findByUuid($id);
        if (!\count($amvs)) {
            throw new Exception(sprintf('Amv(s) with uuid "%s" are not found', $id), 412);
        }

        foreach ($amvs as $amv) {
            $amv->setDbAdapter($this->get('table')->getDb());

            //clone current entity for retrieve difference with new
            $oldEntity = clone $amv;

            $this->prepareHistoryRecordLabel($amv);

            foreach ($amv->getMeasures() as $measure) {
                $linkedMeasuresUuidKey = array_search((string)$measure->getUuid(), $data['measures'], true);
                if ($linkedMeasuresUuidKey === false) {
                    $amv->removeMeasure($measure);
                    continue;
                }
                unset($data['measures'][$linkedMeasuresUuidKey]);
            }
            /** @var MeasureTable $measureTable */
            $measureTable = $this->get('measureTable');
            foreach ($data['measures'] as $measure) {
                $amv->addMeasure($measureTable->getEntity($measure));
            }

            unset($data['measures']);

            $amv->exchangeArray($data);

            $this->setDependencies($amv, $this->dependencies);

            $authorized = $this->compliesRequirement($amv);
            if (!$authorized) {
                throw new Exception($this->errorMessage);
            }

            $this->historizeUpdate('amv', $amv, $oldEntity);

            $amv->setUpdater($this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname());

            $this->get('table')->save($amv);
        }
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function delete($id)
    {
        /** @var  $amv */
        $amv = $this->get('table')->getEntity($id);

        if ($amv) {
            $this->prepareHistoryRecordLabel($amv);

            //details
            $fields = [
                'anr' => 'code',
                'asset' => 'code',
                'threat' => 'code',
                'vulnerability' => 'code'
            ];
            $details = [];
            foreach ($fields as $key => $field) {
                if ($amv->$key) {
                    $details[] = $key . ' => ' . $amv->$key->$field;
                }
            }

            $this->historizeDelete('amv', $amv, $details);

            $this->get('table')->delete($id);
        }
    }

    /*
    * Function to link automatically the amv of the destination from the source depending of the measures_measures
    */
    public function createLinkedAmvs($source_uuid, $destination)
    {
        $measures_dest = $this->get('referentialTable')->getEntity($destination)->getMeasures();
        foreach ($measures_dest as $md) {
            foreach ($md->getMeasuresLinked() as $measureLink) {
                if($measureLink->getReferential()->getUuid()->toString()==$source_uuid ){
                    foreach ($measureLink->amvs as $amv) {
                        $md->addAmv($amv);
                    }
                    $this->get('measureTable')->save($md,false);
                }
            }
        }
        $this->get('measureTable')->getDb()->flush();
    }

    /**
     * Checks whether or not the specified theoretical AMV link complies with the behavioral requirements
     *
     * @param Amv $amv The AMV link to check
     * @param Asset|null $asset The asset
     * @param Model[]|null $assetModels The asset's model
     * @param Threat|null $threat The threat
     * @param Model[]|null $threatModels The threat's model
     * @param Vulnerability|null $vulnerability The vulnerability
     * @param Model[]|null $vulnerabilityModels The vulnerability's models
     *
     * @return bool True if the AMV link is valid, false otherwise
     * @throws Exception If there are behavioral issues
     */
    public function compliesRequirement($amv, $asset = null, $assetModels = null, $threat = null, $threatModels = null, $vulnerability = null, $vulnerabilityModels = null)
    {
        //asset
        $asset = (is_null($asset)) ? $amv->getAsset() : $asset;
        if ($asset->get('type') == 1) {
            throw new Exception('Asset can\'t be primary', 412);
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
            throw new Exception($this->errorMessage, 412);
        }

        return $result;
    }

    /**
     * Checks whether or not the A/M/V combo is compatible to build a link
     *
     * @param $assetMode
     * @param $threatMode
     * @param $vulnerabilityMode
     * @param $assetModelsIds
     * @param $threatModelsIds
     * @param $vulnerabilityModelsIds
     * @param $assetModelsIsRegulator
     *
     * @return bool
     * @throws Exception
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
     * Checks the AMV Integrity Level
     * @param Model[] $models The models in which the AMV link will be applicable
     * @param Asset|null $asset The asset
     * @param Threat|null $threat The threat
     * @param Vulnerability|null $vulnerability The vulnerability
     * @param bool $follow Whether or not the AMV link follows changes
     * @return bool
     */
    public function checkAMVIntegrityLevel($models, $asset = null, $threat = null, $vulnerability = null, $follow = false)
    {
        $amvs = $this->get('table')->findByAMV($asset, $threat, $vulnerability);

        foreach ($amvs as $amv) {

            $amv = $this->get('table')->getEntity($amv['uuid']);
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
     * @param Model[] $models The models in which the AMV link will be applicable
     * @param Asset|null $asset The asset
     * @param Asset|null $threat The threat
     * @param Asset|null $vulnerability The vulnerability
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
     * Check Models Instantiation: Don't remove to an asset of specific model if it is linked to asset by an instance
     * in an anr (by object)
     * @param Asset $asset The asset to check
     * @param array $newModelsIds The IDs of the models
     * @return bool True if valid, false otherwise
     */
    public function checkModelsInstantiation($asset, $newModelsIds)
    {
        $modelsIds = array_combine($newModelsIds, $newModelsIds);//clefs = valeurs

        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        $instances = $instanceTable->getEntityByFields(['asset' => is_string($asset->get('uuid'))?$asset->get('uuid'):$asset->get('uuid')->toString()]);

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
     * Enforces Amv To Follow evolution
     * @param Model[] $models Models
     * @param Asset|null $asset Asset
     * @param Threat|null $threat Threat
     * @param Vulnerability|null $vulnerability Vulnerability
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
                unset($amvAssetsIds[is_string($asset->get('uuid'))?$asset->get('uuid'):$asset->get('uuid')->toString()]);
            }
            if (!is_null($threat)) {
                unset($amvThreatsIds[is_string($threat->get('uuid'))?$threat->get('uuid'):$threat->get('uuid')->toString()]);
            }
            if (!is_null($vulnerability)) {
                unset($amvVulnerabilitiesIds[is_string($vulnerability->get('uuid'))?$vulnerability->get('uuid'):$vulnerability->get('uuid')->toString()]);
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
     * Enforce the entities to follow the model
     * @param array $entitiesIds IDs of entities
     * @param array $models The models the entities should follow
     * @param string $type The type of the entities
     */
    public function enforceToFollow($entitiesIds, $models, $type)
    {
        $tableName = $type . 'Table';

        foreach ($entitiesIds as $entitiesId) {
            $entity = $this->get($tableName)->getEntity($entitiesId);
            if ($entity->mode == AbstractEntity::MODE_SPECIFIC) { // ensures to construct a correct object
                $entity->set('models', $models);

                $this->get($tableName)->save($entity);
            }
        }
    }

    /**
     * Generate an array ready for export
     * @param Amv $amv The AMV entity to export
     * @param bool $withEval
     * @return array The exported array
     */
    public function generateExportArray($amv, $anrId, $withEval = false)
    {
        $amvObj = [
            'uuid' => 'v',
            'threat' => 'o',
            'asset' => 'o',
            'vulnerability' => 'o',
            'measures' => 'o',
            'status' => 'v',
        ];
        $treatsObj = [
            'uuid' => 'uuid',
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
            'a' => 'a',
            'status' => 'status',
        ];
        if ($withEval) {
          $treatsObj = array_merge(
            $treatsObj,
            [
            'trend' => 'trend',
            'comment' => 'comment',
            'qualification' => 'qualification'
            ]
          );
        };
        $vulsObj = [
            'uuid' => 'uuid',
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
            'uuid' => 'uuid',
            'category' => 'category',
            'referential' => 'referential',
            'code' => 'code',
            'status' => 'status',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',

        ];
        $soacategoriesObj = [
            'id' => 'id',
            'code' => 'code',
            'status' => 'status',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
        ];

        $amvs = $threats = $vulns = $themes = $measures = $soacategories = [];

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
                        switch ($k) {
                            case 'threat':
                                $o = $amv->get($k);
                                $amvs[$k] = $o->uuid->toString();
                                $threats[$o->uuid->toString()] = $amv->get($k)->getJsonArray($treatsObj);
                                if (!empty($threats[$o->uuid->toString()]['theme'])) {
                                    $threats[$o->uuid->toString()]['theme'] = $threats[$o->uuid->toString()]['theme']->getJsonArray($themesObj);
                                    $themes[$threats[$o->uuid->toString()]['theme']['id']] = $threats[$o->uuid->toString()]['theme'];
                                    $threats[$o->uuid->toString()]['theme'] = $threats[$o->uuid->toString()]['theme']['id'];
                                }
                                break;
                            case 'vulnerability':
                                $o = $amv->get($k);
                                $amvs[$k] = $o->uuid->toString();
                                $vulns[$o->uuid->toString()] = $amv->get($k)->getJsonArray($vulsObj);
                                break;
                            case 'asset':
                                $o = $amv->get($k);
                                $amvs[$k] = $o->uuid->toString();
                                break;
                            case 'measures':
                                $measuresList = $amv->get($k);
                                if(count($measuresList)>0){
                                    foreach ($measuresList  as $m) {
                                        $measures[$m->uuid->toString()] = $m->getJsonArray($measuresObj);
                                        $measures[$m->uuid->toString()]['category'] = $m->category->getJsonArray($soacategoriesObj);
                                        $measures[$m->uuid->toString()]['referential'] = $m->referential->uuid->toString();
                                        $amvs[$k][] = $m->uuid->toString();
                                    }
                                }
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


    /**
     * Compares and stores differences between two entities in the history (if there are any) as an update event.
     * @param string $type The entity type
     * @param AbstractEntity $entity The new entity (post-changes)
     * @param AbstractEntity $oldEntity The old entity (pre-changes)
     */
    public function historizeUpdate($type, $entity, $oldEntity)
    {
        $diff = $this->compareEntities($entity, $oldEntity);

        if (count($diff)) {
            $this->historize($entity, $type, 'update', implode(' / ', $diff));
        }
    }

    /**
     * Stores an object creation event in the history
     * @param string $type The entity type
     * @param AbstractEntity $entity The entity that has been created
     * @param array $details An array of changes details
     */
    public function historizeCreate($type, $entity, $details)
    {
        $this->historize($entity, $type, 'create', implode(' / ', $details));
    }

    /**
     * Stores an object deletion event in the history
     * @param string $type The entity type
     * @param AbstractEntity $entity The entity that has been deleted
     * @param array $details An array of changes details
     */
    public function historizeDelete($type, $entity, $details)
    {
        $this->historize($entity, $type, 'delete', implode(' / ', $details));
    }

    /**
     * Creates the amv items (assets, threats, vulnerabilities) to use them for amvs creation later.
     * On BackOffice side the $anrId param is null.
     *
     * @throws Exception
     * @throws EntityNotFoundException
     */
    public function createAmvsItems(?int $anrId, array $data): array
    {
        $createdItems = [];
        /** @var AssetService $assetService */
        $assetService = $this->get('assetService');
        /** @var ThreatService $threatService */
        $threatService = $this->get('threatService');
        /** @var VulnerabilityService $vulnerabilityService */
        $vulnerabilityService = $this->get('vulnerabilityService');
        /** @var ThemeTable $themeTable */
        $themeTable = $this->get('themeTable');
        /** @var AmvTable $amvTable */
        $amvTable = $this->get('table');

        $extraCreationParams = [];
        if ($anrId !== null) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->findById($anrId);
            $extraCreationParams = ['anr' => $anrId];
        }

        foreach ($data as $amvItem) {
            if (!empty($amvItem['asset']['uuid'])
                && !empty($amvItem['threat']['uuid'])
                && !empty($amvItem['vulnerability']['uuid'])
                && $amvTable->findByAmvItemsUuidAndAnrId(
                    $amvItem['asset']['uuid'],
                    $amvItem['threat']['uuid'],
                    $amvItem['vulnerability']['uuid'],
                    $anrId
                )
            ) {
                continue;
            }

            if (isset($amvItem['threat']['theme']) && \is_array($amvItem['threat']['theme'])) {
                $labelKey = array_key_first($amvItem['threat']['theme']);
                $labelValue = array_shift($amvItem['threat']['theme']);
                $theme = $themeTable->findByAnrIdAndLabel($anrId, $labelKey, $labelValue);
                if ($theme === null) {
                    $themeClass = $themeTable->getEntityClass();
                    /** @var ThemeSuperClass $theme */
                    $theme = new $themeClass;
                    if (isset($anr)) {
                        $theme->setAnr($anr);
                    }
                    $labelSetterName = 'set' . ucfirst($labelKey);
                    $theme->{$labelSetterName}($labelValue);
                    $themeTable->saveEntity($theme);
                }

                $amvItem['threat']['theme'] = $theme->getId();
            }

            $createdItems[] = [
                'asset' => $this->createAmvItemOrGetUuid(
                    $assetService,
                    array_merge($amvItem['asset'], $extraCreationParams),
                    'asset'
                ),
                'threat' => $this->createAmvItemOrGetUuid(
                    $threatService,
                    array_merge($amvItem['threat'], $extraCreationParams),
                    'threat'
                ),
                'vulnerability' => $this->createAmvItemOrGetUuid(
                    $vulnerabilityService,
                    array_merge($amvItem['vulnerability'], $extraCreationParams),
                    'vulnerability'
                ),
            ];
        }

        return $createdItems;
    }

    private function createAmvItemOrGetUuid(AbstractService $service, array $data, string $itemType): string
    {
        if (!empty($data['uuid'])) {
            return $data['uuid'];
        }

        if (!isset($this->amvItemsByCode[$itemType][$data['code']])) {
            $this->amvItemsByCode[$itemType][$data['code']] = $service->create($data);
        }

        return $this->amvItemsByCode[$itemType][$data['code']];
    }

    /**
     * Stores an event into the history
     * @param AbstractEntity|array $entity The affected entity
     * @param string $type The event type
     * @param string $verb The event kind (create, delete, update)
     * @param string $details The event description / details
     */
    protected function historize($entity, $type, $verb, $details)
    {
        /** @var HistoricalService $historicalService */
        $historicalService = $this->get('historicalService');
        // There is no history table on FO side.
        if ($historicalService === null) {
            return;
        }

        $entityId = null;

        if (is_object($entity) && (property_exists($entity, 'id'))) {
            $entityId = $entity->id;
        } else if (is_array($entity) && (isset($entity['id']))) {
            $entityId = $entity['id'];
        }

        $data = [
            'type' => $type,
            'sourceId' => $entityId,
            'action' => $verb,
            'label1' => (is_object($entity) && property_exists($entity, 'label1')) ? $entity->label1 : $this->label[0],
            'label2' => (is_object($entity) && property_exists($entity, 'label2')) ? $entity->label2 : $this->label[1],
            'label3' => (is_object($entity) && property_exists($entity, 'label3')) ? $entity->label3 : $this->label[2],
            'label4' => (is_object($entity) && property_exists($entity, 'label4')) ? $entity->label4 : $this->label[3],
            'details' => $details,
        ];

        $historicalService->create($data);
    }

    protected function isThreatChanged(array $data, AmvSuperClass $amv): bool
    {
        return (string)$amv->getThreat()->getUuid() !== $data['threat'];
    }

    protected function isVulnerabilityChanged(array $data, AmvSuperClass $amv): bool
    {
        return (string)$amv->getVulnerability()->getUuid() !== $data['vulnerability'];
    }

    private function prepareHistoryRecordLabel(AmvSuperClass $amv): void
    {
        $labelParts = [];
        if ($amv->getAsset()->getCode()) {
            $labelParts[] = $amv->getAsset()->getCode();
        }
        if ($amv->getThreat()->getCode()) {
            $labelParts[] = $amv->getThreat()->getCode();
        }
        if ($amv->getVulnerability()->getCode()) {
            $labelParts[] = $amv->getVulnerability()->getCode();
        }
        $labelParts = implode(' - ', $labelParts);
        $this->label = [$labelParts, $labelParts, $labelParts, $labelParts];
    }
}
