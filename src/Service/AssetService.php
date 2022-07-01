<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Table\MonarcObjectTable;
use Monarc\Core\Model\Table\ObjectObjectTable;
use Monarc\Core\Table;

class AssetService
{
    private Table\AssetTable $assetTable;

    private MonarcObjectTable $monarcObjectTable;

    private ObjectObjectTable $objectObjectTable;

    private Table\ModelTable $modelTable;

    private AmvService $amvService;

    private ConnectedUserService $connectedUserService;

    public function __construct(
        Table\AssetTable $assetTable,
        MonarcObjectTable $monarcObjectTable,
        ObjectObjectTable $objectObjectTable,
        Table\ModelTable $modelTable,
        AmvService $amvService,
        ConnectedUserService $connectedUserService
    ) {
        $this->assetTable = $assetTable;
        $this->monarcObjectTable = $monarcObjectTable;
        $this->objectObjectTable = $objectObjectTable;
        $this->modelTable = $modelTable;
        $this->amvService = $amvService;
        $this->connectedUserService = $connectedUserService;
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];

        /** @var Entity\Asset[] $assets */
        $assets = $this->assetTable->findByParams($params);
        foreach ($assets as $asset) {
            $result[] = $this->prepareAssetDataResult($asset);
        }

        return $result;
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->assetTable->countByParams($params);
    }

    public function getAssetData(string $uuid): array
    {
        $asset = $this->assetTable->findByUuid($uuid);

        return $this->prepareAssetDataResult($asset);
    }

    public function create(array $data, bool $saveInDb = true): Entity\Asset
    {
        $asset = (new Entity\Asset())
            ->setCode($data['code'])
            ->setLabels($data)
            ->setDescriptions($data)
            ->setMode($data['mode'])
            ->setType($data['type'])
            ->setCreator($this->connectedUserService->getConnectedUser()->getEmail());
        if (isset($data['status'])) {
            $asset->setStatus($data['status']);
        }

        if (!empty($data['models']) && $asset->isModeSpecific()) {
            /** @var Entity\Model[] $models */
            $models = $this->modelTable->findByIds($data['models']);
            foreach ($models as $model) {
                $asset->addModel($model);
            }
        }

        $this->assetTable->save($asset, $saveInDb);

        return $asset;
    }

    public function update(string $uuid, array $data)
    {
        $asset = $this->assetTable->findByUuid($uuid);

        $asset->setCode($data['code'])
            ->setLabels($data)
            ->setDescriptions($data)
            ->setType((int)$data['type'])
            ->setStatus($data['status'] ?? Entity\AssetSuperClass::STATUS_ACTIVE)
            ->setUpdater($this->connectedUserService->getConnectedUser()->getEmail());
        if (isset($data['mode'])) {
            $asset->setMode((int)$data['mode']);
        }

        $follow = isset($data['follow']) && (bool)$data['follow'];
        $modelsIds = $asset->isModeSpecific() && !empty($data['models'])
            ? $data['models']
            : [];

        if (!$this->amvService->checkAmvIntegrityLevel($modelsIds, $asset, null, null, $follow)) {
            throw new Exception('Integrity AMV links violation', 412);
        }

        if ($asset->isModeSpecific() && $this->monarcObjectTable->hasGenericObjectsWithAsset($asset)) {
            throw new Exception('Integrity AMV links violation', 412);
        }

        if (!$this->amvService->checkModelsInstantiation($asset, $modelsIds)) {
            throw new Exception('This type of asset is used in a model that is no longer part of the list', 412);
        }

        $asset->unlinkModels();
        if (!empty($modelsIds) && $asset->isModeSpecific()) {
            /** @var Model[] $modelsObj */
            $modelsObj = $this->modelTable->findByIds($modelsIds);
            foreach ($modelsObj as $model) {
                $asset->addModel($model);
            }
            if ($follow) {
                $this->amvService->enforceAmvToFollow($asset->getModels(), $asset);
            }
        }

        $this->validateAssetObjects($asset);

        $this->assetTable->save($asset);

        return $asset->getUuid();
    }

    public function patch(string $uuid, array $data)
    {
        $asset = $this->assetTable->findByUuid($uuid);

        $asset->setStatus((int)$data['status'])
            ->setUpdater($this->connectedUserService->getConnectedUser()->getEmail());

        $this->assetTable->save($asset);
    }


    public function delete(string $uuid): void
    {
        $asset = $this->assetTable->findByUuid($uuid);

        $this->assetTable->remove($asset);
    }

    public function deleteList(array $data): void
    {
        $assets = $this->assetTable->findByUuids($data);

        $this->assetTable->removeList($assets);
    }

    public function prepareAssetDataResult(Entity\Asset $asset): array
    {
        $models = [];
        foreach ($asset->getModels() as $model) {
            $models[] = [
                'id' => $model->getId(),
            ];
        }

        return [
            'uuid' => $asset->getUuid(),
            'code' => $asset->getCode(),
            'label1' => $asset->getLabel(1),
            'label2' => $asset->getLabel(2),
            'label3' => $asset->getLabel(3),
            'label4' => $asset->getLabel(4),
            'description1' => $asset->getDescription(1),
            'description2' => $asset->getDescription(2),
            'description3' => $asset->getDescription(3),
            'description4' => $asset->getDescription(4),
            'type' => $asset->getType(),
            'status' => $asset->getStatus(),
            'mode' => $asset->getMode(),
            'models' => $models,
        ];
    }

    private function validateAssetObjects(Entity\Asset $asset): void
    {
        $objectUuids = $this->monarcObjectTable->findUuidsByAsset($asset);
        if (!empty($objectUuids)) {
            if (!$asset->getModels()->isEmpty()) {
                /*
                 * Check if the asset is compliant with reg/spec model, when they are used as fathers,
                 * not already used in models.
                 */
                $childrenObjects = $this->objectObjectTable->findChildrenByFatherUuids($objectUuids);
                foreach ($childrenObjects as $childObject) {
                    foreach ($asset->getModels() as $model) {
                        $model->validateObjectAcceptance($childObject);
                    }

                }
            }

            /*
             * Check if the asset is compliant with reg/spec model, when they are used as children,
             * not already used in their models.
             */
            $parentObjects = $this->objectObjectTable->findParentsByChildrenUuids($objectUuids);
            foreach ($parentObjects as $parentObject) {
                $models = $parentObject->getAsset()->getModels();
                foreach ($models as $model) {
                    $model->validateObjectAcceptance($parentObject, $asset);
                }
            }
        }
    }
}
