<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Entity\Anr;
use Monarc\Core\Entity\Model;
use Monarc\Core\Entity\ObjectSuperClass;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Table\AnrTable;
use Monarc\Core\Table\ModelTable;

class ModelService
{
    private UserSuperClass $connectedUser;

    public function __construct(
        private ModelTable $modelTable,
        private AnrTable $anrTable,
        private AnrService $anrService,
        private AnrInstanceMetadataFieldService $anrInstanceMetadataFieldService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];

        /** @var Model[] $models */
        $models = $this->modelTable->findByParams($params);
        foreach ($models as $model) {
            $result[] = $this->prepareModelDataResult($model);
        }

        return $result;
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->modelTable->countByParams($params);
    }

    public function create(array $data): int
    {
        $anr = $this->anrService->create([
            'label1' => $data['label1'],
            'label2' => $data['label2'],
            'label3' => $data['label3'],
            'label4' => $data['label4'],
            'description1' => $data['description1'],
            'description2' => $data['description2'],
            'description3' => $data['description3'],
            'description4' => $data['description4'],
        ]);

        $model = (new Model())->setAnr($anr);
        $model = $this->setModelData($model, $data)
            ->setCreator($this->connectedUser->getEmail());

        if (!empty($data['metadataFields'])) {
            foreach ($data['metadataFields'] as $metadataFieldData) {
                $this->anrInstanceMetadataFieldService->create($anr, ['metadataField' => [$metadataFieldData]], false);
            }
        }

        $this->modelTable->save($model);

        return $model->getId();
    }

    public function getModelData(int $id): array
    {
        /** @var Model $model */
        $model = $this->modelTable->findById($id);

        return $this->prepareModelDataResult($model);
    }

    public function update(int $id, array $data): void
    {
        /** @var Model $model */
        $model = $this->modelTable->findById($id);

        $this->validateBeforeUpdate($model, $data);

        $this->setModelData($model, $data)
            ->setUpdater($this->connectedUser->getEmail());

        $this->modelTable->save($model);
    }

    public function patch(int $id, array $data): void
    {
        /** @var Model $model */
        $model = $this->modelTable->findById($id);

        if (isset($data['status'])) {
            $model->setStatus($data['status']);
        }

        $model->setUpdater($this->connectedUser->getEmail());

        $this->modelTable->save($model);
    }

    public function duplicate(int $modelId): int
    {
        /** @var Model $model */
        $model = $this->modelTable->findById($modelId);

        $newAnr = $this->anrService->duplicate($model->getAnr());

        $labelSuffix = ' (copy from ' . date('m/d/Y at H:i') . ')';
        $newModel = (new Model())
            ->setAnr($newAnr)
            ->setLabels([
                'label1' => $model->getLabel(1) . $labelSuffix,
                'label2' => $model->getLabel(2) . $labelSuffix,
                'label3' => $model->getLabel(3) . $labelSuffix,
                'label4' => $model->getLabel(4) . $labelSuffix,
            ])
            ->setDescriptions($model->getDescriptions())
            ->setIsGeneric($model->isGeneric())
            ->setAreScalesUpdatable($model->areScalesUpdatable())
            ->setShowRolfBrut($model->showRolfBrut())
            ->setStatus($model->getStatus())
            ->setCreator($this->connectedUser->getEmail());

        if (!$newModel->isGeneric()) {
            $this->reassignSpecificObjects($model, $newModel);
        }

        $this->modelTable->save($newModel);

        return $newModel->getId();
    }

    public function delete(int $id): void
    {
        /** @var Model $model */
        $model = $this->modelTable->findById($id);
        $model->setStatus(Model::STATUS_DELETED);

        $this->anrTable->remove($model->getAnr(), false);

        $this->modelTable->save($model);
    }

    private function reassignSpecificObjects(Model $fromModel, Model $toModel): void
    {
        foreach ($fromModel->getAssets() as $asset) {
            $toModel->addAsset($asset);
        }
        foreach ($fromModel->getThreats() as $threat) {
            $toModel->addThreat($threat);
        }
        foreach ($fromModel->getVulnerabilities() as $vulnerability) {
            $toModel->addVulnerability($vulnerability);
        }
    }

    /**
     * Verifies the model integrity before update.
     * It's not allowed to change it to generic if there are specific objects linked.
     */
    private function validateBeforeUpdate(Model $model, array $data): void
    {
        /* Attempt to change the model to generic */
        if (!empty($data['isGeneric']) && !$model->isGeneric() && $model->getAnr() !== null) {
            foreach ($model->getAnr()->getObjects() as $object) {
                if ($object->getMode() === ObjectSuperClass::MODE_SPECIFIC) {
                    throw new Exception(
                        'The modification is forbidden. The level of integrity between the model and its objects ' .
                        'will be corrupted.',
                        412
                    );
                }
            }
            foreach ($model->getAssets() as $asset) {
                if ($asset->isModeSpecific()) {
                    throw new Exception(
                        'The modification is forbidden. The level of integrity between the model ' .
                        'and assets will be corrupted.',
                        412
                    );
                }
            }
            foreach ($model->getThreats() as $threat) {
                if ($threat->isModeSpecific()) {
                    throw new Exception(
                        'The modification is forbidden. The level of integrity between the model ' .
                        'and threats will be corrupted.',
                        412
                    );
                }
            }
            foreach ($model->getVulnerabilities() as $vulnerability) {
                if ($vulnerability->isModeSpecific()) {
                    throw new Exception(
                        'The modification is forbidden. The level of integrity between the model ' .
                        'and vulnerabilities will be corrupted.',
                        412
                    );
                }
            }
        }
    }

    private function setModelData(Model $model, array $data): Model
    {
        $model->setLabels($data)
            ->setDescriptions($data);

        if (isset($data['isDefault'])) {
            $model->setIsDefault((bool)$data['isDefault']);
        }
        if (isset($data['isGeneric'])) {
            $model->setIsGeneric((bool)$data['isGeneric']);
        }
        if (isset($data['areScalesUpdatable'])) {
            $model->setAreScalesUpdatable((bool)$data['areScalesUpdatable']);
        }
        if (isset($data['showRolfBrut'])) {
            $model->setShowRolfBrut((bool)$data['showRolfBrut']);
        }

        if (!empty($data['isDefault'])) {
            $this->modelTable->resetCurrentDefault();
        }

        return $model;
    }

    private function prepareModelDataResult(Model $model): array
    {
        /** @var Anr $anr */
        $anr = $model->getAnr();

        return [
            'id' => $model->getId(),
            'anr' => [
                'id' => $anr->getId(),
                'seuil1' => $anr->getSeuil1(),
                'seuil2' => $anr->getSeuil2(),
                'seuilRolf1' => $anr->getSeuilRolf1(),
                'seuilRolf2' => $anr->getSeuilRolf2(),
                'seuilTraitement' => $anr->getSeuilTraitement(),
            ],
            'label1' => $model->getLabel(1),
            'label2' => $model->getLabel(2),
            'label3' => $model->getLabel(3),
            'label4' => $model->getLabel(4),
            'description1' => $model->getDescription(1),
            'description2' => $model->getDescription(2),
            'description3' => $model->getDescription(3),
            'description4' => $model->getDescription(4),
            'isGeneric' => (int)$model->isGeneric(),
            'isDefault' => (int)$model->isDefault(),
            'areScalesUpdatable' => (int)$model->areScalesUpdatable(),
            'showRolfBrut' => (int)$model->showRolfBrut(),
            'status' => $model->getStatus(),
        ];
    }
}
