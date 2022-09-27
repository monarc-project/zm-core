<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Table\ModelTable;

/**
 * Model Service
 *
 * Class ModelService
 *
 * @package Monarc\Core\Service
 */
class ModelService extends AbstractService
{
    protected $dependencies = ['anr'];
    protected $anrService;
    protected $anrTable;
    protected $instanceRiskTable;
    protected $instanceRiskOpTable;
    protected $MonarcObjectTable;
    protected $amvTable;
    protected $clientTable; // only loaded by Monarc\FrontOffice service factory
    protected $forbiddenFields = ['anr'];
    protected $filterColumns = [
        'label1',
        'label2',
        'label3',
        'label4',
        'description1',
        'description2',
        'description3',
        'description4',
    ];

    /**
     * @inheritdoc
     */
    public function getList(
        $page = 1,
        $limit = 25,
        $order = null,
        $filter = null,
        $filterAnd = null,
        $scope = 'BO'
    ) {
        $joinModel = -1;

        if ($scope == 'FO') {
            $filterAnd['isGeneric'] = 1;
            $client = $this->clientTable->findTheClient();

            if ($client) {
                $joinModel = $client->getModels()
                    ->map(
                        function ($obj) {
                            return $obj->getModelId();
                        }
                    )
                    ->getValues();
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

            $models = array_merge(
                $models,
                $this->get('table')->fetchAllFiltered(
                    array_keys($this->get('entity')->getJsonArray()),
                    $page,
                    $limit,
                    $this->parseFrontendOrder($order),
                    $this->parseFrontendFilter($filter, $this->filterColumns),
                    $filterAnd
                )
            );
        }

        return $models;
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
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
        /**
        * @var AnrService $anrService
        */
        $anrService = $this->get('anrService');
        $anrId = $anrService->create($dataAnr);

        $data['anr'] = $anrId;

        /**
        * @var Model $model
        */
        $model = $this->get('entity');

        $model->exchangeArray($data);

        $dependencies
            = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($model, $dependencies);

        // If we reached here, our object is ready to be saved.
        // If we're the new default model, remove the previous one (if any)
        if ($data['isDefault']) {
            $this->resetCurrentDefault();
        }

        $model->setCreator(
            $this->getConnectedUser()->getEmail()
        );

        return $this->get('table')->save($model);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function update($id, $data)
    {
        /**
        * @var Model $model
        */
        $model = $this->get('table')->getEntity($id);
        if (!$model) {
            throw new \Monarc\Core\Exception\Exception('Entity does not exist', 412);
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
            throw new \Monarc\Core\Exception\Exception('Data missing', 412);
        }

        $model->exchangeArray($data);

        $dependencies
            = (property_exists($this, 'dependencies')) ? $this->dependencies : [];
        $this->setDependencies($model, $dependencies);

        $model->setUpdater(
            $this->getConnectedUser()->getEmail()
        );

        return $this->get('table')->save($model);
    }

    /**
     * Verifies the model integrity before updating it
     *
     * @param  Model $model The model to check
     * @param  array $data  The new data
     * @return bool True if it's correct, false otherwise
     * @throws \Exception
     */
    public function verifyBeforeUpdate($model, $data)
    {
        if (isset($data['isRegulator'])
            && isset($data['isGeneric'])
            && $data['isRegulator'] && $data['isGeneric']
        ) {
            throw new \Monarc\Core\Exception\Exception(
                "A regulator model may not be generic",
                412
            );
        }

        $modeObject = null;

        if (isset($data['isRegulator'])
            && $data['isRegulator']
            && !$model->isRegulator
        ) { // change to regulator
            //retrieve assets
            $assetsIds = [];
            foreach ($model->assets as $asset) {
                $assetsIds[] = $asset->getUuid();
            }
            if (!empty($assetsIds)) {
                $amvs = $this->get('amvTable')
                    ->getEntityByFields(['asset' => $assetsIds]);
                foreach ($amvs as $amv) {
                    if ($amv->get('asset')->get('mode') == MonarcObject::MODE_SPECIFIC
                        && $amv->get('threat')->get('mode') == MonarcObject::MODE_GENERIC
                        && $amv->get('vulnerability')->get('mode') == MonarcObject::MODE_GENERIC
                    ) {
                        throw new \Monarc\Core\Exception\Exception(
                            'You can not make this change. '.
                            'The level of integrity between '.
                            'the model and its objects would corrupt',
                            412
                        );
                    }
                }
            }

            $modeObject = MonarcObject::MODE_GENERIC;
        } elseif (isset($data['isGeneric'])
            && $data['isGeneric']
            && !$model->isGeneric
        ) { // change to generic
            $modeObject = MonarcObject::MODE_SPECIFIC;
        }

        if (!is_null($modeObject)) {
            $objects = $model->get('anr')->get('objects');
            if (!empty($objects)) {
                foreach ($objects as $o) {
                    if ($o->get('mode') == $modeObject) {
                        throw new \Monarc\Core\Exception\Exception(
                            'You can not make this change. The level of integrity '.
                            'between the model and its objects would corrupt',
                            412
                        );
                    }
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function patch($id, $data)
    {
        //security
        $this->filterPatchFields($data);
        return parent::patch($id, $data);
    }

    /**
     * Resets the current default model
     */
    protected function resetCurrentDefault()
    {
        $this->get('table')->resetCurrentDefault();
    }

    /**
     * Unset Specific Models from the passed array
     * @param array $data Models array
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
     * Duplicates a model
     * @param int $modelId The model ID to duplicate
     * @return mixed|null The new model entity
     */
    public function duplicate($modelId)
    {
        //retrieve model
        /**
        * @var ModelTable $modelTable
        */
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
        /**
        * @var AnrService $anrService
        */
        $anrService = $this->get('anrService');
        $newAnr = $anrService->duplicate($newModel->anr);

        $newModel->setAnr($newAnr);

        return $modelTable->save($newModel);
    }

    /**
     * @inheritdoc
     */
    public function delete($id)
    {
        $model = $this->get('table')->getEntity($id);
        $anr = $model->get('anr');
        $model->set('anr', null);
        $model->set(
            'status',
            \Monarc\Core\Model\Entity\AbstractEntity::STATUS_DELETED
        );
        $this->get('table')->save($model);

        if ($anr) {
            $this->get('anrTable')->delete($anr->get('id'));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteList($data)
    {
        $nbData = count($data);
        $i = 1;
        foreach ($data as $d) {
            $model = $this->get('table')->getEntity($d);
            $anr = $model->get('anr');
            $model->set('anr', null);
            $model->set(
                'status',
                \Monarc\Core\Model\Entity\AbstractEntity::STATUS_DELETED
            );
            $this->get('table')->save($model, ($i == $nbData));
            $i++;

            if ($anr) {
                $this->get('anrTable')->delete($anr->get('id'));
            }
        }
        return true;
    }
}
