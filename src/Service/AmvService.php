<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Entity;
use Monarc\Core\Table\MeasureTable;
use Monarc\Core\Table\ReferentialTable;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Table;

class AmvService implements PositionUpdatableServiceInterface
{
    use PositionUpdateTrait;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        private Table\AmvTable $amvTable,
        private Table\AssetTable $assetTable,
        private Table\ThreatTable $threatTable,
        private Table\VulnerabilityTable $vulnerabilityTable,
        private MeasureTable $measureTable,
        private ReferentialTable $referentialTable,
        private Table\ModelTable $modelTable,
        private HistoricalService $historicalService,
        private AssetService $assetService,
        private ThreatService $threatService,
        private VulnerabilityService $vulnerabilityService,
        private InstanceRiskService $instanceRiskService,
        ConnectedUserService $connectedUserService
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function getList(FormattedInputParams $params): array
    {
        $result = [];
        /** @var Entity\Amv[] $amvs */
        $amvs = $this->amvTable->findByParams($params);
        foreach ($amvs as $amv) {
            $result[] = $this->prepareAmvDataResult($amv);
        }

        return $result;
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->amvTable->countByParams($params);
    }

    public function getAmvData(string $uuid): array
    {
        /** @var Entity\Amv $amv */
        $amv = $this->amvTable->findByUuid($uuid);

        return $this->prepareAmvDataResult($amv, true);
    }

    public function create(array $data): Entity\Amv
    {
        if ($this->amvTable->findByAmvItemsUuids($data['asset'], $data['threat'], $data['vulnerability']) !== null) {
            throw new Exception('The informational risk already exists.', 412);
        }

        /** @var Entity\Asset $asset */
        $asset = $this->assetTable->findByUuid($data['asset']);
        /** @var Entity\Threat $threat */
        $threat = $this->threatTable->findByUuid($data['threat']);
        /** @var Entity\Vulnerability $vulnerability */
        $vulnerability = $this->vulnerabilityTable->findById($data['vulnerability']);

        $amv = (new Entity\Amv())
            ->setAsset($asset)
            ->setThreat($threat)
            ->setVulnerability($vulnerability)
            ->setCreator($this->connectedUser->getEmail());
        if (isset($data['status'])) {
            $amv->setStatus($data['status']);
        }

        $this->validateAmvCompliesRequirements($amv);

        foreach ($data['measures'] ?? [] as $measureUuid) {
            $amv->addMeasure($this->measureTable->findByUuid($measureUuid));
        }

        $this->updatePositions($amv, $this->amvTable, $data);

        $this->createInstanceRiskForInstances($asset, $amv);

        $this->amvTable->save($amv);

        $this->historize(
            $amv,
            'create',
            $amv->getAsset()->getCode() . ' - ' . $amv->getThreat()->getCode() . ' - '
            . $amv->getVulnerability()->getCode(),
            'asset => ' . $amv->getAsset()->getCode() . ' /  threat => ' . $amv->getThreat()->getCode()
            . ' / vulnerability => ' . $amv->getVulnerability()->getCode()
        );

        /** @var Entity\Amv */
        return $amv;
    }

    public function update(string $id, array $data): Entity\Amv
    {
        /** @var Entity\Amv $amv */
        $amv = $this->amvTable->findByUuid($id);

        $labelForHistory = $amv->getAsset()->getCode() . ' - ' . $amv->getThreat()->getCode() . ' - '
            . $amv->getVulnerability()->getCode();
        $changedData = [];
        if ($data['asset'] !== $amv->getAsset()->getUuid()) {
            /** @var Entity\Asset $asset */
            $asset = $this->assetTable->findByUuid($data['asset']);
            $changedData['asset'] = $amv->getAsset()->getCode() . ' => ' . $asset->getCode();

            $amv->setAsset($asset);
        }
        if ($data['threat'] !== $amv->getThreat()->getUuid()) {
            /** @var Entity\Threat $threat */
            $threat = $this->threatTable->findByUuid($data['threat']);
            $changedData['threat'] = $amv->getThreat()->getCode() . ' => ' . $threat->getCode();

            $amv->setThreat($threat);
        }
        if ($data['vulnerability'] !== $amv->getVulnerability()->getUuid()) {
            /** @var Entity\Vulnerability $vulnerability */
            $vulnerability = $this->vulnerabilityTable->findById($data['vulnerability']);
            $changedData['vulnerability'] = $amv->getVulnerability()->getCode() . ' => ' . $vulnerability->getCode();

            $amv->setVulnerability($vulnerability);
        }

        $this->validateAmvCompliesRequirements($amv);

        $amv->unlinkMeasures();
        foreach ($data['measures'] as $measure) {
            $amv->addMeasure($this->measureTable->findByUuid($measure));
        }

        $amv->setUpdater($this->connectedUser->getEmail());

        $this->updatePositions($amv, $this->amvTable, $data);

        if (!empty($changedData)) {
            $this->historize(
                $amv,
                'create',
                $labelForHistory,
                implode(' / ', $changedData)
            );
        }

        $this->amvTable->save($amv);

        return $amv;
    }

    public function patch(string $id, array $data): Entity\Amv
    {
        /** @var Entity\Amv $amv */
        $amv = $this->amvTable->findByUuid($id);

        if (isset($data['status'])) {
            $amv->setStatus((int)$data['status']);
        }

        $amv->setUpdater($this->connectedUser->getEmail());

        $this->amvTable->save($amv);

        return $amv;
    }

    /**
     * Import of instance risks.
     *
     * @return string[] Created Amvs' uuids.
     */
    public function createAmvItems(array $data): array
    {
        $createdAmvsUuids = [];
        foreach ($data as $amvData) {
            if (isset($amvData['asset']['uuid'], $amvData['threat']['uuid'], $amvData['vulnerability']['uuid'])
                && $this->amvTable->findByAmvItemsUuids(
                    $amvData['asset']['uuid'],
                    $amvData['threat']['uuid'],
                    $amvData['vulnerability']['uuid'],
                ) !== null
            ) {
                continue;
            }

            $asset = $this->assetService->getOrCreateAsset($amvData['asset']);
            $threat = $this->threatService->getOrCreateThreat($amvData['threat']);
            $vulnerability = $this->vulnerabilityService->getOrCreateVulnerability($amvData['vulnerability']);

            $amv = (new Entity\Amv())
                ->setAsset($asset)
                ->setThreat($threat)
                ->setVulnerability($vulnerability)
                ->setCreator($this->connectedUser->getEmail());

            $this->createInstanceRiskForInstances($asset, $amv);

            $this->amvTable->save($amv);

            $createdAmvsUuids[] = $amv->getUuid();
        }

        return $createdAmvsUuids;
    }

    /**
     * Links amv of destination to source depending on the measures_measures (map referential).
     */
    public function createLinkedAmvs(string $sourceReferentialUuid, string $destinationReferentialUuid): void
    {
        $referential = $this->referentialTable->findByUuid($destinationReferentialUuid);
        foreach ($referential->getMeasures() as $destinationMeasure) {
            foreach ($destinationMeasure->getLinkedMeasures() as $measureLink) {
                if ($measureLink->getReferential()->getUuid() === $sourceReferentialUuid) {
                    foreach ($measureLink->getAmvs() as $amv) {
                        $destinationMeasure->addAmv($amv);
                    }
                    $this->measureTable->save($destinationMeasure, false);
                }
            }
        }
        $this->measureTable->getDb()->flush();
    }

    public function delete(string $id): void
    {
        /** @var Entity\Amv $amv */
        $amv = $this->amvTable->findByUuid($id);

        $this->historize(
            $amv,
            'delete',
            $amv->getAsset()->getCode() . ' - ' . $amv->getThreat()->getCode() . ' - '
            . $amv->getVulnerability()->getCode(),
            'asset => ' . $amv->getAsset()->getCode() . ' /  threat => ' . $amv->getThreat()->getCode()
            . ' / vulnerability => ' . $amv->getVulnerability()->getCode()
        );

        $this->shiftPositionsForRemovingEntity($amv, $this->amvTable);

        $this->amvTable->remove($amv);
    }

    public function deleteList(array $data): void
    {
        foreach ($data as $amvUuid) {
            $this->delete($amvUuid);
        }
    }

    /**
     * Ensure Assets Integrity If Enforced
     */
    public function ensureAssetsIntegrityIfEnforced(
        array $modelsIds,
        ?Entity\Asset $asset = null,
        ?Entity\Threat $threat = null,
        ?Entity\Vulnerability $vulnerability = null
    ): bool {
        $amvs = $this->amvTable->findByAmv($asset, $threat, $vulnerability);
        foreach ($amvs as $amv) {
            if (!$this->checkModelsInstantiation($amv->getAsset(), $modelsIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check Models Instantiation: Don't remove to an asset of specific model if it is linked to asset by an instance
     * in an anr (by object).
     */
    public function checkModelsInstantiation(Entity\Asset $asset, array $newModelsIds): bool
    {
        if ($asset->isModeSpecific() && $asset->hasInstances() && !$asset->getModels()->isEmpty()) {
            $modelsIds = array_flip($newModelsIds);
            foreach ($asset->getInstances() as $instance) {
                /** @var Entity\Anr $anr */
                $anr = $instance->getAnr();
                if (!isset($modelsIds[$anr->getModel()->getId()])) {
                    // Don't remove asset of specific model if linked to asset by instance, in anr by object.
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Enforces Amv to follow evolution.
     */
    public function enforceAmvToFollow(
        iterable $models,
        ?Entity\Asset $asset = null,
        ?Entity\Threat $threat = null,
        ?Entity\Vulnerability $vulnerability = null
    ): void {
        if (empty($models)) {
            return;
        }
        $amvs = $this->amvTable->findByAmv($asset, $threat, $vulnerability);
        foreach ($amvs as $amv) {
            foreach ($models as $model) {
                $model->addAsset($amv->getAsset())
                    ->addThreat($amv->getThreat())
                    ->addVulnerability($amv->getVulnerability());
                $this->modelTable->save($model, false);
            }
        }
        $this->modelTable->flush();
    }

    /**
     * Checks the AMV Integrity Level
     *
     * @param Entity\Model[] $models The models in which the AMV link will be applicable
     * @param Entity\Asset|null $asset The asset
     * @param Entity\Threat|null $threat The threat
     * @param Entity\Vulnerability|null $vulnerability The vulnerability
     * @param bool $follow Whether the AMV link follows changes
     */
    public function checkAmvIntegrityLevel(
        array $models,
        ?Entity\Asset $asset = null,
        ?Entity\Threat $threat = null,
        ?Entity\Vulnerability $vulnerability = null,
        bool $follow = false
    ): bool {
        $amvs = $this->amvTable->findByAmv($asset, $threat, $vulnerability);

        foreach ($amvs as $amv) {
            $assetModels = $asset || $follow ? $models : [];
            $threatsModels = $threat || $follow ? $models : [];
            $vulnerabilityModels = $vulnerability || $follow ? $models : [];
            $this->validateAmvCompliesRequirements(
                $amv,
                $asset,
                $assetModels,
                $threat,
                $threatsModels,
                $vulnerability,
                $vulnerabilityModels
            );
        }

        return true;
    }

    /**
     * Validates whether the specified theoretical AMV link complies with the behavioral requirements.
     *
     * @param Entity\Model[]|int[] $assetModels
     * @param Entity\Model[]|int[] $threatModels
     * @param Entity\Model[]|int[] $vulnerabilityModels
     */
    private function validateAmvCompliesRequirements(
        Entity\Amv $amv,
        ?Entity\Asset $asset = null,
        array $assetModels = [],
        ?Entity\Threat $threat = null,
        array $threatModels = [],
        ?Entity\Vulnerability $vulnerability = null,
        array $vulnerabilityModels = []
    ): void {
        /** @var Entity\Asset $asset */
        $asset = $asset ?? $amv->getAsset();
        if ($asset->isPrimary()) {
            throw new Exception('Asset can\'t be primary', 412);
        }

        $assetModelsIds = [];
        foreach (empty($assetModels) ? $asset->getModels() : $assetModels as $model) {
            if (!\is_object($model)) {
                /** @var Entity\Model $model */
                $model = $this->modelTable->findById((int)$model);
            }
            $assetModelsIds[] = $model->getId();
        }

        $threatModelsIds = [];
        foreach (empty($threatModels) ? $amv->getThreat()->getModels() : $threatModels as $model) {
            $threatModelsIds[] = \is_object($model) ? $model->getId() : $model;
        }

        $vulnerabilityModelsIds = [];
        foreach (empty($vulnerabilityModels) ? $amv->getVulnerability()->getModels() : $vulnerabilityModels as $model) {
            $vulnerabilityModelsIds[] = \is_object($model) ? $model->getId() : $model;
        }

        $this->validateAmvCompliesControl(
            $asset->getMode(),
            $threat === null ? $amv->getThreat()->getMode() : $threat->getMode(),
            $vulnerability === null ? $amv->getVulnerability()->getMode() : $vulnerability->getMode(),
            $assetModelsIds,
            $threatModelsIds,
            $vulnerabilityModelsIds
        );
    }

    /**
     * Checks whether the A/M/V combo is compatible to build a link.
     */
    private function validateAmvCompliesControl(
        int $assetMode,
        int $threatMode,
        int $vulnerabilityMode,
        $assetModelsIds,
        $threatModelsIds,
        $vulnerabilityModelsIds,
    ): void {
        if (!$assetMode && !$threatMode && !$vulnerabilityMode) {
            return;
        }

        if (!$assetMode && ($threatMode || $vulnerabilityMode)) { // 0 0 1 || 0 1 0 || 0 1 1
            throw new Exception(
                'The tuple asset / threat / vulnerability is invalid. '
                . 'Specific and generic objects can\'t consist a link together',
                412
            );
        }

        if ($assetMode && (!$threatMode || !$vulnerabilityMode)) { // 1 0 0 || 1 0 1 || 1 1 0
            if (!$threatMode && !$vulnerabilityMode) { // 1 0 0
                return;
            }
            // We have to check the models.
            if (empty($assetModelsIds)) {
                $assetModelsIds = [];
            } elseif (!\is_array($assetModelsIds)) {
                $assetModelsIds = [$assetModelsIds];
            }
            if ($vulnerabilityMode) { // 1 0 1
                $toTest = $vulnerabilityModelsIds;
                if (empty($toTest)) {
                    $toTest = [];
                } elseif (!\is_array($toTest)) {
                    $toTest = [$toTest];
                }
            } else { // 1 1 0
                $toTest = $threatModelsIds;
                if (empty($toTest)) {
                    $toTest = [];
                } elseif (!\is_array($toTest)) {
                    $toTest = [$toTest];
                }
            }
            $diff1 = array_diff($assetModelsIds, $toTest);
            if (empty($diff1)) {
                $diff2 = array_diff($toTest, $assetModelsIds);
                if (empty($diff2)) {
                    return;
                }
            }

            throw new Exception(
                'Specific models relations must be common to asset and '
                . ($vulnerabilityMode ? 'vulnerability' : 'threat'),
                412
            );
        }

        if ($assetMode && $threatMode && $vulnerabilityMode) { // 1 1 1 - We have to check the models.
            if (empty($assetModelsIds)) {
                $assetModelsIds = [];
            } elseif (!\is_array($assetModelsIds)) {
                $assetModelsIds = [$assetModelsIds];
            }
            if (empty($threatModelsIds)) {
                $threatModelsIds = [];
            } elseif (!\is_array($threatModelsIds)) {
                $threatModelsIds = [$threatModelsIds];
            }
            if (empty($vulnerabilityModelsIds)) {
                $vulnerabilityModelsIds = [];
            } elseif (!\is_array($vulnerabilityModelsIds)) {
                $vulnerabilityModelsIds = [$vulnerabilityModelsIds];
            }
            $diff1 = array_diff($assetModelsIds, $threatModelsIds);
            $diff15 = array_diff($threatModelsIds, $assetModelsIds);
            if (empty($diff1) && empty($diff15)) {
                $diff2 = array_diff($assetModelsIds, $vulnerabilityModelsIds);
                $diff25 = array_diff($vulnerabilityModelsIds, $assetModelsIds);
                if (empty($diff2) && empty($diff25)) {
                    $diff3 = array_diff($threatModelsIds, $vulnerabilityModelsIds);
                    $diff35 = array_diff($vulnerabilityModelsIds, $threatModelsIds);
                    if (empty($diff3) && empty($diff35)) {
                        return;
                    }
                }
            }
            throw new Exception('All models must be common to asset, threat and vulnerability', 412);
        }

        throw new Exception('Missing data', 412);
    }

    private function historize(Entity\AmvSuperClass $amv, string $action, string $label, string $details): void
    {
        $this->historicalService->create([
            'type' => 'amv',
            'sourceId' => $amv->getUuid(),
            'action' => $action,
            'label1' => $label,
            'label2' => $label,
            'label3' => $label,
            'label4' => $label,
            'details' => $details,
        ]);
    }

    private function prepareAmvDataResult(Entity\Amv $amv, bool $includePositionFields = false): array
    {
        $measures = [];
        foreach ($amv->getMeasures() as $measure) {
            $referential = $measure->getReferential();
            $measures[] = [
                'uuid' => $measure->getUuid(),
                'code' => $measure->getCode(),
                'label1' => $measure->getLabel(1),
                'label2' => $measure->getLabel(2),
                'label3' => $measure->getLabel(3),
                'label4' => $measure->getLabel(4),
                'referential' => [
                    'uuid' => $referential->getUuid(),
                    'label1' => $referential->getLabel(1),
                    'label2' => $referential->getLabel(2),
                    'label3' => $referential->getLabel(3),
                    'label4' => $referential->getLabel(4),
                ]
            ];
        }

        $result = array_merge([
            'uuid' => $amv->getUuid(),
            'measures' => $measures,
            'status' => $amv->getStatus(),
            'position' => $amv->getPosition(),
        ], $this->getAmvRelationsData($amv));

        if ($includePositionFields) {
            $result['implicitPosition'] = 1;
            if ($amv->getPosition() > 1) {
                $maxPositionByAsset = $this->amvTable->findMaxPosition($amv->getImplicitPositionRelationsValues());
                if ($maxPositionByAsset === $amv->getPosition()) {
                    $result['implicitPosition'] = 2;
                } else {
                    $previousAmv = $this->amvTable->findByAssetAndPosition($amv->getAsset(), $amv->getPosition() - 1);
                    if ($previousAmv !== null) {
                        $result['implicitPosition'] = 3;
                        $result['previous'] = array_merge([
                            'uuid' => $previousAmv->getUuid(),
                            'position' => $previousAmv->getPosition(),
                        ], $this->getAmvRelationsData($previousAmv));
                    }
                }
            }
        }

        return $result;
    }

    private function getAmvRelationsData(Entity\Amv $amv): array
    {
        $asset = $amv->getAsset();
        $threat = $amv->getThreat();
        $vulnerability = $amv->getVulnerability();

        return [
            'asset' => array_merge([
                'uuid' => $asset->getUuid(),
                'code' => $asset->getCode(),
            ], $asset->getLabels()),
            'threat' => array_merge([
                'uuid' => $threat->getUuid(),
                'code' => $threat->getCode(),
            ], $threat->getLabels()),
            'vulnerability' => array_merge([
                'uuid' => $vulnerability->getUuid(),
                'code' => $vulnerability->getCode(),
            ], $vulnerability->getLabels()),
        ];
    }

    /**
     * Created instance risks based on the newly created AMV for the instances based on the linked asset.
     */
    private function createInstanceRiskForInstances(Entity\Asset $asset, Entity\Amv $amv): void
    {
        foreach ($asset->getInstances() as $instance) {
            $this->instanceRiskService->createInstanceRisk($instance, $amv);
        }
    }
}
