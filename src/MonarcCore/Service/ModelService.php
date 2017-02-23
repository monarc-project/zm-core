<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Object;
use MonarcCore\Model\Table\ModelTable;

/**
 * Model Service
 *
 * Class ModelService
 * @package MonarcCore\Service
 */
class ModelService extends AbstractService
{
    protected $dependencies = ['anr'];
    protected $anrService;
    protected $anrTable;
    protected $instanceRiskTable;
    protected $instanceRiskOpTable;
    protected $objectTable;
    protected $amvTable;
    protected $clientTable; // only loaded by MonarcFO service factory
    protected $forbiddenFields = ['anr'];
    protected $filterColumns = [
        'label1', 'label2', 'label3', 'label4', 'description1', 'description2', 'description3', 'description4',
    ];

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return mixed
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $scope = 'BO')
    {
        $joinModel = -1;

        if ($scope == 'FO') {
            $filterAnd['isGeneric'] = 1;
            $client = current($this->clientTable->fetchAll());

            if ($client) {
                $joinModel = $client['model_id'];
            }
        }

        $models = $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd
        );

        if ($joinModel > 0) {
            $filterAnd['id'] = $joinModel;
            $filterAnd['isGeneric'] = 0;

            $models = array_merge($models, $this->get('table')->fetchAllFiltered(
                array_keys($this->get('entity')->getJsonArray()),
                $page,
                $limit,
                $this->parseFrontendOrder($order),
                $this->parseFrontendFilter($filter, $this->filterColumns),
                $filterAnd
            ));
        }

        return $models;
    }

    /**
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {
        $entity = $this->get('entity');
        $entity->setLanguage($this->getLanguage());

        //anr
        $dataAnr = [
            'label1' => $data['label1'],
            'label2' => $data['label2'],
            'label3' => $data['label3'],
            'label4' => $data['label4'],
            'description1' => $data['description1'],
            'description2' => $data['description2'],
            'description3' => $data['description3'],
            'description4' => $data['description4'],
        ];
        /** @var AnrService $anrService */
        $anrService = $this->get('anrService');
        $anrId = $anrService->create($dataAnr);

        $data['anr'] = $anrId;

        //model
        $entity->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($entity, $dependencies);

        // If we reached here, our object is ready to be saved.
        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        return $this->get('table')->save($entity);
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getModelWithAnr($id)
    {
        $model = $this->get('table')->get($id);

        $anrModel = $model['anr']->getJsonArray();
        unset($anrModel['__initializer__']);
        unset($anrModel['__cloner__']);
        unset($anrModel['__isInitialized__']);

        $model['anr'] = $anrModel;

        return $model;
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
        $model = $this->get('table')->getEntity($id);
        if (!$model) {
            throw new \MonarcCore\Exception\Exception('Entity does not exist', 412);
        }

        $this->verifyBeforeUpdate($model, $data);

        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        $this->filterPostFields($data, $model);

        $model->setDbAdapter($this->get('table')->getDb());
        $model->setLanguage($this->getLanguage());

        if (empty($data)) {
            throw new \MonarcCore\Exception\Exception('Data missing', 412);
        }

        $model->exchangeArray($data);

        $dependencies = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($model, $dependencies);

        return $this->get('table')->save($model);
    }

    /**
     * Verify Before Update
     *
     * @param $model
     * @param $data
     * @return bool
     * @throws \MonarcCore\Exception\Exception
     */
    public function verifyBeforeUpdate($model, $data)
    {
        if (isset($data['isRegulator']) && isset($data['isGeneric']) &&
            $data['isRegulator'] && $data['isGeneric']
        ) {
            throw new \MonarcCore\Exception\Exception("A regulator model may not be generic", 412);
        }

        $modeObject = null;

        if (isset($data['isRegulator']) && $data['isRegulator'] && !$model->isRegulator) { // change to regulator
            //retrieve assets
            $assetsIds = [];
            foreach ($model->assets as $asset) {
                $assetsIds[] = $asset->id;
            }
            if (!empty($assetsIds)) {
                $amvs = $this->get('amvTable')->getEntityByFields(['asset' => $assetsIds]);
                foreach ($amvs as $amv) {
                    if ($amv->get('asset')->get('mode') == Object::MODE_SPECIFIC && $amv->get('threat')->get('mode') == Object::MODE_GENERIC && $amv->get('vulnerability')->get('mode') == Object::MODE_GENERIC) {
                        throw new \MonarcCore\Exception\Exception('You can not make this change. The level of integrity between the model and its objects would corrupt', 412);
                    }
                }
            }

            $modeObject = Object::MODE_GENERIC;
        } elseif (isset($data['isGeneric']) && $data['isGeneric'] && !$model->isGeneric) { // change to generic
            $modeObject = Object::MODE_SPECIFIC;
        }

        if (!is_null($modeObject)) {
            $objects = $model->get('anr')->get('objects');
            if (!empty($objects)) {
                foreach ($objects as $o) {
                    if ($o->get('mode') == $modeObject) {
                        throw new \MonarcCore\Exception\Exception('You can not make this change. The level of integrity between the model and its objects would corrupt', 412);
                    }
                }
            }
        }

        return true;
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

        return parent::patch($id, $data);
    }

    /**
     * Reset Current Default
     */
    protected function resetCurrentDefault()
    {
        $this->get('table')->resetCurrentDefault();
    }

    /**
     * Unset Specific Models
     *
     * @param $data
     */
    public function unsetSpecificModels(&$data)
    {
        /** @var ModelTable $modelTable */
        $modelTable = $this->get('table');
        foreach ($data['models'] as $key => $modelId) {
            $model = $modelTable->getEntity($modelId);
            if (!$model->isGeneric) {
                unset($data['models'][$key]);
            }
        }
    }

    /**
     * Duplicate
     *
     * @param $modelId
     * @return mixed|null
     */
    public function duplicate($modelId)
    {
        //retrieve model
        /** @var ModelTable $modelTable */
        $modelTable = $this->get('table');
        $model = $modelTable->getEntity($modelId);

        //duplicate model
        $newModel = clone $model;
        $newModel->set('id', null);
        $newModel->set('isDefault', false);

        $suffix = ' (copié le ' . date('m/d/Y à H:i') . ')';
        for ($i = 1; $i <= 4; $i++) {
            $newModel->set('label' . $i, $newModel->get('label' . $i) . $suffix);
        }

        //duplicate anr
        /** @var AnrService $anrService */
        $anrService = $this->get('anrService');
        $newAnr = $anrService->duplicate($newModel->anr);

        $newModel->setAnr($newAnr);

        return $modelTable->save($newModel);
    }

    /**
     * Delete
     *
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        $model = $this->get('table')->getEntity($id);
        $anr = $model->get('anr');
        $model->set('anr', null);
        $model->set('status', \MonarcCore\Model\Entity\AbstractEntity::STATUS_DELETED);
        $this->get('table')->save($model);

        if ($anr) {
            $this->get('anrTable')->delete($anr->get('id'));
        }
        return true;
    }

    /**
     * Delete List
     *
     * @param $data
     * @return bool
     */
    public function deleteList($data)
    {
        $nbData = count($data);
        $i = 1;
        foreach ($data as $d) {
            $model = $this->get('table')->getEntity($d);
            $anr = $model->get('anr');
            $model->set('anr', null);
            $model->set('status', \MonarcCore\Model\Entity\AbstractEntity::STATUS_DELETED);
            $this->get('table')->save($model, ($i == $nbData));
            $i++;

            if ($anr) {
                $this->get('anrTable')->delete($anr->get('id'));
            }
        }
        return true;
    }
}