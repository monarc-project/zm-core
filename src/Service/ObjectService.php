<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Model\Entity\AmvSuperClass;
use Monarc\Core\Model\Entity\AnrObjectCategory;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Table\AnrObjectCategoryTable;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\ObjectCategoryTable;
use Monarc\Core\Model\Table\ObjectObjectTable;
use Monarc\Core\Model\Table\MonarcObjectTable;
use Monarc\Core\Table;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;
use Monarc\Core\Model\Table\RolfTagTable;

/**
 * Object Service
 *
 * Class ObjectService
 * @package Monarc\Core\Service
 */
class ObjectService extends AbstractService
{
    protected $objectObjectService;
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
     * @param null $modelId
     * @param null $anr
     * @param null $lock
     *
     * @return array
     * @throws Exception
     */
    public function getListSpecific($page = 1, $limit = 25, $order = null, $filter = null, $asset = null, $category = null, $modelId = null, $anr = null, $lock = null)
    {
        /** @var AssetService $assetService */
        $assetService = $this->get('assetService');
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
                $child = $lock == 'true' ? [] : $categoryTable->getDescendants($category);
                $child[] = $category;
            } elseif ($category == -1) {
                $child = null;
            }

            $filterAnd['category'] = $child;
        }

        $objects = $this->getAnrObjects($page, $limit, $order, $filter, $filterAnd, $modelId, $anr);

        $rootArray = [];

        foreach ($objects as $object) {
            /** @var Asset $asset */
            $asset = $object['asset'];
            $object['asset'] = $assetService->prepareAssetDataResult($asset);
            if (!empty($object['category'])) {
                $object['category'] = $categoryTable->get($object['category']->getId());
            }
            $rootArray[(string)$object['uuid']] = $object;
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
     * @param $modelId
     * @param $anr
     *
     * @return array|bool
     */
    public function getAnrObjects($page, $limit, $order, $filter, $filterAnd, $modelId, $anr, $context = AbstractEntity::BACK_OFFICE)
    {
        if ($modelId) {
            /** @var Table\ModelTable $modelTable */
            $modelTable = $this->get('modelTable');
            /** @var Model $model */
            $model = $modelTable->findById($modelId);
            if ($model->isGeneric()) { // le modèle est générique, on récupère les modèles génériques
                $filterAnd['mode'] = MonarcObject::MODE_GENERIC;
            } else {
                $filterAnd['asset'] = [];
                $assets = $model->getAssets();
                foreach ($assets as $a) { // on récupère tous les assets associés au modèle et on ne prend que les spécifiques
                    if ($a->get('mode') == MonarcObject::MODE_SPECIFIC) {
                        $filterAnd['asset'][$a->getUuid()] = $a->getUuid();
                    }
                }
                if (!$model->isRegulator()) { // si le modèle n'est pas régulateur
                    $assets = $this->get('assetTable')->getEntityByFields(['mode' => MonarcObject::MODE_GENERIC]); // on récupère tous les assets génériques
                    foreach ($assets as $a) {
                        $filterAnd['asset'][$a->getUuid()] = $a->getUuid();
                    }
                }
                if (!empty($filterAnd['asset'])) {
                    $filterAnd['asset'] = array_values($filterAnd['asset']);
                }
            }
            if ($context != AbstractEntity::FRONT_OFFICE) {
                $objects = $model->getAnr()->getObjects();
                if (!empty($objects)) { // on enlève tout les objets déjà liés
                    foreach ($objects as $o) {
                        $filterAnd['uuid']['value'][$o->getUuid()] = $o->getUuid();
                    }
                    if (!empty($filterAnd['uuid']['value'])) {
                        $filterAnd['uuid'] = [
                            'op' => 'NOT IN',
                            'value' => array_values($filterAnd['uuid']['value']),
                        ];
                    }
                }
            }
        } elseif ($anr) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anrObj = $anrTable->getEntity($anr);
            $objects = $anrObj->get('objects');
            $value = [];
            foreach ($objects as $o) { // on en prend que les objets déjà liés (composants)
                $value[] = $o->getUuid();
            }
            if (empty($value)) {
                return [];
            }
            $filterAnd['uuid'] = ['op' => 'IN', 'value' => $value];
        }

        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');

        return $monarcObjectTable->fetchAllFiltered(
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
     *
     * @return mixed
     * @throws Exception
     */
    public function getCompleteEntity($id, $anrContext = MonarcObject::CONTEXT_BDC, $anr = null)
    {
        $monarcFO = false;
        $table = $this->get('table');
        try {
            /** @var MonarcObject $object */
            $object = $table->getEntity($id);
        } catch (QueryException | MappingException $e) {
            $object = $table->getEntity(['uuid' => $id, 'anr' => $anr]);
            $monarcFO = true;
        }

        $objectArr = $object->getJsonArray();

        // Retrieve children recursively
        /** @var ObjectObjectService $objectObjectService */
        $objectObjectService = $this->get('objectObjectService');
        if ($object->getAnr() == null) {
            $objectArr['children'] = $objectObjectService->getRecursiveChildren((string)$objectArr['uuid'], null);
        } else {
            $objectArr['children'] = $objectObjectService->getRecursiveChildren((string)$objectArr['uuid'], $anr);
        }

        // Calculate the risks table
        $objectArr['risks'] = $this->getRisks($object);
        $objectArr['oprisks'] = $this->getRisksOp($object);
        $objectArr['parents'] = $this->getDirectParents((string)$objectArr['uuid'], $anr);

        // Retrieve parent recursively
        if ($anrContext == MonarcObject::CONTEXT_ANR) {
            //Check if the object is linked to the $anr
            $found = false;
            $anrObject = null;
            foreach ($object->getAnrs() as $a) {
                if ($a->getId() === $anr) {
                    $found = true;
                    $anrObject = $a;
                    break;
                }
            }

            if (!$found) {
                throw new Exception('This object is not bound to the ANR', 412);
            }

            if (!$anr) {
                throw new Exception('Anr missing', 412);
            }

            /** @var InstanceTable $instanceTable */
            $instanceTable = $this->get('instanceTable');
            if ($monarcFO) {
                $instances = $instanceTable->getEntityByFields([
                    'anr' => $anr,
                    'object' => ['uuid' => $id, 'anr' => $anr],
                ]);
            } else {
                $instances = $instanceTable->getEntityByFields(['anr' => $anr, 'object' => $id]);
            }


            $instances_arr = [];
            /** @var InstanceSuperClass $instance */
            foreach ($instances as $instance) {
                $instanceHierarchy = $instance->getHierarchyArray();

                $names = [
                    'name1' => $anrObject->getLabelByLanguageIndex(1),
                    'name2' => $anrObject->getLabelByLanguageIndex(2),
                    'name3' => $anrObject->getLabelByLanguageIndex(3),
                    'name4' => $anrObject->getLabelByLanguageIndex(4),
                ];
                foreach ($instanceHierarchy as $instanceData) {
                    $names['name1'] .= ' > ' . $instanceData['name1'];
                    $names['name2'] .= ' > ' . $instanceData['name2'];
                    $names['name3'] .= ' > ' . $instanceData['name3'];
                    $names['name4'] .= ' > ' . $instanceData['name4'];
                }
                $names['id'] = $instance->get('id');
                $instances_arr[] = $names;
            }

            $objectArr['replicas'] = $instances_arr;
        } else {
            $anrIds = [];
            foreach ($object->getAnrs() as $item) {
                $anrIds[] = $item->getId();
            }

            $objectArr['replicas'] = [];
            if (!empty($anrIds)) {
                /** @var Table\ModelTable $modelTable */
                $modelTable = $this->get('modelTable');
                $models = $modelTable->findByAnrIds($anrIds);

                $modelsData = [];
                foreach ($models as $model) {
                    $modelsData[] = [
                        'id' => $model->getId(),
                        'label1' => $model->getLabel(1),
                        'label2' => $model->getLabel(2),
                        'label3' => $model->getLabel(3),
                        'label4' => $model->getLabel(4),
                    ];
                }

                $objectArr['replicas'] = $modelsData;
            }
        }

        return $objectArr;
    }

    protected function getRisks(ObjectSuperClass $object): array
    {
        /** @var Table\AmvTable $amvTable */
        $amvTable = $this->get('amvTable');
        // TODO: Check if it works of FO.
        $params = (new FormattedInputParams())
            ->addFilter('asset', ['value' => $object->getAsset()])
            ->addOrder('position', Criteria::ASC);
        /** @var AmvSuperClass[] $amvs */
        $amvs = $amvTable->findByParams($params);

        $risks = [];
        foreach ($amvs as $amv) {
            $risks[] = [
                'id' => $amv->getUuid(),
                'threatLabel1' => $amv->getThreat()->getLabel(1),
                'threatLabel2' => $amv->getThreat()->getLabel(2),
                'threatLabel3' => $amv->getThreat()->getLabel(3),
                'threatLabel4' => $amv->getThreat()->getLabel(4),
                'threatDescription1' => $amv->getThreat()->getDescription(1),
                'threatDescription2' => $amv->getThreat()->getDescription(2),
                'threatDescription3' => $amv->getThreat()->getDescription(3),
                'threatDescription4' => $amv->getThreat()->getDescription(4),
                'threatRate' => '-',
                'vulnLabel1' => $amv->getVulnerability()->getLabel(1),
                'vulnLabel2' => $amv->getVulnerability()->getLabel(2),
                'vulnLabel3' => $amv->getVulnerability()->getLabel(3),
                'vulnLabel4' => $amv->getVulnerability()->getLabel(4),
                'vulnDescription1' => $amv->getVulnerability()->getDescription(1),
                'vulnDescription2' => $amv->getVulnerability()->getDescription(2),
                'vulnDescription3' => $amv->getVulnerability()->getDescription(3),
                'vulnDescription4' => $amv->getVulnerability()->getDescription(4),
                'vulnerabilityRate' => '-',
                'c_risk' => '-',
                'c_risk_enabled' => $amv->getThreat()->getConfidentiality(),
                'i_risk' => '-',
                'i_risk_enabled' => $amv->getThreat()->getIntegrity(),
                'd_risk' => '-',
                'd_risk_enabled' => $amv->getThreat()->getAvailability(),
                'comment' => '',
            ];
        }

        return $risks;
    }

    /**
     * Get Risks Op
     *
     * @param $object
     *
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
     *
     * @return int
     */
    public function getFilteredCount($filter = null, $asset = null, $category = null, $modelId = null, $anr = null, $context = MonarcObject::BACK_OFFICE)
    {
        $filterAnd = [];
        if ((!is_null($asset)) && ($asset != 0)) {
            $filterAnd['asset'] = $asset;
        }
        if ((!is_null($category)) && ($category != 0)) {
            $filterAnd['category'] = $category;
        }

        $result = $this->getAnrObjects(1, 0, null, $filter, $filterAnd, $modelId, $anr, $context);

        return count($result);
    }

    /**
     * Recursive child
     *
     * @param $hierarchy
     * @param $parent
     * @param $childHierarchy
     *
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

        $result = $objectsArray[$parent];
        $this->formatDependencies($result, $this->dependencies);
        if ($children) {
            $result['childs'] = $children;
        }

        return $result;
    }

    /**
     * @param $data
     * @param bool $last
     * @param string $context
     *
     * @return mixed
     * @throws Exception
     */
    public function create($data, $last = true, $context = AbstractEntity::BACK_OFFICE)
    {
        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');
        $entity = $monarcObjectTable->getEntityClass();
        /** @var MonarcObject $monarcObject */
        $monarcObject = new $entity;
        $monarcObject->setLanguage($this->getLanguage());
        $monarcObject->setDbAdapter($monarcObjectTable->getDb());

        //in FO, all objects are generics
        if ($context == AbstractEntity::FRONT_OFFICE) {
            $data['mode'] = MonarcObject::MODE_GENERIC;
        }

        $setRolfTagNull = false;
        if (empty($data['rolfTag'])) {
            unset($data['rolfTag']);
            $setRolfTagNull = true;
        }


        $anr = null;
        if (!empty($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->findById((int)$data['anr']);

            $monarcObject->setAnr($anr);
        }

        if (!empty($data['mosp'])) {
            $monarcObject = $this->importFromMosp($data, $anr);

            return $monarcObject ? $monarcObject->getUuid() : null;
        }

        // Si asset secondaire, pas de rolfTag
        if (!empty($data['asset']) && !empty($data['rolfTag'])) {
            /** @var Table\AssetTable $assetTable */
            $assetTable = $this->get('assetTable');
            $asset = $assetTable->findByUuid($data['asset']);
            if (!$asset->isPrimary()) {
                unset($data['rolfTag']);
                $setRolfTagNull = true;
            }
        }

        $monarcObject->setDbAdapter($monarcObjectTable->getDb());
        $monarcObject->exchangeArray($data);

        //object dependencies
        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($monarcObject, $dependencies);

        $monarcObject->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        if ($setRolfTagNull) {
            $monarcObject->set('rolfTag', null);
        }

        if (empty($data['category'])) {
            $data['category'] = null;
        }

        if (isset($data['source'])) {
            $monarcObject->source = $monarcObjectTable->getEntity($data['source']);
        }

        //security
        if ($context == AbstractEntity::BACK_OFFICE &&
            $monarcObject->isModeGeneric()
            && $monarcObject->getAsset()->isModeSpecific()
        ) {
            throw new Exception("You can't have a generic object based on a specific asset", 412);
        }
        $model = null;
        if (isset($data['modelId'])) {
            /** @var Table\ModelTable $modelTable */
            $modelTable = $this->get('modelTable');
            $model = $modelTable->findByAnrId($data['modelId']);
            $model->validateObjectAcceptance($monarcObject);
        }

        if ($monarcObject->isScopeGlobal() && $monarcObject->getAsset()->isPrimary()) {
            throw new Exception('You cannot create an object that is both global and primary', 412);
        }

        if ($context === MonarcObject::BACK_OFFICE) {
            //create object type bdc
            $id = $monarcObjectTable->save($monarcObject);

            //attach object to anr
            if ($model !== null) {
                $id = $this->attachObjectToAnr($monarcObject, $model->getAnr()->getId());
            }
        } elseif ($anr) {
            $id = $this->attachObjectToAnr($monarcObject, $anr, null, null, $context);
        } else {
            //create object type anr
            $id = $monarcObjectTable->save($monarcObject);
        }

        return $id;
    }

    /**
     * TODO: We are not going to implement it now.
     */
    protected function importFromMosp(array $data, ?AnrSuperClass $anr): ?ObjectSuperClass
    {
        return null;
        ///** @var ObjectImportService $objectImportService */
        //$objectImportService = $this->get('objectImportService');
        //$objectImportService->importFromMosp($data);
    }

    public function update($id, $data, $context = AbstractEntity::BACK_OFFICE)
    {
        $anrIds = $data['anrs'];
        unset($data['anrs']);
        if (empty($data)) {
            throw new Exception('Data missing', 412);
        }

        //in FO, all objects are generics
        if ($context === AbstractEntity::FRONT_OFFICE) {
            $data['mode'] = MonarcObject::MODE_GENERIC;
        }

        try {
            /** @var MonarcObject $monarcObject */
            $monarcObject = $this->get('table')->getEntity($id);
        } catch (QueryException | MappingException $e) {
            $monarcObject = $this->get('table')->getEntity(['uuid' => $id, 'anr' => $data['anr']]);
        }
        if (!$monarcObject) {
            throw new Exception('Entity `id` not found.');
        }
        $monarcObject->setDbAdapter($this->get('table')->getDb());
        $monarcObject->setLanguage($this->getLanguage());

        $setRolfTagNull = false;
        if (empty($data['rolfTag'])) {
            unset($data['rolfTag']);
            $setRolfTagNull = true;
        }

        if (isset($data['scope']) && $data['scope'] != $monarcObject->getScope()) {
            throw new Exception('You cannot change the scope of an existing object.', 412);
        }

        if (isset($data['asset']) && $data['asset'] != $monarcObject->getAsset()->getUuid()) {
            throw new Exception('You cannot change the asset type of an existing object.', 412);
        }

        if (isset($data['mode']) && $data['mode'] != $monarcObject->get('mode') &&
            !$this->checkModeIntegrity($monarcObject->getUuid(), $monarcObject->get('mode'))) {
            /* on test:
            - que l'on a pas de parents GENERIC quand on passe de GENERIC à SPECIFIC
            - que l'on a pas de fils SPECIFIC quand on passe de SPECIFIC à GENERIC
            */
            if ($monarcObject->get('mode') == MonarcObject::MODE_GENERIC) {
                throw new Exception(
                    'You cannot set this object to specific mode because one of its parents is in generic mode.',
                    412
                );
            }

            throw new Exception(
                'You cannot set this object to generic mode because one of its children is in specific mode.',
                412
            );
        }

        // Si asset secondaire, pas de rolfTag
        if (!empty($data['asset']) && !empty($data['rolfTag'])) {
            /** @var Table\AssetTable $assetTable */
            $assetTable = $this->get('assetTable');
            $asset = $assetTable->findByUuid($data['asset']);
            if (!$asset->isPrimary()) {
                unset($data['rolfTag']);
                $setRolfTagNull = true;
            }
        }

        // As a temporary solution to allow moving objects out from "uncategorised" category.
        $oldRootCategory = null;
        if ($monarcObject->getCategory() !== null) {
            $oldRootCategory = $monarcObject->getCategory()->getRoot() ?: $monarcObject->getCategory();
        }

        $newRolfTag = false;
        if (!empty($data['rolfTag'])
            && (
                $monarcObject->getRolfTag() === null
                || $data['rolfTag'] !== $monarcObject->getRolfTag()->getId()
            )
        ) {
            $newRolfTag = $data['rolfTag'];
        }

        $monarcObject->exchangeArray($data, true);

        $dependencies = property_exists($this, 'dependencies') ? $this->dependencies : [];
        $this->setDependencies($monarcObject, $dependencies);

        if ($setRolfTagNull) {
            $monarcObject->setRolfTag(null);
        }

        $monarcObject->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');
        $monarcObjectTable->saveEntity($monarcObject);

        $newRootCategory = $monarcObject->getCategory()->getRoot() ?: $monarcObject->getCategory();

        if ($oldRootCategory !== $newRootCategory) {
            /*
             * AnrObjectCategory entity used only to link Anrs with root categories.
             * TODO: AnrObjectCategory is not really needed, can be replaced with the ObjectCategory usage with a status field.
             * Status can tell us about visibility on UI (when no objects left we don't show the root category),
             * but seems it works well without status.
             */
            $anr = $monarcObject->getAnr();
            /*
             * For Backoffice we should fetch all the Models (but as they are linked with Anr OneToOne we go for them),
             * and update the links for every Anr relation.
             */
            if ($anr === null && !empty($anrIds)) {
                $anrs = $this->get('anrTable')->findByIds(array_column($anrIds, 'id'));
            } else {
                $anrs = [$anr];
            }

            foreach ($anrs as $anr) {
                // As a temporary solution to allow moving objects out from "uncategorised" category.
                if ($oldRootCategory !== null) {
                    $this->unlinkCategoryFromAnrIfNoObjectsOrChildrenLeft($oldRootCategory, $anr);
                }

                $this->linkCategoryWithAnrIfNotLinked($newRootCategory, $anr);
            }
        }

        $this->instancesImpacts($monarcObject, $newRolfTag, $setRolfTagNull);

        return $id;
    }

    /**
     * Patch
     *
     * @param $id
     * @param $data
     *
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

        try {
            /** @var MonarcObject $monarcObject */
            $monarcObject = $this->get('table')->getEntity($id);
        } catch (QueryException | MappingException $e) {
            $monarcObject = $this->get('table')->getEntity(['uuid' => $id, 'anr' => $data['anr']]);
        }
        $monarcObject->setLanguage($this->getLanguage());
        unset($data['anr']);

        $rolfTagId = ($monarcObject->rolfTag) ? $monarcObject->rolfTag->id : null;

        $monarcObject->exchangeArray($data, true);

        if ($monarcObject->rolfTag) {
            $newRolfTagId = (is_int($monarcObject->rolfTag)) ? $monarcObject->rolfTag : $monarcObject->rolfTag->id;
            $newRolfTag = ($rolfTagId == $newRolfTagId) ? false : $monarcObject->rolfTag;
        } else {
            $newRolfTag = false;
        }

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($monarcObject, $dependencies);

        $monarcObject->setUpdater(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        $this->get('table')->save($monarcObject);

        $this->instancesImpacts($monarcObject, $newRolfTag, $setRolfTagNull);

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
        try {
            $instances = $instanceTable->getEntityByFields(['object' => $object]);
        } catch (MappingException | QueryException $e) {
            $instances = $instanceTable->getEntityByFields([
                'object' => [
                    'uuid' => $object->getUuid(),
                    'anr' => $object->anr->id,
                ],
            ]);
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
                            'object' => $object->getUuid(),
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
     *
     * @return bool
     */
    protected function checkModeIntegrity($id, $mode)
    {
        /** @var ObjectObjectService $objectObjectService */
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
     *
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
     *
     * @throws Exception
     */
    public function delete($id)
    {
        /** @var MonarcObjectTable $table */
        $table = $this->get('table');
        $entity = $table->get($id);
        if (!$entity) {
            throw new Exception('Entity `id` not found.');
        }

        $table->delete($id);
    }

    /**
     * Duplicate
     *
     * @param $data
     *
     * @return mixed
     * @throws Exception
     */
    public function duplicate($data, $context = AbstractEntity::BACK_OFFICE)
    {
        try {
            $entity = $this->getEntity($data['id']);
        } catch (QueryException | MappingException $e) {
            $entity = $this->getEntity(['uuid' => $data['id'], 'anr' => $data['anr']]);
        }

        if (!$entity) {
            throw new Exception('Entity `id` not found.');
        }

        $keysToRemove = [
            'uuid',
            'position',
            'creator',
            'createdAt',
            'updater',
            'updatedAt',
            'inputFilter',
            'language',
            'dbadapter',
            'parameters',
        ];
        foreach ($keysToRemove as $key) {
            unset($entity[$key]);
        }

        foreach ($this->dependencies as $dependency) {
            if (is_object($entity[$dependency])) {
                if ($dependency == 'asset') {
                    $entity[$dependency] = ['anr' => $data['anr'], 'uuid' => $entity[$dependency]->getUuid()];
                } else {
                    $entity[$dependency] = $entity[$dependency]->id;
                }
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
            $suff = time();
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
        try {
            $objectsObjects = $objectObjectTable->getEntityByFields(['father' => $data['id']]);
        } catch (QueryException | MappingException $e) {
            $objectsObjects = $objectObjectTable->getEntityByFields([
                'anr' => $data['anr'],
                'father' => [
                    'anr' => $data['anr'],
                    'uuid' => $data['id'],
                ],
            ]);
        }
        foreach ($objectsObjects as $objectsObject) {
            if ($context == AbstractEntity::BACK_OFFICE) {
                $data = [
                    'id' => $objectsObject->getChild()->getUuid(),
                    'implicitPosition' => $data['implicitPosition'],
                ];
            } else {
                $data = [
                    'id' => $objectsObject->getChild()->getUuid(),
                    'implicitPosition' => $data['implicitPosition'],
                    'anr' => $data['anr'],
                ];
            }

            $childId = $this->duplicate($data, $context);

            $newObjectObject = clone $objectsObject;
            $newObjectObject->setId(null);
            try {
                $newObjectObject->setFather($this->get('table')->getEntity($id));
                $newObjectObject->setChild($this->get('table')->getEntity($childId));
            } catch (QueryException | MappingException $e) {
                $newObjectObject->setFather($this->get('table')->getEntity(['anr' => $data['anr'], 'uuid' => $id]));
                $newObjectObject->setChild($this->get('table')->getEntity(['anr' => $data['anr'], 'uuid' => $childId]));
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
     *
     * @return null
     * @throws Exception
     */
    public function attachObjectToAnr($object, $anrId, $parent = null, $objectObjectPosition = null, $context = AbstractEntity::BACK_OFFICE)
    {
        //object
        /** @var MonarcObjectTable $table */
        $table = $this->get('table');

        if (!is_object($object)) {
            try {
                $object = $table->getEntity($object);
            } catch (QueryException | MappingException $e) {
                $object = $table->getEntity(['uuid' => $object, 'anr' => $anrId]);
            }
        }

        if (!$object) {
            throw new Exception('Object does not exist', 412);
        }

        if ($context == AbstractEntity::BACK_OFFICE) {
            //retrieve model
            /** @var Table\ModelTable $modelTable */
            $modelTable = $this->get('modelTable');
            $model = $modelTable->findByAnrId($anrId);

            /*
                4 cas d'erreur:
                - model generique & objet specifique
                - model regulateur & objet generique
                - model regulateur & objet specifique & asset generique
                - model specifique ou regulateur & objet specifique non lié au model
            */

            if ($model !== null) {
                $model->validateObjectAcceptance($object);
            }
        }

        //retrieve anr
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->getEntity($anrId);
        if (!$anr) {
            throw new Exception('This risk analysis does not exist', 412);
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
            $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields([
                'anr' => $anrId,
                'category' => $objectRootCategoryId,
            ]);
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
        $children = $objectObjectService->getChildren($object->getUuid(), $anrId);
        foreach ($children as $child) {
            try {
                $childObject = $table->getEntity($child->getChild()->getUuid());
            } catch (QueryException | MappingException $e) {
                $childObject = $table->getEntity(['uuid' => $child->getChild()->getUuid(), 'anr' => $anrId]);
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
     *
     * @throws Exception
     */
    public function detachObjectToAnr($objectId, $anrId, $context = MonarcObject::BACK_OFFICE)
    {
        //verify object exist
        /** @var MonarcObjectTable $table */
        $table = $this->get('table');
        /** @var ObjectSuperClass $object */
        $object = $table->getEntity($objectId);
        if (!$object) {
            throw new Exception('Object does not exist', 412);
        }

        //verify anr exist
        /** @var AnrTable $anrTable */
        $anrTable = $this->get('anrTable');
        $anr = $anrTable->getEntity($anrId);
        if (!$anr) {
            throw new Exception('This risk analysis does not exist', 412);
        }

        //if object is not a component, delete link and instances children for anr
        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('objectObjectTable');
        try {
            $links = $objectObjectTable->getEntityByFields([
                'anr' => $context === MonarcObject::BACK_OFFICE ? 'null' : $anrId,
                'child' => $objectId,
            ]);
        } catch (QueryException | MappingException $e) {
            $links = $objectObjectTable->getEntityByFields([
                'anr' => $context === MonarcObject::BACK_OFFICE ? 'null' : $anrId,
                'child' => ['uuid' => $objectId, 'anr' => $anrId],
            ]);
        }
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        foreach ($links as $link) {
            //retrieve instance with link father object
            $fatherInstancesIds = [];
            try {
                $fatherInstances = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => $link->getFather()->getUuid(),
                ]);
            } catch (QueryException | MappingException $e) {
                $fatherInstances = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => [
                        'anr' => $anrId,
                        'uuid' => $link->getFather()->getUuid(),
                    ],
                ]);
            }

            foreach ($fatherInstances as $fatherInstance) {
                $fatherInstancesIds[] = $fatherInstance->id;
            }

            //retrieve instance with link child object and delete instance child if parent id is concern by link
            try {
                $childInstances = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => $link->getChild()->getUuid(),
                ]);
            } catch (QueryException | MappingException $e) {
                $childInstances = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => [
                        'anr' => $anrId,
                        'uuid' => $link->getChild()->getUuid(),
                    ],
                ]);
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
        $areObjectsUnderTheRootCategory = false;
        $objectRootCategory = null;
        if ($object->getCategory()) {
            $objectRootCategory = $object->getCategory()->getRoot() ?: $object->getCategory();
            $areObjectsUnderTheRootCategory = $table->hasObjectsUnderRootCategoryExcludeObject($objectRootCategory, $object);
        }

        //if the last object of the category in the anr, delete category from anr
        if (!$areObjectsUnderTheRootCategory && $objectRootCategory) {
            //anrs objects categories
            /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
            $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
            $anrObjectCategories = $anrObjectCategoryTable->getEntityByFields([
                'anr' => $anrId,
                'category' => $objectRootCategory->getId(),
            ]);
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
        try {
            $instances = $instanceTable->getEntityByFields(['anr' => $anrId, 'object' => $objectId]);
        } catch (QueryException | MappingException $e) {
            $instances = $instanceTable->getEntityByFields([
                'anr' => $anrId,
                'object' => ['anr' => $anrId, 'uuid' => $objectId],
            ]);
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
     *
     * @return mixed
     */
    public function getCategoriesLibraryByAnr($anrId)
    {
        // Retrieve objects
        $anrObjects = [];
        $objectsCategories = [];

        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');
        /** @var ObjectSuperClass[] $objects */
        $objects = $monarcObjectTable->getEntityByFields(['anrs' => $anrId]);

        foreach ($objects as $object) {
            if ($object->getCategory()) {
                $anrObjects[$object->getCategory()->getId()][] = $object->getJsonArray();
                if (!isset($objectsCategories[$object->getCategory()->getId()])) {
                    $objectsCategories[$object->getCategory()->getId()] = $object->getCategory()->getJsonArray();
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
        /** @var AnrTable $anrTable */
        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrTable = $this->get('anrTable');
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategories = $anrObjectCategoryTable->findByAnrOrderedByPosititon($anrTable->findById($anrId));

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
     *
     * @return int
     */
    protected function sortCategories($a, $b)
    {
        if (isset($a['position'], $b['position'])) {
            return ($a['position'] - $b['position']);
        }

        if (isset($a['position']) && !isset($b['position'])) {
            return -1;
        }

        if (isset($b['position']) && !isset($a['position'])) {
            return 1;
        }

        return 0;
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
     *
     * @return array
     */
    public function getDirectParents($object_id, $anrId = null)
    {
        /** @var ObjectObjectTable $objectObjectTable */
        $objectObjectTable = $this->get('objectObjectTable');

        return $objectObjectTable->getDirectParentsInfos($object_id, $anrId);
    }

    private function getChildren(array $parentObjectCategory, array &$objectsCategories): array
    {
        $currentObjectCategory = $parentObjectCategory;
        unset($objectsCategories[$parentObjectCategory['id']]);

        foreach ($objectsCategories as $objectsCategory) {
            if ($objectsCategory['parent'] && $objectsCategory['parent']->getId() === $parentObjectCategory['id']) {
                $objectsCategory = $this->getChildren($objectsCategory, $objectsCategories);
                $currentObjectCategory['child'][] = $objectsCategory;
            }
            unset(
                $objectsCategory['__initializer__'],
                $objectsCategory['__cloner__'],
                $objectsCategory['__isInitialized__']
            );
        }

        return $currentObjectCategory;
    }

    /**
     * @param $data
     *
     * @return false|string
     * @throws Exception
     * @throws EntityNotFoundException
     * @throws NonUniqueResultException
     */
    public function export(&$data)
    {
        if (empty($data['id'])) {
            throw new Exception('Object to export is required', 412);
        }

        /** @var ObjectExportService $objectExportService */
        $objectExportService = $this->get('objectExportService');
        $isForMosp = !empty($data['mosp']);
        if ($isForMosp) {
            $object = $objectExportService->generateExportMospArray($data['id']);
        } else {
            $object = $objectExportService->generateExportArray($data['id'], false);
        }

        $exported = json_encode($object);

        $data['filename'] = $objectExportService->generateExportFileName($data['id'], $isForMosp);

        if (!empty($data['password'])) {
            $exported = $this->encrypt($exported, $data['password']);
        }

        return $exported;
    }

    private function unlinkCategoryFromAnrIfNoObjectsOrChildrenLeft(
        ObjectCategorySuperClass $objectCategory,
        AnrSuperClass $anr
    ): void {
        /** @var MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');
        // Check if there are no more objects left under the root category or its children ones.
        $rootCategory = $objectCategory->getRoot() ?: $objectCategory;
        if ($monarcObjectTable->hasObjectsUnderRootCategoryExcludeObject($rootCategory)) {
            return;
        }

        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');

        // Remove the relation with Anr (AnrObjectCategory) if exists.
        $anrObjectCategory = $anrObjectCategoryTable->findOneByAnrAndObjectCategory($anr, $objectCategory);
        if ($anrObjectCategory !== null) {
            $anrObjectCategoryTable->delete($anrObjectCategory->getId());
        }
    }

    private function linkCategoryWithAnrIfNotLinked(
        ObjectCategorySuperClass $objectCategory,
        AnrSuperClass $anr
    ): void {
        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategory = $anrObjectCategoryTable->findOneByAnrAndObjectCategory($anr, $objectCategory);
        if ($anrObjectCategory !== null) {
            return;
        }

        /** @var AnrObjectCategory $anrObjectCategory */
        $anrObjectCategory = $this->get('anrObjectCategoryEntity');
        $anrObjectCategory = new $anrObjectCategory;
        $anrObjectCategory->setAnr($anr)->setCategory($objectCategory);

        /** @var AnrObjectCategoryTable $anrObjectCategoryTable */
        $anrObjectCategoryTable = $this->get('anrObjectCategoryTable');
        $anrObjectCategory->setDbAdapter($anrObjectCategoryTable->getDb());
        $anrObjectCategory->exchangeArray(['implicitPosition' => 2]);

        $anrObjectCategoryTable->save($anrObjectCategory);
    }
}
