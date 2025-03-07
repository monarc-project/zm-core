<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Entity;
use Monarc\Core\Table;

class AssetService
{
    private Table\AssetTable $assetTable;

    private Table\MonarcObjectTable $monarcObjectTable;

    private Table\ModelTable $modelTable;

    private AmvService $amvService;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        Table\AssetTable $assetTable,
        Table\MonarcObjectTable $monarcObjectTable,
        Table\ModelTable $modelTable,
        AmvService $amvService,
        ConnectedUserService $connectedUserService
    ) {
        $this->assetTable = $assetTable;
        $this->monarcObjectTable = $monarcObjectTable;
        $this->modelTable = $modelTable;
        $this->amvService = $amvService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
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
        /** @var Entity\Asset $asset */
        $asset = $this->assetTable->findByUuid($uuid);

        return $this->prepareAssetDataResult($asset);
    }

    public function create(array $data, bool $saveInDb = true): Entity\Asset
    {
        /** @var Entity\Asset $asset */
        $asset = (new Entity\Asset())
            ->setCode($data['code'])
            ->setLabels($data)
            ->setDescriptions($data)
            ->setType($data['type'])
            ->setCreator($this->connectedUser->getEmail());
        if (isset($data['mode'])) {
            $asset->setMode((int)$data['mode']);
        }
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

    public function createList(array $data): array
    {
        $createdUuids = [];
        foreach ($data as $row) {
            $createdUuids[] = $this->create($row, false)->getUuid();
        }
        $this->assetTable->flush();

        return $createdUuids;
    }

    public function getOrCreateAsset(array $assetData, bool $saveInDb = false): Entity\Asset
    {
        if (!empty($assetData['uuid'])) {
            try {
                /** @var Entity\Asset $asset */
                $asset = $this->assetTable->findByUuid($assetData['uuid']);

                return $asset;
            } catch (EntityNotFoundException) {
            }
        }

        return $this->create($assetData, $saveInDb);
    }

    public function update(string $uuid, array $data)
    {
        /** @var Entity\Asset $asset */
        $asset = $this->assetTable->findByUuid($uuid);

        $asset->setCode($data['code'])
            ->setLabels($data)
            ->setDescriptions($data)
            ->setType((int)$data['type'])
            ->setStatus($data['status'] ?? Entity\AssetSuperClass::STATUS_ACTIVE)
            ->setUpdater($this->connectedUser->getEmail());
        if (isset($data['mode'])) {
            $asset->setMode((int)$data['mode']);
        }

        $follow = !empty($data['follow']);
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
            /** @var Entity\Model[] $modelsObj */
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
        /** @var Entity\Asset $asset */
        $asset = $this->assetTable->findByUuid($uuid);

        $asset->setStatus((int)$data['status'])
            ->setUpdater($this->connectedUser->getEmail());

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

    private function prepareAssetDataResult(Entity\Asset $asset): array
    {
        $models = [];
        foreach ($asset->getModels() as $model) {
            $models[] = [
                'id' => $model->getId(),
            ];
        }

        return array_merge($asset->getLabels(), $asset->getDescriptions(), [
            'uuid' => $asset->getUuid(),
            'code' => $asset->getCode(),
            'type' => $asset->getType(),
            'status' => $asset->getStatus(),
            'mode' => $asset->getMode(),
            'models' => $models,
        ]);
    }

    private function validateAssetObjects(Entity\Asset $asset): void
    {
        if ($asset->hasObjects()) {
            if (!$asset->hasModels()) {
                /*
                 * Check if the asset is compliant with generic/specific model, when they are used as parents,
                 * not already used in models.
                 */
                foreach ($asset->getObjects() as $monarcObject) {
                    foreach ($monarcObject->getChildren() as $childObject) {
                        foreach ($asset->getModels() as $model) {
                            $model->validateObjectAcceptance($childObject);
                        }
                    }
                }
            }

            /*
             * Check if the asset is compliant with generic/specific model, when they are used as children,
             * not already used in their models.
             */
            foreach ($asset->getObjects() as $monarcObject) {
                foreach ($monarcObject->getParents() as $parentObject) {
                    /** @var Entity\Asset $objectAsset */
                    $objectAsset = $parentObject->getAsset();
                    foreach ($objectAsset->getModels() as $model) {
                        $model->validateObjectAcceptance($parentObject, $asset);
                    }
                }
            }
        }
    }
}
