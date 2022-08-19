<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\NonUniqueResultException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Model\Entity\AnrObjectCategory;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectCategory;
use Monarc\Core\Model\Entity\ObjectCategorySuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\InstanceRiskOpTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Table;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query\QueryException;
use Monarc\Core\Model\Table\RolfTagTable;
use Monarc\Core\Table\MonarcObjectTable;

class ObjectService
{
    public const MODE_OBJECT_EDIT = 'edit';
    public const MODE_KNOWLEDGE_BASE = 'bdc';
    public const MODE_ANR = 'anr';

    private Table\MonarcObjectTable $monarcObjectTable;

    private Table\AssetTable $assetTable;

    private Table\ModelTable $modelTable;

    private Table\ObjectObjectTable $objectObjectTable;

    private Table\ObjectCategoryTable $objectCategoryTable;

    private Table\AnrObjectCategoryTable $anrObjectCategoryTable;

    private InstanceTable $instanceTable;

    private ObjectObjectService $objectObjectService;

    private UserSuperClass $connectedUser;

    public function __construct(
        Table\MonarcObjectTable $monarcObjectTable,
        Table\AssetTable $assetTable,
        Table\ModelTable $modelTable,
        Table\ObjectObjectTable $objectObjectTable,
        Table\ObjectCategoryTable $objectCategoryTable,
        Table\AnrObjectCategoryTable $anrObjectCategoryTable,
        InstanceTable $instanceTable,
        ObjectObjectService $objectObjectService,
        ConnectedUserService $connectedUserService
    ) {
        $this->monarcObjectTable = $monarcObjectTable;
        $this->assetTable = $assetTable;
        $this->modelTable = $modelTable;
        $this->objectObjectTable = $objectObjectTable;
        $this->objectCategoryTable = $objectCategoryTable;
        $this->anrObjectCategoryTable = $anrObjectCategoryTable;
        $this->instanceTable = $instanceTable;
        $this->objectObjectService = $objectObjectService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getListSpecific(FormattedInputParams $formattedInputParams): array
    {
        if (!$this->areAnrObjectsValid($formattedInputParams)) {
            return [];
        }
        $this->prepareObjectsListFilter($formattedInputParams);

        /** @var MonarcObject $objects */
        $objects = $this->monarcObjectTable->findByParams($formattedInputParams);

        $result = [];
        foreach ($objects as $object) {
            $result[] = $this->getPreparedObjectData($object);
        }

        return $result;
    }

    public function getCount(FormattedInputParams $formattedInputParams): int
    {
        if (!$this->areAnrObjectsValid($formattedInputParams)) {
            return 0;
        }
        $this->prepareObjectsListFilter($formattedInputParams);

        return $this->monarcObjectTable->countByParams($formattedInputParams);
    }

    public function getObjectData(string $uuid, FormattedInputParams $formattedInputParams)
    {
        /** @var ObjectSuperClass $object */
        $object = $this->monarcObjectTable->findByUuid($uuid);

        $objectData = $this->getPreparedObjectData($object);

        $filteredData = $formattedInputParams->getFilter();

        /* Object edit dialog scenario. */
        if ($this->isEditObjectMode($filteredData)) {
            return $objectData;
        }

        $objectData['children'] = $this->getChildrenTreeList($object);
        $objectData['risks'] = $this->getRisks($object);
        $objectData['oprisks'] = $this->getRisksOp($object);
        $objectData['parents'] = $this->getDirectParents($object);

        if ($this->isAnrObjectMode($filteredData)) {
            /* Anr/model object in a library scenario.*/
            $anr = $this->getValidatedAnr($filteredData, $object);

            $instances = $this->instanceTable->findByAnrAndObject($anr, $object);

            $objectData['replicas'] = [];
            foreach ($instances as $instance) {
                $instanceHierarchy = $instance->getHierarchyArray();

                $names = [
                    'name1' => $anr->getLabelByLanguageIndex(1),
                    'name2' => $anr->getLabelByLanguageIndex(2),
                    'name3' => $anr->getLabelByLanguageIndex(3),
                    'name4' => $anr->getLabelByLanguageIndex(4),
                ];
                foreach ($instanceHierarchy as $instanceData) {
                    $names['name1'] .= ' > ' . $instanceData['name1'];
                    $names['name2'] .= ' > ' . $instanceData['name2'];
                    $names['name3'] .= ' > ' . $instanceData['name3'];
                    $names['name4'] .= ' > ' . $instanceData['name4'];
                }
                $names['id'] = $instance->getId();
                $objectData['replicas'][] = $names;
            }

            return $objectData;
        }

        /* Knowledge base scenario. */
        $anrIds = [];
        foreach ($object->getAnrs() as $item) {
            $anrIds[] = $item->getId();
        }

        $objectData['replicas'] = [];
        if (!empty($anrIds)) {
            $models = $this->modelTable->findByAnrIds($anrIds);

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

            $objectData['replicas'] = $modelsData;
        }

        return $objectData;
    }

    public function getLibraryTreeStructure(AnrSuperClass $anr): array
    {
        $result = [];
        foreach ($anr->getAnrObjectCategories() as $anrObjectCategory) {
            $objectCategory = $anrObjectCategory->getCategory();
            $objectsData = [];
            foreach ($objectCategory->getObjects() as $object) {
                $objectsData[] = $this->getPreparedObjectData($object, true);
            }
            if (!empty($objectsData)) {
                $result['categories'][] = $this->getPreparedObjectCategoryData($objectCategory, $objectsData);
            }
        }

        /* Places uncategorized objects. */
        $objectsData = [];
        foreach ($anr->getObjects() as $object) {
            if ($object->getCategory() === null) {
                $objectsData[] = $this->getPreparedObjectData($object, true);
            }
        }
        if (!empty($objectsData)) {
            $result['categories'][-1] = [
                'id' => -1,
                'label1' => 'Sans catégorie',
                'label2' => 'Uncategorized',
                'label3' => 'Keine Kategorie',
                'label4' => 'Geen categorie',
                'position' => -1,
                'child' => [],
                'objects' => $objectsData,
            ];
        }

        return $result;
    }

    // TODO: stopped here ...

    public function create($data, $saveInDb = true)
    {
        $context = AbstractEntity::BACK_OFFICE;

        $monarcObject = new MonarcObject();

        //in FO, all objects are generics
//        if ($context == AbstractEntity::FRONT_OFFICE) {
//            $data['mode'] = MonarcObject::MODE_GENERIC;
//        }

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
            /** @var Asset $asset */
            $asset = $assetTable->findByUuid($data['asset']);
            if (!$asset->isPrimary()) {
                unset($data['rolfTag']);
                $setRolfTagNull = true;
            }
        }

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
            $monarcObject->source = $this->monarcObjectTable->getEntity($data['source']);
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

        if ($context === AbstractEntity::BACK_OFFICE) {
            //create object type bdc
            $id = $this->monarcObjectTable->save($monarcObject);

            //attach object to anr
            if ($model !== null) {
                $id = $this->attachObjectToAnr($monarcObject, $model->getAnr()->getId());
            }
        } elseif ($anr) {
            $id = $this->attachObjectToAnr($monarcObject, $anr, null, null, $context);
        } else {
            //create object type anr
            $id = $this->monarcObjectTable->save($monarcObject);
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
            /** @var Asset $asset */
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

        /** @var Table\MonarcObjectTable $monarcObjectTable */
        $monarcObjectTable = $this->get('table');
        $monarcObjectTable->save($monarcObject);

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

        $rolfTagId = ($monarcObject->getRolfTag()) ? $monarcObject->getRolfTag()->getId() : null;

        $monarcObject->exchangeArray($data, true);

        if ($monarcObject->getRolfTag()) {
            $newRolfTagId = (is_int($monarcObject->getRolfTag())) ? $monarcObject->getRolfTag() : $monarcObject->getRolfTag()->getId();
            $newRolfTag = $rolfTagId === $newRolfTagId ? false : $monarcObject->getRolfTag();
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
        try {
            $objectsObjects = $this->objectObjectTable->getEntityByFields(['parent' => $data['id']]);
        } catch (QueryException | MappingException $e) {
            $objectsObjects = $this->objectObjectTable->getEntityByFields([
                'anr' => $data['anr'],
                'parent' => [
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
                $newObjectObject->setParent($this->get('table')->getEntity($id));
                $newObjectObject->setChild($this->get('table')->getEntity($childId));
            } catch (QueryException | MappingException $e) {
                $newObjectObject->setParent($this->get('table')->getEntity(['anr' => $data['anr'], 'uuid' => $id]));
                $newObjectObject->setChild($this->get('table')->getEntity(['anr' => $data['anr'], 'uuid' => $childId]));
            }
            $this->objectObjectTable->save($newObjectObject);
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
            $objectCategory = $this->objectCategoryTable->getEntity($object->category->id);
            $objectRootCategoryId = ($objectCategory->root) ? $objectCategory->root->id : $objectCategory->id;

            //add root category to anr
            $anrObjectCategories = $this->anrObjectCategoryTable->getEntityByFields([
                'anr' => $anrId,
                'category' => $objectRootCategoryId,
            ]);
            if (!count($anrObjectCategories)) {
                $class = $this->get('anrObjectCategoryEntity');
                $anrObjectCategory = new $class();
                $anrObjectCategory->setDbAdapter($this->anrObjectCategoryTable->getDb());
                $anrObjectCategory->exchangeArray([
                    'anr' => $anr,
                    'category' => (($object->category->root) ? $object->category->root : $object->category),
                    'implicitPosition' => 2,
                ]);
                $this->anrObjectCategoryTable->save($anrObjectCategory);
            }
        }

        //children
        $children = $this->objectObjectService->getChildren($object->getUuid(), $anrId);
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
    public function detachObjectFromAnr($objectId, $anrId, $context = AbstractEntity::BACK_OFFICE)
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
                'anr' => $context === AbstractEntity::BACK_OFFICE ? 'null' : $anrId,
                'child' => $objectId,
            ]);
        } catch (QueryException | MappingException $e) {
            $links = $objectObjectTable->getEntityByFields([
                'anr' => $context === AbstractEntity::BACK_OFFICE ? 'null' : $anrId,
                'child' => ['uuid' => $objectId, 'anr' => $anrId],
            ]);
        }
        /** @var InstanceTable $instanceTable */
        $instanceTable = $this->get('instanceTable');
        foreach ($links as $link) {
            //retrieve instance with link parent object
            $fatherInstancesIds = [];
            try {
                $fatherInstances = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => $link->getParent()->getUuid(),
                ]);
            } catch (QueryException | MappingException $e) {
                $fatherInstances = $instanceTable->getEntityByFields([
                    'anr' => $anrId,
                    'object' => [
                        'anr' => $anrId,
                        'uuid' => $link->getParent()->getUuid(),
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
            $anrObjectCategories = $this->anrObjectCategoryTable->getEntityByFields([
                'anr' => $anrId,
                'category' => $objectRootCategory->getId(),
            ]);
            $i = 1;
            $nbAnrObjectCategories = count($anrObjectCategories);
            foreach ($anrObjectCategories as $anrObjectCategory) {
                $this->anrObjectCategoryTable->delete($anrObjectCategory->id, ($i == $nbAnrObjectCategories));
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

    public function getParentsInAnr(AnrSuperClass $anr, string $uuid)
    {
        /** @var MonarcObject $object */
        $object = $this->monarcObjectTable->findByUuid($uuid);

        if (!$object->hasAnr($anr)) {
            throw new Exception(sprintf('The object is not linked to the anr ID "%d"', $anr->getId()), 412);
        }

        $directParents = [];
        foreach ($object->getParentsLinks() as $parentLink) {
            if ($parentLink->getParent()->hasAnr($anr)) {
                $directParents = [
                    'uuid' => $parentLink->getParent()->getUuid(),
                    'linkid' => $parentLink->getId(),
                    'label1' => $parentLink->getParent()->getLabel(1),
                    'label2' => $parentLink->getParent()->getLabel(2),
                    'label3' => $parentLink->getParent()->getLabel(3),
                    'label4' => $parentLink->getParent()->getLabel(4),
                    'name1' => $parentLink->getParent()->getName(1),
                    'name2' => $parentLink->getParent()->getName(2),
                    'name3' => $parentLink->getParent()->getName(3),
                    'name4' => $parentLink->getParent()->getName(4),
                ];
            }
        }

        return $directParents;
    }

    public function getPreparedObjectData(ObjectSuperClass $object, bool $objectOnly = false): array
    {
        $result = [
            'uuid' => $object->getUuid(),
            'label1' => $object->getLabel(1),
            'label2' => $object->getLabel(2),
            'label3' => $object->getLabel(3),
            'label4' => $object->getLabel(4),
            'name1' => $object->getName(1),
            'name2' => $object->getName(2),
            'name3' => $object->getName(3),
            'name4' => $object->getName(4),
            'mode' => $object->getMode(),
            'scope' => $object->getScope(),
            'position' => $object->getPosition(),
        ];

        if (!$objectOnly) {
            $result['category'] = $object->getCategory() !== null
                ? [
                    'id' => $object->getCategory()->getId(),
                    'label1' => $object->getCategory()->getLabel(1),
                    'label2' => $object->getCategory()->getLabel(2),
                    'label3' => $object->getCategory()->getLabel(3),
                    'label4' => $object->getCategory()->getLabel(4),
                    'position' => $object->getCategory()->getPosition(),
                ]
                : [
                    'id' => -1,
                    'label1' => 'Sans catégorie',
                    'label2' => 'Uncategorized',
                    'label3' => 'Keine Kategorie',
                    'label4' => 'Geen categorie',
                    'position' => -1,
                ];
            $result['asset'] = [
                'uuid' => $object->getAsset()->getUuid(),
                'code' => $object->getAsset()->getCode(),
                'label1' => $object->getAsset()->getLabel(1),
                'label2' => $object->getAsset()->getLabel(2),
                'label3' => $object->getAsset()->getLabel(3),
                'label4' => $object->getAsset()->getLabel(4),
            ];
            $result['rolfTag'] = $object->getRolfTag() === null ? null : [
                'id' => $object->getRolfTag()->getId(),
                'code' => $object->getRolfTag()->getCode(),
                'label1' => $object->getRolfTag()->getLabel(1),
                'label2' => $object->getRolfTag()->getLabel(2),
                'label3' => $object->getRolfTag()->getLabel(3),
                'label4' => $object->getRolfTag()->getLabel(4),
            ];
        }

        return $result;
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

    private function getCategoriesWithObjectsChildrenTreeList(ObjectCategorySuperClass $objectCategory): array
    {
        $result = [];
        foreach ($objectCategory->getChildren() as $category) {
            $objectsData = [];
            foreach ($objectCategory->getObjects() as $object) {
                $objectsData[] = $this->getPreparedObjectData($object, true);
            }
            if (!empty($objectsData)) {
                $result[] = $this->getPreparedObjectCategoryData($category, $objectsData);
            }
        }

        return $result;
    }

    private function getPreparedObjectCategoryData(ObjectCategorySuperClass $category, array $objectsData): array
    {
        return [
            'id' => $category->getId(),
            'label1' => $category->getLabel(1),
            'label2' => $category->getLabel(2),
            'label3' => $category->getLabel(3),
            'label4' => $category->getLabel(4),
            'position' => $category->getPosition(),
            'child' => $category->getChildren()->isEmpty()
                ? []
                : $this->getCategoriesWithObjectsChildrenTreeList($category),
            'objects' => $objectsData,
        ];
    }

    private function getChildrenTreeList(ObjectSuperClass $object): array
    {
        $result = [];
        foreach ($object->getChildrenLinks() as $childLinkObject) {
            $result[] = [
                'component_link_id' => $childLinkObject->getId(),
                'label1' => $childLinkObject->getChild()->getLabel(1),
                'label2' => $childLinkObject->getChild()->getLabel(2),
                'label3' => $childLinkObject->getChild()->getLabel(3),
                'label4' => $childLinkObject->getChild()->getLabel(4),
                'name1' => $childLinkObject->getChild()->getName(1),
                'name2' => $childLinkObject->getChild()->getName(2),
                'name3' => $childLinkObject->getChild()->getName(3),
                'name4' => $childLinkObject->getChild()->getName(4),
                'mode' => $childLinkObject->getChild()->getMode(),
                'scope' => $childLinkObject->getChild()->getScope(),
                'children' => $childLinkObject->getChild()->getChildren()->isEmpty()
                    ? []
                    : $this->getChildrenTreeList($childLinkObject->getChild()),
            ];
        }

        return $result;
    }

    private function getRisks(ObjectSuperClass $object): array
    {
        $risks = [];
        foreach ($object->getAsset()->getAmvs() as $amv) {
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

    private function getRisksOp(ObjectSuperClass $object): array
    {
        $riskOps = [];
        if ($object->getRolfTag() !== null && $object->getAsset()->isPrimary()) {
            foreach ($object->getRolfTag()->getRisks() as $rolfRisk) {
                $riskOps[] = [
                    'label1' => $rolfRisk->getLabel(1),
                    'label2' => $rolfRisk->getLabel(2),
                    'label3' => $rolfRisk->getLabel(3),
                    'label4' => $rolfRisk->getLabel(4),
                    'description1' => $rolfRisk->getDescription(1),
                    'description2' => $rolfRisk->getDescription(2),
                    'description3' => $rolfRisk->getDescription(3),
                    'description4' => $rolfRisk->getDescription(4),
                ];
            }
        }

        return $riskOps;
    }
    // TODO: perhaps we need to reset position.

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

        // Remove the relation with Anr (AnrObjectCategory) if exists.
        // TODO:  We can do now: if ($objectCategory->hasAnrLink($anr)) { $objectCategory->removeAnrLink(); }
        $anrObjectCategory = $this->anrObjectCategoryTable->findOneByAnrAndObjectCategory($anr, $objectCategory);
        if ($anrObjectCategory !== null) {
            $this->anrObjectCategoryTable->delete($anrObjectCategory->getId());
        }
    }

    /** TODO: Not just a refactoring, but position update also. */
    private function linkCategoryWithAnrIfNotLinked(
        ObjectCategorySuperClass $objectCategory,
        AnrSuperClass $anr
    ): void {
        $anrObjectCategory = $this->anrObjectCategoryTable->findOneByAnrAndObjectCategory($anr, $objectCategory);
        if ($anrObjectCategory !== null) {
            return;
        }

        /** @var AnrObjectCategory $anrObjectCategory */
        $anrObjectCategory = $this->get('anrObjectCategoryEntity');
        $anrObjectCategory = new $anrObjectCategory;
        $anrObjectCategory->setAnr($anr)->setCategory($objectCategory);

        $anrObjectCategory->setDbAdapter($this->anrObjectCategoryTable->getDb());
        $anrObjectCategory->exchangeArray(['implicitPosition' => 2]);

        $this->anrObjectCategoryTable->save($anrObjectCategory);
    }

    private function getDirectParents(ObjectSuperClass $object): array
    {
        $parents = [];
        foreach ($object->getParents() as $parentObject) {
            $parents[] = [
                'name1' => $parentObject->getName(1),
                'name2' => $parentObject->getName(2),
                'name3' => $parentObject->getName(3),
                'name4' => $parentObject->getName(4),
                'label1' => $parentObject->getLabel(1),
                'label2' => $parentObject->getLabel(2),
                'label3' => $parentObject->getLabel(3),
                'label4' => $parentObject->getLabel(4),
            ];
        }

        return $parents;
    }

    private function isEditObjectMode(array $filteredData): bool
    {
        return isset($filteredData['mode']) && $filteredData['mode'] === self::MODE_OBJECT_EDIT;
    }

    private function isAnrObjectMode(array $filteredData): bool
    {
        return isset($filteredData['mode']) && $filteredData['mode'] === self::MODE_ANR;
    }

    private function getValidatedAnr(array $filteredData, ObjectSuperClass $object): AnrSuperClass
    {
        $anr = $filteredData['anr'] ?? null;
        if (!$anr instanceof AnrSuperClass) {
            throw new \Exception('Anr parameter has to be passed missing for the mode "anr".', 412);
        }
        if (!$object->hasAnr($anr)) {
            throw new Exception(sprintf('The object is not linked to the anr ID "%d"', $anr->getId()), 412);
        }

        return $anr;
    }

    private function areAnrObjectsValid(FormattedInputParams $formattedInputParams): bool
    {
        $anrFilter = $formattedInputParams->getFilterFor('anr');
        if (empty($anrFilter['value'])) {
            return true;
        }

        /** @var AnrSuperClass $anr */
        $anr = $anrFilter['value'];

        return !$anr->getObjects()->isEmpty();
    }

    private function prepareObjectsListFilter(FormattedInputParams $formattedInputParams): void
    {
        $this->prepareCategoryFilter($formattedInputParams);
        $this->prepareModelFilter($formattedInputParams);
        $this->prepareAnrFilter($formattedInputParams);
    }

    private function prepareModelFilter(FormattedInputParams $formattedInputParams): void
    {
        $modelFilter = $formattedInputParams->getFilterFor('model');
        if (!empty($modelFilter['value'])) {
            /** @var Model $model */
            $model = $this->modelTable->findById($modelFilter['value']);
            if ($model->isGeneric()) {
                $formattedInputParams->setFilterValueFor('mode', ObjectSuperClass::MODE_GENERIC);
            } else {
                $assetsFilter = [];
                foreach ($model->getAssets() as $asset) {
                    if ($asset->isModeSpecific()) {
                        $assetsFilter[$asset->getUuid()] = $asset->getUuid();
                    }
                }
                if (!$model->isRegulator()) {
                    $assets = $this->assetTable->findByMode(AssetSuperClass::MODE_GENERIC);
                    foreach ($assets as $asset) {
                        $assetsFilter[$asset->getUuid()] = $asset->getUuid();
                    }
                }
                $formattedInputParams->setFilterValueFor('asset', array_values($assetsFilter));
            }

            $objectsToFilterOut = [];
            foreach ($model->getAnr()->getObjects() as $object) {
                $objectsToFilterOut[$object->getUuid()] = $object->getUuid();
            }
            if (!empty($objectsToFilterOut)) {
                $formattedInputParams->setFilterFor('uuid', [
                    'value' => array_values($objectsToFilterOut),
                    'operator' => Comparison::NIN,
                ]);
            }
        }
    }

    private function prepareAnrFilter(FormattedInputParams $formattedInputParams): void
    {
        $anrFilter = $formattedInputParams->getFilterFor('anr');
        if (!empty($anrFilter['value'])) {
            /** @var AnrSuperClass $anr */
            $anr = $anrFilter['value'];
            $objectsUuids = [];
            foreach ($anr->getObjects() as $object) {
                $objectsUuids[] = $object->getUuid();
            }

            $formattedInputParams->setFilterValueFor('uuid', $objectsUuids);
        }
    }

    private function prepareCategoryFilter(FormattedInputParams $formattedInputParams): void
    {
        $lockFilter = $formattedInputParams->getFilterFor('lock');
        $categoryFilter = $formattedInputParams->getFilterFor('category');
        if (empty($lockFilter['value']) && !empty($categoryFilter['value'])) {
            /** @var ObjectCategory $objectCategory */
            $objectCategory = $this->objectCategoryTable->findById($categoryFilter['value']);
            $formattedInputParams->setFilterValueFor(
                'category',
                array_merge($categoryFilter['value'], $objectCategory->getRecursiveChildrenIds())
            );
        }
    }
}
