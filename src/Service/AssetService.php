<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\AssetTable;
use Monarc\Core\Model\Table\ObjectObjectTable;
use Monarc\Core\Table\ModelTable;

/**
 * Asset Service
 *
 * Class AssetService
 * @package Monarc\Core\Service
 */
class AssetService extends AbstractService
{
    protected $anrTable;
    protected $modelTable;
    protected $amvService;
    protected $modelService;
    protected $MonarcObjectTable;
    protected $objectObjectTable;
    protected $assetExportService;
    protected $dependencies = ['anr', 'model[s]()'];
    protected $forbiddenFields = ['anr'];
    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4',
        'description1', 'description2', 'description3', 'description4',
        'code',
    ];

    public function create($data, $saveInDb = true)
    {
        /** @var AssetTable $assetTable */
        $assetTable = $this->get('table');
        $entityClass = $assetTable->getEntityClass();

        /** @var Asset $asset */
        $asset = new $entityClass();
        $asset->setLanguage($this->getLanguage());
        $asset->setDbAdapter($assetTable->getDb());

        if (!empty($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->findById($data['anr']);

            $asset->setAnr($anr);
        }

        $asset->exchangeArray($data);
        $this->setDependencies($asset, $this->dependencies);

        $asset->setCreator($this->getConnectedUser()->getEmail());

        return $assetTable->save($asset, $saveInDb);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $this->filterPatchFields($data);

        /** @var AssetTable $assetTable */
        $assetTable = $this->get('table');
        /** @var Asset $asset */
        $asset = $assetTable->findByUuid($id);
        $asset->setDbAdapter($assetTable->getDb());
        $asset->setLanguage($this->getLanguage());

        if ($data['mode'] === Asset::MODE_GENERIC && $asset->isModeSpecific()) {
            unset($data['models']);
        }

        $models = $data['models'] ?? [];
        $follow = $data['follow'] ?? null;
        unset($data['models'], $data['follow'], $data['uuid']);

        $asset->exchangeArray($data);
        // TODO: we don't need to do this if set properly before -> change and drop drop.
        if ($asset->getModels()) {
            $asset->getModels()->initialize();
        }

        /** @var AmvService $amvService */
        $amvService = $this->get('amvService');
        if (!$amvService->checkAmvIntegrityLevel($models, $asset, null, null, $follow)) {
            throw new Exception('Integrity AMV links violation', 412);
        }

        if ($asset->isModeSpecific()) {
            $associateObjects = $this->get('MonarcObjectTable')->getGenericByAssetId($asset->getUuid());
            if (\count($associateObjects)) {
                throw new Exception('Integrity AMV links violation', 412);
            }
        }

        if (!$amvService->checkModelsInstantiation($asset, $models)) {
            throw new Exception('This type of asset is used in a model that is no longer part of the list', 412);
        }

        /** @var ModelTable $modelTable */
        $modelTable = $this->get('modelTable');
        $asset->unlinkModels();
        if ($asset->isModeSpecific()) {
            if (!empty($models)) {
                /** @var Model[] $modelsObj */
                $modelsObj = $modelTable->findByIds($models);
                foreach ($modelsObj as $model) {
                    $asset->addModel($model);
                }
            }
            if ($follow) {
                $amvService->enforceAmvToFollow($asset->getModels(), $asset);
            }
        }

        $objects = $this->get('MonarcObjectTable')->getEntityByFields(['asset' => $asset->getId()]);
        if (!empty($objects)) {
            $oids = [];
            foreach ($objects as $o) {
                $oids[$o->id] = $o->id;
            }
            if (!empty($asset->getModels())) {
                //We need to check if the asset is compliant with reg/spec model when they are used as fathers
                //not already used in models
                /** @var ObjectObjectTable $objectObjectTable */
                $objectObjectTable = $this->get('objectObjectTable');
                $olinks = $objectObjectTable->getEntityByFields(['father' => $oids]);
                if (!empty($olinks)) {
                    foreach ($olinks as $ol) {
                        foreach ($asset->getModels() as $model) {
                            $model->validateObjectAcceptance($ol->getChild());
                        }
                    }
                }
            }
            //We need to check if the asset is compliant with reg/spec model when they are used as children
            //of objects not already used in models. This code is pretty similar to the previous one

            //we need the parents of theses objects
            /** @var ObjectObjectTable $objectObjectTable */
            $objectObjectTable = $this->get('objectObjectTable');
            $olinks = $objectObjectTable->getEntityByFields(['child' => $oids]);
            if (!empty($olinks)) {
                foreach ($olinks as $ol) {
                    /** @var Model[] $models */
                    $models = $ol->getFather()->getAsset()->getModels();
                    foreach ($models as $model) {
                        $model->validateObjectAcceptance($ol, $asset);
                    }
                }
            }
        }

        $asset->setUpdater($this->getConnectedUser()->getEmail());

        $assetTable->saveEntity($asset);

        return $asset->getUuid();
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);

        parent::patch($id, $data);
    }

    /**
     * Exports the asset, optionaly encrypted
     * @param array $data The 'id' and 'password' for export
     * @return string The file, optionaly encrypted
     * @throws \Exception if the asset is invalid
     */
    public function exportAsset(&$data)
    {
        if (empty($data['id'])) {
            throw new Exception('Asset to export is required', 412);
        }
        $filename = "";

        $exportedAsset = json_encode($this->get('assetExportService')->generateExportArray($data['id'], $filename));
        $data['filename'] = $filename;

        if (! empty($data['password'])) {
            $exportedAsset = $this->encrypt($exportedAsset, $data['password']);
        }

        return $exportedAsset;
    }
}
