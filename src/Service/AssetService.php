<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Table\AssetTable;

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

    public function create($data, $last = true)
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

        $asset->setCreator(
            $this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname()
        );

        return $assetTable->save($asset, $last);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $this->filterPatchFields($data);

        /** @var Asset $asset */
        $asset = $this->get('table')->getEntity($id);
        $asset->setDbAdapter($this->get('table')->getDb());
        $asset->setLanguage($this->getLanguage());

        if ($data['mode'] === Asset::MODE_GENERIC && $asset->getMode() === Asset::MODE_SPECIFIC) {
            unset($data['models']);
        }

        $models = $data['models'] ?? [];
        $follow = $data['follow'] ?? null;
        unset($data['models'], $data['follow'], $data['uuid']);

        $asset->exchangeArray($data);
        if ($asset->getModels()) {
            $asset->getModels()->initialize();
        }

        /** @var AmvService $amvService */
        $amvService = $this->get('amvService');
        if (!$amvService->checkAMVIntegrityLevel($models, $asset, null, null, $follow)) {
            throw new Exception('Integrity AMV links violation', 412);
        }

        if ($asset->getMode() === Asset::MODE_SPECIFIC) {
            $associateObjects = $this->get('MonarcObjectTable')->getGenericByAssetId($asset->getUuid());
            if (\count($associateObjects)) {
                throw new Exception('Integrity AMV links violation', 412);
            }
        }

        if (!$this->isSomethingChangedExceptOfLabelsAndDescriptions($asset)
            && !$amvService->checkModelsInstantiation($asset, $models)
        ) {
            throw new Exception('This type of asset is used in a model that is no longer part of the list', 412);
        }

        switch ($asset->get('mode')) {
            case Asset::MODE_SPECIFIC:
                if (empty($models)) {
                    $asset->set('models', []);
                } else {
                    $modelsObj = [];
                    foreach ($models as $mid) {
                        $modelsObj[] = $this->get('modelTable')->getEntity($mid);
                    }
                    $asset->set('models', $modelsObj);
                }
                if ($follow) {
                    $amvService->enforceAMVtoFollow($asset->get('models'), $asset, null, null);
                }
                break;
            case Asset::MODE_GENERIC:
                $asset->set('models', []);
                break;
        }

        $objects = $this->get('MonarcObjectTable')->getEntityByFields(['asset' => $asset->get('id')]);
        if (!empty($objects)) {
            $oids = [];
            foreach ($objects as $o) {
                $oids[$o->id] = $o->id;
            }
            if (!empty($asset->models)) {
                //We need to check if the asset is compliant with reg/spec model when they are used as fathers
                //not already used in models
                $olinks = $this->get('objectObjectTable')->getEntityByFields(['father' => $oids]);
                if (!empty($olinks)) {
                    foreach ($olinks as $ol) {
                        foreach ($asset->models as $m) {
                            $this->get('modelTable')->canAcceptObject($m->id, $ol->child);
                        }
                    }
                }
            }
            //We need to check if the asset is compliant with reg/spec model when they are used as children
            //of objects not already used in models. This code is pretty similar to the previous one

            //we need the parents of theses objects
            $olinks = $this->get('objectObjectTable')->getEntityByFields(['child' => $oids]);
            if (!empty($olinks)) {
                foreach ($olinks as $ol) {
                    if (!empty($ol->father->asset->models)) {
                        foreach ($ol->father->asset->models as $m) {
                            $this->get('modelTable')->canAcceptObject($m->id, $ol->child, null, $asset);
                        }
                    }
                }
            }
        }

        $asset->setUpdater($this->getConnectedUser()->getFirstname() . ' ' . $this->getConnectedUser()->getLastname());

        return $this->get('table')->save($asset);
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

    private function isSomethingChangedExceptOfLabelsAndDescriptions(AssetSuperClass $asset): bool
    {
        /** @var AssetTable $assetTable */
        $assetTable = $this->get('table');
        $unitOfWOrk = $assetTable->getDb()->getEntityManager()->getUnitOfWork();
        $unitOfWOrk->computeChangeSets();
        $assetChangeSet = $unitOfWOrk->getEntityChangeSet($asset);

        return empty(array_diff(
            array_keys($assetChangeSet),
            ['label1', 'label2', 'label3', 'label4', 'description1', 'description2', 'description3', 'description4']
        ));
    }
}
