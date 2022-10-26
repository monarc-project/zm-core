<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Table\AmvTable;
use Monarc\Core\Table\ModelTable;

class ModelService
{
    private ModelTable $modelTable;

    private AmvTable $amvTable;

    private AnrTable $anrTable;

    private AnrService $anrService;

    private UserSuperClass $connectedUser;

    public function __construct(
        ModelTable $modelTable,
        AmvTable $amvTable,
        AnrTable $anrTable,
        AnrService $anrService,
        ConnectedUserService $connectedUserService
    ) {
        $this->modelTable = $modelTable;
        $this->amvTable = $amvTable;
        $this->anrTable = $anrTable;
        $this->anrService = $anrService;
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
        ], true, true);

        $model = (new Model())->setAnr($anr);
        $model = $this->setModelData($model, $data)
            ->setCreator($this->connectedUser->getEmail());

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
            ->setIsRegulator($model->isRegulator())
            ->setAreScalesUpdatable($model->areScalesUpdatable())
            ->setShowRolfBrut($model->getShowRolfBrut())
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

        $this->anrTable->deleteEntity($model->getAnr(), false);

        $this->modelTable->save($model);
    }

    public function deleteList(array $data): void
    {
        foreach ($data as $modelId) {
            $this->delete((int)$modelId);
        }
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
        foreach ($fromModel->getAnr()->getObjects() as $object) {
            $toModel->getAnr()->addObject($object);
        }
    }

    /**
     * Verifies the model integrity before update.
     */
    private function validateBeforeUpdate(Model $model, array $data): void
    {
        if (!empty($data['isRegulator']) && !empty($data['isGeneric'])) {
            throw new Exception('A regulator model can\'t be generic', 412);
        }

        $modeObject = null;
        if (!empty($data['isRegulator']) && !$model->isRegulator()) {
            /* Changes to regulator */
            foreach ($model->getAssets() as $asset) {
                $amvs = $this->amvTable->findByAsset($asset);
                foreach ($amvs as $amv) {
                    if ($asset->isModeSpecific()
                        && $amv->getThreat()->isModeGeneric()
                        && $amv->getVulnerability()->isModeGeneric()
                    ) {
                        throw new Exception(
                            'The modification is forbidden. The level of integrity between the model ' .
                            'and its objects will be corrupted.',
                            412
                        );
                    }
                }
            }

            $modeObject = MonarcObject::MODE_GENERIC;
        } elseif (!empty($data['isGeneric']) && !$model->isGeneric()) {
            /* changes to generic */
            $modeObject = MonarcObject::MODE_SPECIFIC;
        }

        if ($modeObject !== null) {
            foreach ($model->getAnr()->getObjects() as $object) {
                if ($object->getMode() === $modeObject) {
                    throw new Exception(
                        'The modification is forbidden. The level of integrity between the model and its objects ' .
                        'will be corrupted.',
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
        if (isset($data['isRegulator'])) {
            $model->setIsRegulator((bool)$data['isRegulator']);
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
            'isRegulator' => (int)$model->isRegulator(),
            'areScalesUpdatable' => (int)$model->areScalesUpdatable(),
            'showRolfBrut' => (int)$model->getShowRolfBrut(),
            'status' => $model->getStatus(),
        ];
    }
}
