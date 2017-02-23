<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Asset;
use MonarcCore\Model\Table\AnrTable;

/**
 * Asset Service
 *
 * Class AssetService
 * @package MonarcCore\Service
 */
class AssetService extends AbstractService
{
    protected $anrTable;
    protected $modelTable;
    protected $amvService;
    protected $modelService;
    protected $objectTable;
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
        if (isset($data['anr']) && strlen($data['anr'])) {
            /** @var AnrTable $anrTable */
            $anrTable = $this->get('anrTable');
            $anr = $anrTable->getEntity($data['anr']);

            if (!$anr) {
                throw new \MonarcCore\Exception\Exception('This risk analysis does not exist', 412);
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
            throw new \MonarcCore\Exception\Exception('Integrity AMV links violation', 412);
        }

        if ($entity->mode == Asset::MODE_SPECIFIC) {
            $associateObjects = $this->get('objectTable')->getGenericByAssetId($entity->getId());
            if (count($associateObjects)) {
                throw new \MonarcCore\Exception\Exception('Integrity AMV links violation', 412);
            }
        }

        if (!$amvService->checkModelsInstantiation($entity, $models)) {
            throw new \MonarcCore\Exception\Exception('This type of asset is used in a model that is no longer part of the list', 412);
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

        $objects = $this->get('objectTable')->getEntityByFields(['asset' => $entity->get('id')]);
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

        parent::patch($id, $data);
    }

    /**
     * Export Asset
     *
     * @param $data
     * @return string
     * @throws \MonarcCore\Exception\Exception
     */
    public function exportAsset(&$data)
    {
        if (empty($data['id'])) {
            throw new \MonarcCore\Exception\Exception('Asset to export is required', 412);
        }
        if (empty($data['password'])) {
            $data['password'] = '';
        }
        $filename = "";
        $return = $this->get('assetExportService')->generateExportArray($data['id'], $filename);
        $data['filename'] = $filename;

        return base64_encode($this->encrypt(json_encode($return), $data['password']));
    }
}