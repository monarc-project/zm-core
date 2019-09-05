<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Table\AmvTable;
use Monarc\Core\Model\Table\AnrObjectCategoryTable;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\AssetTable;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\ModelTable;
use Monarc\Core\Model\Table\ObjectCategoryTable;
use Monarc\Core\Model\Table\ObjectObjectTable;
use Monarc\Core\Model\Table\MonarcObjectTable;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\ORMException;

/**
 * Object Service
 *
 * Class ObjectService
 * @package Monarc\Core\Service
 */
class ObjectService extends AbstractService
{
    protected $objectObjectService;
    protected $modelService;
    protected $instanceRiskOpService;
    protected $anrObjectCategoryEntity;
    protected $anrTable;
    protected $userAnrTable;
    protected $anrObjectCategoryTable;
    protected $assetTable;
    protected $assetService;
    protected $categoryTable;
    protected $instanceTable;
    protected $instanceRiskOpTable;
    protected $modelTable;
    protected $objectObjectTable;
    protected $rolfTagTable;
    protected $amvTable;
    protected $objectExportService;
    protected $filterColumns = ['name1', 'name2', 'name3', 'name4', 'label1', 'label2', 'label3', 'label4'];
    protected $dependencies = ['anr', 'asset', 'category', 'rolfTag'];

    /**
     * Get List Specific
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param null $asset
     * @param null $category
     * @param null $model
     * @param null $anr
     * @param null $lock
     * @return array
     * @throws \Monarc\Core\Exception\Exception
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null, $model = null, $anr = null, $lock = null)
    {
        /** @var AssetTable $assetTable */
        $assetTable = $this->get('assetTable');
        /** @var ObjectCategoryTable $categoryTable */
        $categoryTable = $this->get('categoryTable');

        $filterAnd = [];
        $assetsTab = [];
        if ((!is_null($asset)) && ($asset != null)) {
          $assetsTab[] = $asset;
          $filterAnd['asset'] = ['op' => 'IN', 'value' => $assetsTab];
        }
        if ((!is_null($category)) && ($category != 0)) {
            if ($category > 0) {
                $child = ($lock == 'true') ? [] : $categoryTable->getDescendants($category);
                $child[] = $category;
            } else if ($category == -1) {
                $child = null;
            }

            $filterAnd['category'] = $child;
        }

        $objects = $this->getAnrObjects($page, $limit, $order, $filter, $filterAnd, $model, $anr);

        $rootArray = [];

        foreach ($objects as $object) {
            if (!empty($object['asset'])) {
                if ($anr != null) {
                    try {
                        $object['asset'] = $assetTable->get(['uuid' => $object['asset']->getUuid()->toString(), 'anr'=>$anr]);
                    } catch (ORMException $e) {
                        // section used when the request comes from a back office
                        $object['asset'] = $assetTable->get(['uuid' => $object['asset']->getUuid()->toString()]);
                    }
                }
                else {
                    $object['asset'] = $assetTable->get($object['asset']->getUuid());
                }
            }
            if (!empty($object['category'])) {
                $object['category'] = $categoryTable->get($object['category']->getId());
            }
            $rootArray[$object['uuid']->toString()] = $object;
        }
        return array_values($rootArray);
    }

    /**
     * Get Anr Objects
     *
     * @param $page
     * @param $limit
     * @param $order
     * @param $filter
     * @param $filterAnd
     * @param $model
     * @param $anr
     * @return array|bool
     */
    public function getAnrObjects($page, $limit, $order, $filter, $filterAnd, $model, $anr, $context = \Monarc\Core\Model\Entity\AbstractEntity::BACK_OFFICE)
    {
        if ($model) {
            /** @var ModelTable $modelTable */
            $modelTable = $this->get('modelTable');
            $model = $modelTable->getEntity($model);
            if ($model->get('isGeneric')) { // le modèle est générique, on récupère les modèles génériques
                $filterAnd['mode'] = MonarcObject::MODE_GENERIC;
            } else {
                $filterAnd['asset'] = [];

                $assets = $model->get('assets');
                foreach ($assets as $a) { // on récupère tous les assets associés au modèle et on ne prend que les spécifiques
                    if ($a->get('mode') == MonarcObject::MODE_SPECIFIC) {
                        $filterAnd['asset'][$a->get('uuid')] = $a->get('uuid');
                    }
                }
                if (!$model->get('isRegulator')) { // si le modèle n'est pas régulateur
                    $assets = $this->get('assetTable')->getEntityByFields(['mode' => MonarcObject::MODE_GENERIC]); // on récupère tous les assets génériques
                    foreach ($assets as $a) {
                        $filterAnd['asset'][$a->get('uuid')] = $a->get('uuid');
                    }
                }
            }
            if ($context != \Monarc\Core\Model\Entity\AbstractEntity::FRONT_OFFICE) {
                $objects = $model->get('anr')->get('objects');
                if (!empty($objects)) { // on enlève tout les objets déjà liés
                    $filterAnd['uuid'] = ['op' => 'NOT IN', 'value' => []];
                    foreach ($objects as $o) {
                        $filterAnd['uuid']['value'][$o->get('uuid')] = $o->get('uuid');
                    }
                    if (empty($filterAnd['uuid']['value'])) {
                        unset($filterAnd['uuid']);
                    }
                }
            }
        } else if ($anr) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');

            $anrObj = $anrTable->getEntity($anr);
            $filterAnd['uuid'] = [];
            $objects = $anrObj->get('objects');
            $value = [];
            foreach ($objects as $o) { // on en prend que les objets déjà liés (composants)
              $value[] = $o->get('uuid')->toString();
                //$filterAnd['uuid'][$o->get('uuid')->toString()] = $o->get('uuid')->toString();
            }
            $filterAnd['uuid'] = ['op' => 'IN', 'value' => $value];

        }
        /** @var MonarcObjectTable $MonarcObjectTable */
        $MonarcObjectTable = $this->get('table');
        return $MonarcObjectTable->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );
    }

    /**
     * Get Complete Entity
     *
     * @param $id
     * @param string $anrContext
     * @param null $anr
     * @return mixed
     * @throws \Monarc\Core\Exception\Exception
     */
    public function getCompleteEntity($id, $anrContext = MonarcObject::CONTEXT_BDC, $anr = null)
    {
      $Monarc\FrontOffice = false;
        $table = $this->get('table');
        /** @var Object $object */
        try{
          $object = $table->getEntity($id);
        }catch(MappingException $e)
        {
          $object = $table->getEntity(['uuid'=>$id,'anr'=>$anr]);
          $Monarc\FrontOffice = true;
        }
        $object_arr = $object->getJsonArray();

        // Retrieve children recursively
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        if ($object->anr->id == null) {
            $object_arr['children'] = $objectObjectService->getRecursiveChildren($object_arr['uuid']->toString(), null);
        } else {
            $object_arr['children'] = $objectObjectService->getRecursiveChildren($object_arr['uuid']->toString(), $anr);
        }

        // Calculate the risks table
        $object_arr['risks'] = $this->getRisks($object);
        $object_arr['oprisks'] = $this->getRisksOp($object);
        $object_arr['parents'] = $this->getDirectParents($object_arr['uuid'], $anr);

        // Retrieve parent recursively
        if ($anrContext == MonarcObject::CONTEXT_ANR) {
            //Check if the object is linked to the $anr
            $found = false;
            $anrObject = null;
            foreach ($object->anrs as $a) {
                if ($a->id == $anr) {
                    $found = true;
                    $anrObject = $a;
                    break;
                }
            }

            if (!$found) {
                throw new \Monarc\Core\Exception\Exception('This object is not bound to the ANR', 412);
            }

            if (!$anr) {
                throw new \Monarc\Core\Exception\Exception('Anr missing', 412);
            }

            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('instanceTable');
            if($Monarc\FrontOffice)
              $instances = $instanceTable->getEntityByFields(['anr' => $anr, 'object' => ['uuid' => $id, 'anr'=>$anr]]);
            else
              $instances = $instanceTable->getEntityByFields(['anr' => $anr, 'object' => $id]);


            $instances_arr = [];
            foreach ($instances as $instance) {
                $asc = $instanceTable->getAscendance($instance);

                $names = [
                    'name1' => $anrObject->label1,
                    'name2' => $anrObject->label2,
                    'name3' => $anrObject->label3,
                    'name4' => $anrObject->label4,
                ];
                foreach ($asc as $a) {
                    $names['name1'] .= ' > ' . $a['name1'];
                    $names['name2'] .= ' > ' . $a['name2'];
                    $names['name3'] .= ' > ' . $a['name3'];
                    $names['name4'] .= ' > ' . $a['name4'];
                }
                $names['id'] = $instance->get('id');
                $instances_arr[] = $names;
            }

            $object_arr['replicas'] = $instances_arr;
        } else {

            $anrsIds = [];
            foreach ($object->anrs as $anr) {
                $anrsIds[] = $anr->id;
            }

            /** @var ModelTable $modelTable */
            $modelTable = $this->get('modelTable');
            $models = $modelTable->getByAnrs($anrsIds);

            $models_arr = [];
            foreach ($models as $model) {
                $models_arr[] = [
                    'id' => $model->id,
                    'label1' => $model->label1,
                    'label2' => $model->label2,
                    'label3' => $model->label3,
                    'label4' => $model->label4,
                ];
            }

            $object_arr['replicas'] = $models_arr;
        }

        return $object_arr;
    }

    /**
     * Get Risks
     *
     * @param $object
     * @return array
     */
    protected function getRisks($object)
    {
        /** @var AmvTable $amvTable */
        $amvTable = $this->get('amvTable');
        try{
          $amvs = $amvTable->getEntityByFields(['asset' => $object->asset->uuid], ['position' => 'asc']);
        }catch(\Doctrine\ORM\Query\QueryException $e) // we check if the uuid id already existing
        {
          $amvs = $amvTable->getEntityByFields(['asset' => ['uuid' =>$object->asset->uuid->toString(), 'anr'=>$object->asset->anr->id]], ['position' => 'asc']);
        }


        $risks = [];
        foreach ($amvs as $amv) {

            $risks[] = [
                'id' => $amv->uuid->toString(),
                'threatLabel1' => $amv->threat->label1,
                'threatLabel2' => $amv->threat->label2,
                'threatLabel3' => $amv->threat->label3,
                'threatLabel4' => $amv->threat->label4,
                'threatDescription1' => $amv->threat->description1,
                'threatDescription2' => $amv->threat->description2,
                'threatDescription3' => $amv->threat->description3,
                'threatDescription4' => $amv->threat->description4,
                'threatRate' => '-',
                'vulnLabel1' => $amv->vulnerability->label1,
                'vulnLabel2' => $amv->vulnerability->label2,
                'vulnLabel3' => $amv->vulnerability->label3,
                'vulnLabel4' => $amv->vulnerability->label4,
                'vulnDescription1' => $amv->vulnerability->description1,
                'vulnDescription2' => $amv->vulnerability->description2,
                'vulnDescription3' => $amv->vulnerability->description3,
                'vulnDescription4' => $amv->vulnerability->description4,
                'vulnerabilityRate' => '-',
                'c_risk' => '-',
                'c_risk_enabled' => $amv->threat->c,
                'i_risk' => '-',
                'i_risk_enabled' => $amv->threat->i,
                'd_risk' => '-',
                'd_risk_enabled' => $amv->threat->d,
                'comment' => ''
            ];
        }

        return $risks;
    }

    /**
     * Get Risks Op
     *
     * @param $object
     * @return array
     */
    protected function getRisksOp($object)
    {
        $riskOps = [];

        if (isset($object->asset) && $object->asset->type == Asset::TYPE_PRIMARY && !is_null($object->rolfTag)) {
            //retrieve rolf risks
            /** @var RolfTagTable $rolfTagTable */
            $rolfTagTable = $this->get('rolfTagTable');
            $rolfTag = $rolfTagTable->getEntity($object->rolfTag->id);
            $rolfRisks = $rolfTag->risks;

            if (!empty($rolfRisks)) {
                foreach ($rolfRisks as $rolfRisk) {
                    $riskOps[] = [
                        'label1' => $rolfRisk->label1,
                        'label2' => $rolfRisk->label2,
                        'label3' => $rolfRisk->label3,
                        'label4' => $rolfRisk->label4,
                        'description1' => $rolfRisk->description1,
                        'description2' => $rolfRisk->description2,
                        'description3' => $rolfRisk->description3,
                        'description4' => $rolfRisk->description4,
                        'prob' => '-',
                        'r' => '-',
                        'o' => '-',
                        'l' => '-',
                        'p' => '-',
                        'risk' => '-',
                        'comment' => '',
                        't' => '',
                        'target' => '-',
                    ];
                }
            }
        }

        return $riskOps;
    }

    /**
     * Get Filtered Count
     *
     * @param null $filter
     * @param null $asset
     * @param null $category
     * @param null $model
     * @return int
     */
    public function getFilteredCount($filter = null, $asset = null, $category = null, $model = null, $anr = null, $context = MonarcObject::BACK_OFFICE)
    {
        $filterAnd = [];
        if ((!is_null($asset)) && ($asset != 0)) {
            $filterAnd['asset'] = $asset;
        }
        if ((!is_null($category)) && ($category != 0)) {
            $filterAnd['category'] = $category;
        }

        $result = $this->getAnrObjects(1, 0, null, $filter, $filterAnd, $model, $anr, $context);

        return count($result);
    }

    /**
     * Get generic by asset
     *
     * @param $asset
     * @return mixed
     */
    public function getGenericByAsset($asset)
    {
        return $this->get('table')->getGenericByAssetId($asset->getId());
    }

    /**
     * Recursive child
     *
     * @param $hierarchy
     * @param $parent
     * @param $childHierarchy
     * @return mixed
     */
    public function recursiveChild($hierarchy, $parent, &$childHierarchy, $objectsArray)
    {
        $children = [];
        foreach ($childHierarchy as $key => $link) {
            if ((int)$link['father'] == $parent) {
                $recursiveChild = $this->recursiveChild($hierarchy, $link['child'], $childHierarchy, $objectsArray);
                $recursiveChild['objectObjectId'] = $link['id'];
                $children[] = $recursiveChild;
                unset($childHierarchy[$key]);
            }
        }

        $hierarchy = $objectsArray[$parent];
        $this->formatDependencies($hierarchy, $this->dependencies);
        if ($children) {
            $hierarchy['childs'] = $children;
        }

        return $hierarchy;
    }

    /**
     * @param $data
     * @param bool $last
     * @param string $context
     * @return mixed
     * @throws \Monarc\Core\Exception\Exception
     */
    public function create($data, $last = true, $context = AbstractEntity::BACK_OFFICE)
    {
        //create object
        $class = $this->get('entity');
        $object = new $class;
        $object->setLanguage($this->getLanguage());
        $object->setDbAdapter($this->get('table')->getDb());

        //in FO, all objects are generics
        if ($context == AbstractEntity::FRONT_OFFICE) {
            $data['mode'] = MonarcObject::MODE_GENERIC;
        }

        $setRolfTagNull = false;
        if (empty($data['rolfTag'])) {
            unset($data['rolfTag']);
            $setRolfTagNull = true;
        }


        $anr = false;
        if (isset($data['anr']) && strlen($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->getEntity($data['anr']);

            if (!$anr) {
                throw new \Monarc\Core\Exception\Exception('This risk analysis does not exist', 412);
            }
            $object->setAnr($anr);
        }

        // Si asset secondaire, pas de rolfTag
        if (!empty($data['asset']) && !empty($data['rolfTag'])) {
            $assetTable = $this->get('assetTable');
            $asset = $assetTable->get($data['asset']);
            if (!empty($asset['type']) && $asset['type'] != \Monarc\Core\Model\Entity\Asset::TYPE_PRIMARY) {
                unset($data['rolfTag']);
                $setRolfTagNull = true;
            }
        }

        $object->setDbAdapter($this->get('table')->getDb());
        $object->exchangeArray($data);

        //object dependencies
        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($object, $dependencies);

        if ($setRolfTagNull) {
            $object->set('rolfTag', null);
        }

        if (empty($data['category'])) {
            $data['category'] = null;
        }

        if (isset($data['source'])) {
            $object->source = $this->get('table')->getEntity($data['source']);
        }

        //security
        if ($context == AbstractEntity::BACK_OFFICE &&
            $object->mode == MonarcObject::MODE_GENERIC && $object->asset->mode == MonarcObject::MODE_SPECIFIC) {
            throw new \Monarc\Core\Exception\Exception("You can't have a generic object based on a specific asset", 412);
        }
        if (isset($data['modelId'])) {
            $this->get('modelTable')->canAcceptObject($data['modelId'], $object, $context);
        }

        if (($object->asset->type == Asset::TYPE_PRIMARY) && ($object->scope == MonarcObject::SCOPE_GLOBAL)) {
            throw new \Monarc\Core\Exception\Exception('You cannot create an object that is both global and primary', 412);
        }


        if ($context == MonarcObject::BACK_OFFICE) {
            //create object type bdc
            $id = $this->get('table')->save($object);

            //attach object to anr
            if (isset($data['modelId'])) {

                $model = $this->get('modelService')->getEntity($data['modelId']);

                if (!$model['anr']) {
                    throw new \Monarc\Core\Exception\Exception('No anr associated to this model', 412);
                }

                $id = $this->attachObjectToAnr($object, $model['anr']->id);
            }
        } else if ($anr) {
            $id = $this->attachObjectToAnr($object, $anr, null, null, $context);
        } else {
            //create object type anr
            $id = $this->get('table')->save($object);
        }

        return $id;
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return bool
     * @throws \Monarc\Core\Exception\Exception
     */
    public function update($id, $data, $context = AbstractEntity::BACK_OFFICE)
    {
        unset($data['anrs']);
        if (empty($data)) {
            throw new \Monarc\Core\Exception\Exception('Data missing', 412);
        }

        //in FO, all objects are generics
        if ($context == AbstractEntity::FRONT_OFFICE) {
            $data['mode'] = MonarcObject::MODE_GENERIC;
        }

        try{
          $object = $this->get('table')->getEntity($id);
        }catch(QueryException $e){
          $object = $this->get('table')->getEntity(['uuid' => $id, 'anr' => $data['anr']]);
        } catch (MappingException $e) {
          $object = $this->get('table')->getEntity(['uuid' => $id, 'anr' => $data['anr']]);
        }
        if (!$object) {
            throw new \Monarc\Core\Exception\Exception('Entity `id` not found.');
        }
        $object->setDbAdapter($this->get('table')->getDb());
        $object->setLanguage($this->getLanguage());

        $setRolfTagNull = false;
        if (empty($data['rolfTag'])) {
            unset($data['rolfTag']);
            $setRolfTagNull = true;
        }

        if (isset($data['scope']) && $data['scope'] != $object->scope) {
            throw new \Monarc\Core\Exception\Exception('You cannot change the scope of an existing object.', 412);
        }

        if (isset($data['asset']) && $data['asset'] != $object->asset->uuid) {
            throw new \Monarc\Core\Exception\Exception('You cannot change the asset type of an existing object.', 412);
        }

        if (isset($data['mode']) && $data['mode'] != $object->get('mode') &&
            !$this->checkModeIntegrity($object->get('id'), $object->get('mode'))) {
            /* on test:
            - que l'on a pas de parents GENERIC quand on passe de GENERIC à SPECIFIC
            - que l'on a pas de fils SPECIFIC quand on passe de SPECIFIC à GENERIC
            */
            if ($object->get('mode') == MonarcObject::MODE_GENERIC) {
                throw new \Monarc\Core\Exception\Exception('You cannot set this object to specific mode because one of its parents is in generic mode.', 412);
            } else {
                throw new \Monarc\Core\Exception\Exception('You cannot set this object to generic mode because one of its children is in specific mode.', 412);
            }
        }

        $currentRootCategory = ($object->category && $object->category->root) ? $object->category->root : $object->category;

        // Si asset secondaire, pas de rolfTag
        if (!empty($data['asset']) && !empty($data['rolfTag'])) {
            $assetTable = $this->get('assetTable');
            try{
              $asset = $assetTable->get($data['asset']);
            }catch(MappingException $e){
              $asset = $assetTable->get(['uuid'=>$data['asset'], 'anr' => $data['anr']]);
            }
            if (!empty($asset['type']) && $asset['type'] != \Monarc\Core\Model\Entity\Asset::TYPE_PRIMARY) {
                unset($data['rolfTag']);
                $setRolfTagNull = true;
            }
        }

        $rolfTagId = ($object->rolfTag) ? $object->rolfTag->id : null;

        $object->exchangeArray($data, true);

        if ($object->rolfTag) {
            $newRolfTagId = (is_int($object->rolfTag)) ? $object->rolfTag : $object->rolfTag->id;
            $newRolfTag = ($rolfTagId == $newRolfTagId) ? false : $object->rolfTag;
        } else {
            $newRolfTag = false;
        }


        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($object, $dependencies);

        if ($setRolfTagNull) {
            $object->set('rolfTag', null);
        }

        $objectRootCategory = ($object->category->root) ? $object->category->root : $object->category;

        if ($currentRootCategory && $objectRootCategory && $currentRootCategory->id != $objectRootCategory->id) {

            //retrieve anrs for object
            foreach ($object->anrs as $anr) {

                //retrieve number anr objects with the same root category than current object
                $nbObjectsSameOldRootCategory = 0;
                foreach ($anr->objects as $anrObject) {
                    $anrObjectCategory = ($anrObject->category->root) ? $anrObject->category->root : $anrObject->category;
                    if (($anrObjectCategory->id == $currentRootCategory->id) && ($anrObject->uuid->toString() != $object->uuid->toString())) {
                        $nbObjectsSameOldRootCategory++;
                        break; // no need to go further
                    }
                }
                if (!$nbObjectsSameOldRootCategory) {
                    /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
                    $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
                    $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields(['anr' => $anr->id, 'category' => $currentRootCategory->id]);
                    $i = 1;
                    $nbAnrObjectCategories = count($anrObjectCategories);
                    foreach ($anrObjectCategories as $anrObjectCategory) {
                        $anrObjectCategoryTable->delete($anrObjectCategory->id, ($i == $nbAnrObjectCategories));
                        $i++;
                    }
                }

                //retrieve number anr objects with the same category than current object
                $nbObjectsSameNewRootCategory = 0;
                foreach ($anr->objects as $anrObject) {
                    $anrObjectCategory = ($anrObject->category->root) ? $anrObject->category->root : $anrObject->category;
                    if (($anrObjectCategory->id == $objectRootCategory->id) && ($anrObject->uuid->toString() != $object->uuid->toString())) {
                        $nbObjectsSameNewRootCategory++;
                        break; // no need to go further
                    }
                }
                if (!$nbObjectsSameNewRootCategory) {
                    /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
                    $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');

                    $class = $this->get('anrObjectCategoryEntity');
                    $anrObjectCategory = new $class();
                    $anrObjectCategory->setDbAdapter($anrObjectCategoryTable->getDb());

                    $anrObjectCategory->exchangeArray([
                        'anr' => $anr,
                        'category' => $objectRootCategory,
                        'implicitPosition' => 2,
                    ]);

                    $anrObjectCategoryTable->save($anrObjectCategory);
                }
            }
        }

        $this->get('table')->save($object);

        $this->instancesImpacts($object, $newRolfTag, $setRolfTagNull);

        return $id;
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function patch($id, $data, $context = AbstractEntity::FRONT_OFFICE)
    {
        // in FO, all objects are generics
        if ($context == AbstractEntity::FRONT_OFFICE) {
            $data['mode'] = MonarcObject::MODE_GENERIC;
        }

        $setRolfTagNull = false;
        // To improve.
        // There is a bug on operational risks when position of primary asset changing. Risks are changed to specific.
        // if (empty($data['rolfTag'])) {
        //     unset($data['rolfTag']);
        //     $setRolfTagNull = true;
        // }

        try{
          $object = $this->get('table')->getEntity($id);
        }catch(MappingException $e){
          $object = $this->get('table')->getEntity(['uuid'=>$id, 'anr' => $data['anr']]);
        }
        $object->setLanguage($this->getLanguage());
        unset($data['anr']);

        $rolfTagId = ($object->rolfTag) ? $object->rolfTag->id : null;

        $object->exchangeArray($data, true);

        if ($object->rolfTag) {
            $newRolfTagId = (is_int($object->rolfTag)) ? $object->rolfTag : $object->rolfTag->id;
            $newRolfTag = ($rolfTagId == $newRolfTagId) ? false : $object->rolfTag;
        } else {
            $newRolfTag = false;
        }

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($object, $dependencies);

        $this->get('table')->save($object);

        $this->instancesImpacts($object, $newRolfTag, $setRolfTagNull);

        return $id;
    }

    /**
     * Instances Impacts
     *
     * @param $object
     * @param bool $newRolfTag
     * @param bool $forcecSpecific
     */
    protected function instancesImpacts($object, $newRolfTag = false, $forceSpecific = false)
    {
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        try{
          $instances = $instanceTable->getEntityByFields(['object' => $object]);
        }catch(QueryException $e){
          $uuid = is_string($object->uuid)?$object->uuid:$object->uuid->toString();
          $instances = $instanceTable->getEntityByFields(['object' => ['uuid' =>$uuid, 'anr'=>$object->anr->id]]);
        }
        foreach ($instances as $instance) {
            $modifyInstance = false;
            for ($i = 1; $i <= 4; $i++) {
                $name = 'name' . $i;
                if ($instance->$name != $object->$name) {
                    $modifyInstance = true;
                    $instance->$name = $object->$name;
                }
                $label = 'label' . $i;
                if ($instance->$label != $object->$label) {
                    $modifyInstance = true;
                    $instance->$label = $object->$label;
                }
            }
            if ($modifyInstance) {
                $instanceTable->save($instance);
            }
            if (($newRolfTag) || (is_null($newRolfTag)) || ($forceSpecific)) {

                //change instance risk op to specific
                /** @var InstanceRiskOpTable $instanceRiskOpTable */
                $instanceRiskOpTable = $this->get('instanceRiskOpTable');
                $instancesRisksOp = $instanceRiskOpTable->getEntityByFields(['instance' => $instance->id]);
                $i = 1;
                $nbInstancesRiskOp = count($instancesRisksOp);
                foreach ($instancesRisksOp as $instanceRiskOp) {
                    $instanceRiskOp->specific = 1;
                    $instanceRiskOpTable->save($instanceRiskOp, ($i == $nbInstancesRiskOp));
                    $i++;
                }

                if (!is_null($newRolfTag) && (!$forceSpecific)) {
                    //add new risk op to instance
                    /** @var RolfTagTable $rolfTagTable */
                    $rolfTagTable = $this->get('rolfTagTable');
                    $rolfTag = $rolfTagTable->getEntity($newRolfTag);
                    $rolfRisks = $rolfTag->risks;
                    $nbRolfRisks = count($rolfRisks);
                    $i = 1;
                    foreach ($rolfRisks as $rolfRisk) {
                        $data = [
                            'anr' => $object->anr->id,
                            'instance' => $instance->id,
                            'object' => is_string($object->uuid)?$object->uuid:$object->uuid->toString(),
                            'rolfRisk' => $rolfRisk->id,
                            'riskCacheCode' => $rolfRisk->code,
                            'riskCacheLabel1' => $rolfRisk->label1,
                            'riskCacheLabel2' => $rolfRisk->label2,
                            'riskCacheLabel3' => $rolfRisk->label3,
                            'riskCacheLabel4' => $rolfRisk->label4,
                            'riskCacheDescription1' => $rolfRisk->description1,
                            'riskCacheDescription2' => $rolfRisk->description2,
                            'riskCacheDescription3' => $rolfRisk->description3,
                            'riskCacheDescription4' => $rolfRisk->description4,
                        ];
                        /** @var InstanceRiskOpService $instanceRiskOpService */
                        $instanceRiskOpService = $this->get('instanceRiskOpService');
                        $instanceRiskOpService->create($data, ($nbRolfRisks == $i));
                        $i++;
                    }
                }
            }
        }
    }

    /**
     * Check Mode Integrity
     *
     * @param $id
     * @param $mode
     * @return bool
     */
    protected function checkModeIntegrity($id, $mode)
    {
        $objectObjectService = $this->get('objectObjectService');
        switch ($mode) {
            case MonarcObject::MODE_GENERIC:
                $objects = $objectObjectService->getRecursiveParents($id);
                $field = 'parents';
                break;
            case MonarcObject::MODE_SPECIFIC:
                $objects = $objectObjectService->getRecursiveChildren($id);
                $field = 'children';
                break;
            default:
                return false;
                break;
        }
        return $this->checkModeIntegrityRecursive($mode, $field, $objects);
    }

    /**
     * Check Mode Integrity Recursive
     *
     * @param array $objects
     * @param $mode
     * @param $field
     * @return bool
     */
    private function checkModeIntegrityRecursive($mode, $field, $objects = [])
    {
        foreach ($objects as $p) {
            if ($p['mode'] == $mode || (!empty($p[$field]) && !$this->checkModeIntegrityRecursive($mode, $field, $p[$field]))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Delete
     *
     * @param $id
     * @throws \Monarc\Core\Exception\Exception
     */
    public function delete($id)
    {
        /** @var MonarcObjectTable $table */
        $table = $this->get('table');
        $entity = $table->get($id);
        if (!$entity) {
            throw new \Monarc\Core\Exception\Exception('Entity `id` not found.');
        }

        $table->delete($id);
    }

    /**
     * Duplicate
     *
     * @param $data
     * @return mixed
     * @throws \Monarc\Core\Exception\Exception
     */
    public function duplicate($data, $context = AbstractEntity::BACK_OFFICE)
    {
        try{
          $entity = $this->getEntity($data['id']);
        }catch(MappingException $e){
          $entity = $this->getEntity(['uuid' => $data['id'], 'anr' => $data['anr']]);
        }

        if (!$entity) {
            throw new \Monarc\Core\Exception\Exception('Entity `id` not found.');
        }

        $keysToRemove = ['uuid', 'position', 'creator', 'createdAt', 'updater', 'updatedAt', 'inputFilter', 'language', 'dbadapter', 'parameters'];
        foreach ($keysToRemove as $key) {
            unset($entity[$key]);
        }

        foreach ($this->dependencies as $dependency) {
            if (is_object($entity[$dependency])) {
              if($dependency=='asset')
                $entity[$dependency] = ['anr' => $data['anr'],'uuid' => $entity[$dependency]->uuid->toString()];
              else
                $entity[$dependency] = $entity[$dependency]->id;
            }
        }

        $keys = array_keys($entity);
        foreach ($keys as $key) {
            if (is_null($entity[$key])) {
                unset($entity[$key]);
            }
        }

        $entity['implicitPosition'] = isset($data['implicitPosition']) ? $data['implicitPosition'] : 2;
        $filter = [];
        for ($i = 1; $i <= 4; $i++) {
            if (!empty($entity['name' . $i])) {
                $filter['name' . $i] = $entity['name' . $i];
            }
        }

        $exist = current($this->get('table')->getEntityByFields($filter));
        $suff = 0;
        while (!empty($exist)) {
            $suff=time();
            $filterB = $filter;
            foreach ($filterB as $k => $v) {
                $filterB[$k] = $v . ' (copy #' . $suff . ')';
            }
            $exist = current($this->get('table')->getEntityByFields($filterB));
        }
        if ($suff > 0) {
            foreach ($filter as $k => $v) {
                $entity[$k] = $v . ' (copy #' . $suff . ')';
            }
        }

        $id = $this->create($entity, true, $context);

        //children
        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('objectObjectTable');
        try{
          $objectsObjects = $objectObjectTable->getEntityByFields(['father' => $data['id']]);
        }catch(QueryException $e){
          $objectsObjects = $objectObjectTable->getEntityByFields(['anr' => $data['anr'],'father' => ['anr' => $data['anr'], 'uuid' => $data['id']]]);
        }
        foreach ($objectsObjects as $objectsObject) {
          if($context == AbstractEntity::BACK_OFFICE)
          {
              $data = [
                'id' => is_string($objectsObject->child->uuid)?$objectsObject->child->uuid:$objectsObject->child->uuid->toString(),
                'implicitPosition' => $data['implicitPosition'],
            ];
          }else{
            $data = [
              'id' => is_string($objectsObject->child->uuid)?$objectsObject->child->uuid:$objectsObject->child->uuid->toString(),
              'implicitPosition' => $data['implicitPosition'],
              'anr' => $data['anr'],
            ];
          }

            $childId = $this->duplicate($data, $context);

            $newObjectObject = clone $objectsObject;
            $newObjectObject->setId(null);
            try{
              $newObjectObject->setFather($this->get('table')->getEntity($id));
              $newObjectObject->setChild($this->get('table')->getEntity($childId));
            }catch(MappingException $e){
              $newObjectObject->setFather($this->get('table')->getEntity(['anr' => $data['anr'], 'uuid' =>$id]));
              $newObjectObject->setChild($this->get('table')->getEntity(['anr' => $data['anr'], 'uuid' =>$childId]));
            }
            $objectObjectTable->save($newObjectObject);
        }

        return $id;
    }

    /**
     * Attach Object To Anr
     *
     * @param $object
     * @param $anrId
     * @param null $parent
     * @return null
     * @throws \Monarc\Core\Exception\Exception
     */
    public function attachObjectToAnr($object, $anrId, $parent = null, $objectObjectPosition = null, $context = AbstractEntity::BACK_OFFICE)
    {
        //object
        /** @var MonarcObjectTable $table */
        $table = $this->get('table');

        if (!is_object($object)) {
          try{
            $object = $table->getEntity($object);
          }catch(MappingException $e){
            $object = $table->getEntity(['uuid' =>$object, 'anr'=>$anrId]);
          }
        }

        if (!$object) {
            throw new \Monarc\Core\Exception\Exception('Object does not exist', 412);
        }

        if ($context == AbstractEntity::BACK_OFFICE) {
            //retrieve model
            /** @var ModelTable $modelTable */
            $modelTable = $this->get('modelTable');
            $model = $modelTable->getEntityByFields(['anr' => $anrId])[0];

            /*
                4 cas d'erreur:
                - model generique & objet specifique
                - model regulateur & objet generique
                - model regulateur & objet specifique & asset generique
                - model specifique ou regulateur & objet specifique non lié au model
            */

            if ($model) {
                $this->get('modelTable')->canAcceptObject($model->get('id'), $object);
            }
        }

        //retrieve anr
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->getEntity($anrId);
        if (!$anr) {
            throw new \Monarc\Core\Exception\Exception('This risk analysis does not exist', 412);
        }

        //add anr to object
        $object->addAnr($anr);

        //save object
        $id = $table->save($object);

        //retrieve root category
        if ($object->category && $object->category->id) {
            /** @var ObjectCategoryTable $objectCategoryTable */
            $objectCategoryTable = $this->get('categoryTable');
            $objectCategory = $objectCategoryTable->getEntity($object->category->id);
            $objectRootCategoryId = ($objectCategory->root) ? $objectCategory->root->id : $objectCategory->id;

            //add root category to anr
            /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
            $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
            $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'category' => $objectRootCategoryId]);
            if (!count($anrObjectCategories)) {
                $class = $this->get('anrObjectCategoryEntity');
                $anrObjectCategory = new $class();
                $anrObjectCategory->setDbAdapter($anrObjectCategoryTable->getDb());
                $anrObjectCategory->exchangeArray([
                    'anr' => $anr,
                    'category' => (($object->category->root) ? $object->category->root : $object->category),
                    'implicitPosition' => 2,
                ]);
                $anrObjectCategoryTable->save($anrObjectCategory);
            }
        } else {
            $objectRootCategoryId = null;
        }

        //children
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        $children = $objectObjectService->getChildren($object->uuid->toString(),$anrId);
        foreach ($children as $child) {
            try{
              $childObject = $table->getEntity($child->child->uuid->toString());
            }catch(QueryException $e){
              $childObject = $table->getEntity(['uuid' => $child->child->uuid->toString(), 'anr' => $anrId]);
            }
            $this->attachObjectToAnr($childObject, $anrId, $id, $child->position, $context);
        }

        return $id;
    }

    /**
     * Detach Object To Anr
     *
     * @param $objectId
     * @param $anrId
     * @throws \Monarc\Core\Exception\Exception
     */
    public function detachObjectToAnr($objectId, $anrId, $context = MonarcObject::BACK_OFFICE)
    {
        //verify object exist
        /** @var MonarcObjectTable $table */
        $table = $this->get('table');
        $object = $table->getEntity($objectId);
        if (!$object) {
            throw new \Monarc\Core\Exception\Exception('Object does not exist', 412);
        }

        //verify anr exist
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->getEntity($anrId);
        if (!$anr) {
            throw new \Monarc\Core\Exception\Exception('This risk analysis does not exist', 412);
        }

        //if object is not a component, delete link and instances children for anr
        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('objectObjectTable');
        try{
          $links = $objectObjectTable->getEntityByFields(['anr' => ($context == MonarcObject::BACK_OFFICE) ? 'null' : $anrId, 'child' => $objectId]);
        }catch(QueryException $e){
          $links = $objectObjectTable->getEntityByFields(['anr' => ($context == MonarcObject::BACK_OFFICE) ? 'null' : $anrId, 'child' => ['uuid' => $objectId, 'anr' => $anrId]]);
        } catch (MappingException $e) {
            $links = $objectObjectTable->getEntityByFields(['anr' => ($context == MonarcObject::BACK_OFFICE) ? 'null' : $anrId, 'child' => ['uuid' => $objectId, 'anr' => $anrId]]);
        }
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        foreach ($links as $link) {

            //retrieve instance with link father object
            $fatherInstancesIds = [];
            try{
              $fatherInstances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $link->father->uuid->toString()]);
            } catch(QueryException $e){
              $fatherInstances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => ['anr' => $anrId,'uuid' => $link->father->uuid->toString()]]);
            } catch (MappingException $e) {
                $fatherInstances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => ['anr' => $anrId,'uuid' => $link->father->uuid->toString()]]);
            }
            foreach ($fatherInstances as $fatherInstance) {
                $fatherInstancesIds[] = $fatherInstance->id;
            }

            //retrieve instance with link child object and delete instance child if parent id is concern by link
            try{
              $childInstances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $link->child->uuid->toString()]);
            }catch(QueryException $e){
              $childInstances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => ['anr' => $anrId, 'uuid' => $link->child->uuid->toString()]]);
            } catch (MappingException $e) {
                $childInstances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => ['anr' => $anrId, 'uuid' => $link->child->uuid->toString()]]);
            }
            foreach ($childInstances as $childInstance) {
                if (in_array($childInstance->parent->id, $fatherInstancesIds)) {
                    $instanceTable->delete($childInstance->id);
                }
            }

            //delete link
            $objectObjectTable->delete($link->id);
        }

        //retrieve number anr objects with the same root category than current objet
        $nbObjectsSameRootCategory = 0;
        if ($object->category) {
            $objectRootCategory = ($object->category->root) ? $object->category->root : $object->category;
            foreach ($anr->objects as $anrObject) {
                $anrObjectRootCategory = ($anrObject->category->root) ? $anrObject->category->root : $anrObject->category;
                if (($anrObjectRootCategory->id == $objectRootCategory->id) && ($anrObject->uuid->toString() != $object->uuid->toString())) {
                    $nbObjectsSameRootCategory++;
                }
            }
        } else {
            $objectRootCategory = null;
        }

        //if the last object of the category in the anr, delete category from anr
        if (!$nbObjectsSameRootCategory && $objectRootCategory) {
            //anrs objects categories
            /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
            $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
            $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId, 'category' => $objectRootCategory->id]);
            $i = 1;
            $nbAnrObjectCategories = count($anrObjectCategories);
            foreach ($anrObjectCategories as $anrObjectCategory) {
                $anrObjectCategoryTable->delete($anrObjectCategory->id, ($i == $nbAnrObjectCategories));
                $i++;
            }
        }

        //delete instance from anr
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        try{
          $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $objectId]);
        }catch(MappingException $e){
          $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => ['anr' => $anrId,'uuid' => $objectId]]);
        } catch(QueryException $e) {
          $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => ['anr' => $anrId,'uuid' => $objectId]]);
        }
        $i = 1;
        $nbInstances = count($instances);
        foreach ($instances as $instance) {
            $instanceTable->delete($instance->id, ($i == $nbInstances));
            $i++;
        }

        //detach object
        /** @var MonarcObjectTable $table */
        $table = $this->get('table');
        $object = $table->getEntity($objectId);
        $anrs = [];
        foreach ($object->anrs as $anr) {
            if ($anr->id != $anrId) {
                $anrs[] = $anr;
            }
        }
        $object->anrs = $anrs;
        $table->save($object);
    }

    /**
     * Get Categories Library By Anr
     *
     * @param $anrId
     * @return mixed
     */
    public function getCategoriesLibraryByAnr($anrId)
    {
        // Retrieve objects
        $anrObjects = [];
        $objectsCategories = [];

        /** @var MonarcObjectTable $MonarcObjectTable */
        $MonarcObjectTable = $this->get('table');
        $objects = $MonarcObjectTable->getEntityByFields(['anrs' => $anrId]);

        foreach ($objects as $object) {
            if ($object->get('category')) {
                $anrObjects[$object->get('category')->get('id')][] = $object->getJsonArray();
                if (!isset($objectsCategories[$object->get('category')->get('id')])) {
                    $objectsCategories[$object->get('category')->get('id')] = $object->get('category')->getJsonArray();
                }
            } else {
                $anrObjects[-1][] = $object->getJsonArray();
                if (!isset($objectsCategories[-1])) {
                    // Setup a virtual container category
                    $objectsCategories[-1] = [
                        'id' => -1,
                        'parent' => null,
                        'objects' => [],
                        'label1' => 'Sans catégorie',
                        'label2' => 'Uncategorized',
                        'label3' => 'Keine Kategorie',
                        'label4' => '',
                        'position' => -1,
                    ];
                }
            }
        }
        unset($objects);

        // Recursively get the parent categories to fill the tree completely
        $parents = [];
        foreach ($objectsCategories as $id => $category) {
            if ($id > 0) {
                $this->getRecursiveParents($category, $parents, true);
            }
        }

        // Concat both the current categories and their parents
        $objectsCategories = $objectsCategories + $parents;

        foreach ($anrObjects as $idCateg => $anrObject) {
            // add object to categories to field "objects"
            if (isset($objectsCategories[$idCateg])) {
                $objectsCategories[$idCateg]['objects'] = $anrObject;
            }
        }

        // Retrieve ANR's categories mapping as root categories can be sorted
        $anrObjectsCategories = [];
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields(['anr' => $anrId], ['position' => 'ASC']);

        foreach ($anrObjectCategories as $anrObjectCategory) {
            $anrObjectsCategories[$anrObjectCategory->id] = $this->getChildren($anrObjectCategory->category->getJsonArray(), $objectsCategories);
            $anrObjectsCategories[$anrObjectCategory->id]['position'] = $anrObjectCategory->get('position'); // overwrite categ position from anr_categ position
            $anrObjectsCategories[$anrObjectCategory->id]['objects'] = [];
            if (!empty($anrObjects[$anrObjectCategory->category->id])) {
                $anrObjectsCategories[$anrObjectCategory->id]['objects'] = $anrObjects[$anrObjectCategory->category->id]; // add objects
            }
        }
        unset($anrObjects);

        // If we created our virtual category, inject it at first position. We won't need to fill it again in the next
        // loop as we already have the objects and we don't have any sub-categories or nested levels.
        if (isset($objectsCategories[-1]) && (!empty($objectsCategories[-1]['objects']) || !empty($objectsCategories[-1]['child']))) {
            $objectsCategories[-1]['position'] = count($anrObjectsCategories) + 1; // on met cette "catégorie" à la fin
            $anrObjectsCategories[-1] = $objectsCategories[-1];
        } else {
            unset($anrObjectsCategories[-1]);
        }
        unset($objectsCategories);

        // Order categories by position
        usort($anrObjectsCategories, function ($a, $b) {
            return $this->sortCategories($a, $b);
        });
        foreach ($anrObjectsCategories as &$cat) {
            if (isset($cat['child']) && is_array($cat['child'])) {
                usort($cat['child'], function ($a, $b) {
                    return $this->sortCategories($a, $b);
                });
            }
        }

        return $anrObjectsCategories;
    }

    /**
     * Sort Categories
     *
     * @param $a
     * @param $b
     * @return int
     */
    protected function sortCategories($a, $b)
    {
        if (isset($a['position']) && isset($b['position'])) {
            return ($a['position'] - $b['position']);
        } else if (isset($a['position']) && !isset($b['position'])) {
            return -1;
        } else if (isset($b['position']) && !isset($a['position'])) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Get Recursive Parents
     *
     * @param $category
     * @param $array
     */
    public function getRecursiveParents($category, &$array, $objectToArray = false)
    {
        if (is_object($category)) {
            $category = $category->getJsonArray();
        }

        if ($category && $category['parent']) {
            /** @var ObjectCategoryTable $table */
            $table = $this->get('categoryTable');
            $parent = $table->getEntity($category['parent']->id);

            if ($objectToArray) {
                $array[$parent->id] = $parent->getJsonArray();
            } else {
                $array[$parent->id] = $parent;
            }

            $this->getRecursiveParents($parent, $array, $objectToArray);
        }
    }

    /**
     * Get Direct Parents
     *
     * @param $object_id
     * @return array
     */
    public function getDirectParents($object_id, $anrId = null)
    {
        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('objectObjectTable');
        return $objectObjectTable->getDirectParentsInfos($object_id->toString(),$anrId);
    }

    /**
     * Get Children
     *
     * @param $parentObjectCategory
     * @param $objectsCategories
     * @return mixed
     */
    public function getChildren($parentObjectCategory, &$objectsCategories)
    {
        $currentObjectCategory = $parentObjectCategory;
        unset($objectsCategories[$parentObjectCategory['id']]);

        foreach ($objectsCategories as $objectsCategory) {
            if ($objectsCategory['parent'] && $objectsCategory['parent']->id == $parentObjectCategory['id']) {
                $objectsCategory = $this->getChildren($objectsCategory, $objectsCategories);
                unset($objectsCategory['__initializer__']);
                unset($objectsCategory['__cloner__']);
                unset($objectsCategory['__isInitialized__']);
                $currentObjectCategory['child'][] = $objectsCategory;
            }
        }

        return $currentObjectCategory;
    }

    /**
     * Export
     *
     * @param $data
     * @return string
     * @throws \Monarc\Core\Exception\Exception
     */
    public function export(&$data)
    {
        if (empty($data['id'])) {
            throw new \Monarc\Core\Exception\Exception('Object to export is required', 412);
        }
        $filename = "";

        $exported = json_encode($this->get('objectExportService')->generateExportArray($data['id'], $data['anr'], $filename));
        $data['filename'] = $filename;

        if (! empty($data['password'])) {
            $exported = $this->encrypt($exported, $data['password']);
        }

        return $exported;
    }
}