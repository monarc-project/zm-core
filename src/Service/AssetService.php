<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Table\AnrTable;

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

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {

        $class = $this->get('entity');
        $entity = new $class();
        $entity->setLanguage($this->getLanguage());
        $entity->setDbAdapter($this->get('table')->getDb());

        if (isset($data['anr']) && strlen($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->getEntity($data['anr']);

            if (!$anr) {
                throw new \Monarc\Core\Exception\Exception('This risk analysis does not exist', 412);
            }
            $entity->setAnr($anr);
        }
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        $entity->status = 1;

        return $this->get('table')->save($entity);
    }

    /**
     * @inheritdoc
     */
    public function update($id, $data)
    {
        $this->filterPatchFields($data);

        $entity = $this->get('table')->getEntity($id);
        $entity->setDbAdapter($this->get('table')->getDb());
        $entity->setLanguage($this->getLanguage());

        if (($entity->mode == Asset::MODE_SPECIFIC) && ($data['mode'] == Asset::MODE_GENERIC)) {
            //delete models
            unset($data['models']);
        }

        $models = isset($data['models']) ? $data['models'] : [];
        $follow = isset($data['follow']) ? $data['follow'] : null;
        unset($data['models']);
        unset($data['follow']);

        $entity->exchangeArray($data);
        if ($entity->get('models')) {
            $entity->get('models')->initialize();
        }

        /** @var AmvService $amvService */
        $amvService = $this->get('amvService');
        if (!$amvService->checkAMVIntegrityLevel($models, $entity, null, null, $follow)) {
            throw new \Monarc\Core\Exception\Exception('Integrity AMV links violation', 412);
        }

        if ($entity->mode == Asset::MODE_SPECIFIC) {
            $associateObjects = $this->get('MonarcObjectTable')->getGenericByAssetId($entity->getUuid());
            if (count($associateObjects)) {
                throw new \Monarc\Core\Exception\Exception('Integrity AMV links violation', 412);
            }
        }

        if (!$amvService->checkModelsInstantiation($entity, $models)) {
            throw new \Monarc\Core\Exception\Exception('This type of asset is used in a model that is no longer part of the list', 412);
        }

        switch ($entity->get('mode')) {
            case Asset::MODE_SPECIFIC:
                if (empty($models)) {
                    $entity->set('models', []);
                } else {
                    $modelsObj = [];
                    foreach ($models as $mid) {
                        $modelsObj[] = $this->get('modelTable')->getEntity($mid);
                    }
                    $entity->set('models', $modelsObj);
                }
                if ($follow) {
                    $amvService->enforceAMVtoFollow($entity->get('models'), $entity, null, null);
                }
                break;
            case Asset::MODE_GENERIC:
                $entity->set('models', []);
                break;
        }

        $objects = $this->get('MonarcObjectTable')->getEntityByFields(['asset' => $entity->get('id')]);
        if (!empty($objects)) {
            $oids = [];
            foreach ($objects as $o) {
                $oids[$o->id] = $o->id;
            }
            if (!empty($entity->models)) {
                //We need to check if the asset is compliant with reg/spec model when they are used as fathers
                //not already used in models
                $olinks = $this->get('objectObjectTable')->getEntityByFields(['father' => $oids]);
                if (!empty($olinks)) {
                    foreach ($olinks as $ol) {
                        foreach ($entity->models as $m) {
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
                            $this->get('modelTable')->canAcceptObject($m->id, $ol->child, null, $entity);
                        }
                    }
                }
            }
        }

        return $this->get('table')->save($entity);
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
            throw new \Monarc\Core\Exception\Exception('Asset to export is required', 412);
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
