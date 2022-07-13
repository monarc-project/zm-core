<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Model\Entity;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\MeasureTable;
use Monarc\Core\Model\Table\ReferentialTable;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Table;

class AmvService implements PositionUpdatableServiceInterface
{
    use PositionUpdateTrait;

    private Table\AmvTable $amvTable;

    private InstanceTable $instanceTable;

    private Table\AssetTable $assetTable;

    private Table\ThreatTable $threatTable;

    private Table\VulnerabilityTable $vulnerabilityTable;

    private MeasureTable $measureTable;

    private ReferentialTable $referentialTable;

    private Table\ModelTable $modelTable;

    private Table\ThemeTable $themeTable;

    private HistoricalService $historicalService;

    private AssetService $assetService;

    private ThreatService $threatService;

    private ThemeService $themeService;

    private VulnerabilityService $vulnerabilityService;

    private InstanceRiskService $instanceRiskService;

    private ConnectedUserService $connectedUserService;

    public function __construct(
        Table\AmvTable $amvTable,
        InstanceTable $instanceTable,
        Table\AssetTable $assetTable,
        Table\ThreatTable $threatTable,
        Table\VulnerabilityTable $vulnerabilityTable,
        MeasureTable $measureTable,
        ReferentialTable $referentialTable,
        Table\ModelTable $modelTable,
        Table\ThemeTable $themeTable,
        HistoricalService $historicalService,
        AssetService $assetService,
        ThreatService $threatService,
        ThemeService $themeService,
        VulnerabilityService $vulnerabilityService,
        InstanceRiskService $instanceRiskService,
        ConnectedUserService $connectedUserService
    ) {
        $this->amvTable = $amvTable;
        $this->instanceTable = $instanceTable;
        $this->assetTable = $assetTable;
        $this->threatTable = $threatTable;
        $this->vulnerabilityTable = $vulnerabilityTable;
        $this->measureTable = $measureTable;
        $this->referentialTable = $referentialTable;
        $this->modelTable = $modelTable;
        $this->themeTable = $themeTable;
        $this->historicalService = $historicalService;
        $this->assetService = $assetService;
        $this->threatService = $threatService;
        $this->themeService = $themeService;
        $this->vulnerabilityService = $vulnerabilityService;
        $this->instanceRiskService = $instanceRiskService;
        $this->connectedUserService = $connectedUserService;
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

    public function getCount($params): int
    {
        return $this->amvTable->countByParams($params);
    }

    public function getAmvData(string $uuid): array
    {
        /** @var Entity\Amv $amv */
        $amv = $this->amvTable->findByUuid($uuid);

        return $this->prepareAmvDataResult($amv, true);
    }

    public function create($data)
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
            ->setCreator($this->connectedUserService->getConnectedUser()->getEmail());
        if (isset($data['status'])) {
            $amv->setStatus($data['status']);
        }

        foreach ($data['measures'] as $measureUuid) {
            $amv->addMeasure($this->measureTable->findByUuid($measureUuid));
        }

        $this->updatePositions($amv, $this->amvTable, $data);

        $this->validateAmvCompliesRequirements($amv);

        $this->amvTable->save($amv);

        $this->createInstanceRiskForInstances($asset);

        $this->historize(
            $amv,
            'create',
            $amv->getAsset()->getCode() . ' - ' . $amv->getThreat()->getCode() . ' - '
            . $amv->getVulnerability()->getCode(),
            'asset => ' . $amv->getAsset()->getCode() . ' /  threat => ' . $amv->getThreat()->getCode()
            . ' / vulnerability => ' . $amv->getVulnerability()->getCode()
        );

        return $amv->getUuid();
    }

    public function update(string $id, array $data)
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
            $vulnerability = $this->vulnerabilityTable->findById($data['vulnerability']);
            $changedData['vulnerability'] = $amv->getVulnerability()->getCode() . ' => ' . $vulnerability->getCode();

            $amv->setVulnerability($vulnerability);
        }

        $amv->unlinkMeasures();
        foreach ($data['measures'] as $measure) {
            $amv->addMeasure($this->measureTable->findByUuid($measure));
        }

        $amv->setUpdater($this->connectedUserService->getConnectedUser()->getEmail());

        $this->updatePositions($amv, $this->amvTable, $data);

        $this->validateAmvCompliesRequirements($amv);

        if (!empty($changedData)) {
            $this->historize(
                $amv,
                'create',
                $labelForHistory,
                implode(' / ', $changedData)
            );
        }

        $this->amvTable->save($amv);
    }

    public function patch(string $id, array $data)
    {
        /** @var Entity\Amv $amv */
        $amv = $this->amvTable->findByUuid($id);

        if (isset($data['status'])) {
            $amv->setStatus((int)$data['status']);
        }

        $amv->setUpdater($this->connectedUserService->getConnectedUser()->getEmail());

        $this->amvTable->save($amv);
    }

    public function delete($id)
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

        $this->amvTable->remove($amv);
    }

    public function deleteList($data)
    {
        foreach ($data as $amvUuid) {
            $this->delete($amvUuid);
        }
    }

    /**
     * TODO: review (called from FO and BO) and probably move out the method as it better fits to referential service.
     * Function to link automatically the amv of the destination from the source depending on the measures_measures
     */
    public function createLinkedAmvs($sourceUuid, $destination)
    {
        $destinationMeasures = $this->referentialTable->getEntity($destination)->getMeasures();
        foreach ($destinationMeasures as $destinationMeasure) {
            foreach ($destinationMeasure->getMeasuresLinked() as $measureLink) {
                if ($measureLink->getReferential()->getUuid() === $sourceUuid) {
                    foreach ($measureLink->getAmvs() as $amv) {
                        $destinationMeasure->addAmv($amv);
                    }
                    $this->measureTable->save($destinationMeasure, false);
                }
            }
        }
        $this->measureTable->getDb()->flush();
    }

    /**
     * Validates whether the specified theoretical AMV link complies with the behavioral requirements.
     */
    public function validateAmvCompliesRequirements(
        Entity\AmvSuperClass $amv,
        ?Entity\AssetSuperClass $asset = null,
        array $assetModels = [],
        ?Entity\ThreatSuperClass $threat = null,
        array $threatModels = [],
        ?Entity\VulnerabilitySuperClass $vulnerability = null,
        array $vulnerabilityModels = []
    ): void {
        $asset = $asset ?? $amv->getAsset();
        if ($asset->isPrimary()) {
            throw new Exception('Asset can\'t be primary', 412);
        }

        $assetMode = $asset->getMode();
        if (empty($assetModels)) {
            $assetModels = $amv->getAsset()->getModels();
        }
        $assetModelsIds = [];
        $assetModelsIsRegulator = false;
        foreach ($assetModels as $model) {
            if (!is_object($model)) {
                /** @var Entity\Model $model */
                $model = $this->modelTable->findById((int)$model);
            }
            $assetModelsIds[] = $model->getId();
            if ($model->isRegulator()) {
                $assetModelsIsRegulator = true;
            }
        }

        $threatMode = $threat === null ? $amv->getThreat()->getMode() : $threat->getMode();
        if (empty($threatModels)) {
            $threatModels = $amv->getThreat()->getModels();
        }
        $threatModelsIds = [];
        foreach ($threatModels as $model) {
            $threatModelsIds[] = \is_object($model) ? $model->getId() : $model;
        }

        $vulnerabilityMode = $vulnerability === null ? $amv->getVulnerability()->getMode() : $vulnerability->getMode();
        if (empty($vulnerabilityModels)) {
            $vulnerabilityModels = $amv->getVulnerability()->getModels();
        }
        $vulnerabilityModelsIds = [];
        foreach ($vulnerabilityModels as $model) {
            $vulnerabilityModelsIds[] = \is_object($model) ? $model->getId() : $model;
        }

        $this->validateAmvCompliesControl(
            $assetMode,
            $threatMode,
            $vulnerabilityMode,
            $assetModelsIds,
            $threatModelsIds,
            $vulnerabilityModelsIds,
            $assetModelsIsRegulator
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
        bool $assetModelsIsRegulator
    ): void {
        if (!$assetMode && !$threatMode && !$vulnerabilityMode) {
            return;
        }

        if (!$assetMode && ($threatMode || $vulnerabilityMode)) { // 0 0 1 || 0 1 0 || 0 1 1
            throw new Exception('The tuple asset / threat / vulnerability is invalid', 412);
        } elseif ($assetMode && (!$threatMode || !$vulnerabilityMode)) { // 1 0 0 || 1 0 1 || 1 1 0
            // only if there is no regulating model for the asset
            if (!$assetModelsIsRegulator) {
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
                    'All models must be common to asset and ' . $vulnerabilityMode ? 'vulnerability' : 'threat',
                    412
                );
            }

            throw new Exception('Asset\'s model must not be regulator', 412);
        } elseif ($assetMode && $threatMode && $vulnerabilityMode) { // 1 1 1 - We have to check the models.
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

    /**
     * Ensure Assets Integrity If Enforced
     */
    public function ensureAssetsIntegrityIfEnforced(
        array $models,
        ?Entity\AssetSuperClass $asset = null,
        ?Entity\ThreatSuperClass $threat = null,
        ?Entity\VulnerabilitySuperClass $vulnerability = null
    ): bool {
        $amvs = $this->amvTable->findByAmv($asset, $threat, $vulnerability);
        foreach ($amvs as $amv) {
            if (!$this->checkModelsInstantiation($amv->getAsset(), $models)) {
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
        if ($asset->isModeSpecific() && !$asset->getModels()->isEmpty()) {
            $instances = $this->instanceTable->findByAsset($asset);

            if (!empty($instances)) {
                $anrIds = [];
                foreach ($instances as $instance) {
                    $anrId = $instance->getAnr()->getId();
                    $anrIds[$anrId] = $anrId;
                }

                if (!empty($anrIds)) {
                    $modelsIds = array_flip($newModelsIds);
                    $models = $this->modelTable->findByAnrIds($anrIds);
                    foreach ($models as $model) {
                        if (!isset($modelsIds[$model->getId()])) {
                            // Don't remove asset of specific model if linked to asset by instance, in anr by object.
                            return false;
                        }
                    }
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
        ?Entity\AssetSuperClass $asset = null,
        ?Entity\ThreatSuperClass $threat = null,
        ?Entity\VulnerabilitySuperClass $vulnerability = null,
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


    public function generateExportArray(Entity\AmvSuperClass $amv, $anrId, bool $withEval = false): array
    {
        $amvObj = [
            'uuid' => 'v',
            'threat' => 'o',
            'asset' => 'o',
            'vulnerability' => 'o',
            'measures' => 'o',
            'status' => 'v',
        ];
        $treatsObj = [
            'uuid' => 'uuid',
            'theme' => 'theme',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'c' => 'c',
            'i' => 'i',
            'a' => 'a',
            'status' => 'status',
        ];
        if ($withEval) {
            $treatsObj = array_merge(
                $treatsObj,
                [
                    'trend' => 'trend',
                    'comment' => 'comment',
                    'qualification' => 'qualification',
                ]
            );
        };
        $vulsObj = [
            'uuid' => 'uuid',
            'mode' => 'mode',
            'code' => 'code',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
            'description1' => 'description1',
            'description2' => 'description2',
            'description3' => 'description3',
            'description4' => 'description4',
            'status' => 'status',
        ];
        $themesObj = [
            'id' => 'id',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
        ];
        $measuresObj = [
            'uuid' => 'uuid',
            'category' => 'category',
            'referential' => 'referential',
            'code' => 'code',
            'status' => 'status',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
        ];
        $soacategoriesObj = [
            'id' => 'id',
            'code' => 'code',
            'status' => 'status',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
        ];
        $referentialObj = [
            'uuid' => 'uuid',
            'label1' => 'label1',
            'label2' => 'label2',
            'label3' => 'label3',
            'label4' => 'label4',
        ];

        $amvs = $threats = $vulns = $themes = $measures = [];

        foreach ($amvObj as $k => $v) {
            switch ($v) {
                case 'v':
                    $amvs[$k] = $amv->get($k);
                    break;
                case 'o':
                    $o = $amv->get($k);
                    if (empty($o)) {
                        $amvs[$k] = null;
                    } else {
                        switch ($k) {
                            case 'threat':
                                $threatUuid = $amv->getThreat()->getUuid();
                                $amvs[$k] = $threatUuid;
                                $threats[$threatUuid] = $amv->get($k)->getJsonArray($treatsObj);
                                if (!empty($threats[$threatUuid]['theme'])) {
                                    $threats[$threatUuid]['theme'] = $threats[$threatUuid]['theme']->getJsonArray(
                                        $themesObj
                                    );
                                    $themes[$threats[$threatUuid]['theme']['id']] = $threats[$threatUuid]['theme'];
                                    $threats[$threatUuid]['theme'] = $threats[$threatUuid]['theme']['id'];
                                }
                                break;
                            case 'vulnerability':
                                $vulnerabilityUuid = $amv->getVulnerability()->getUuid();
                                $amvs[$k] = $vulnerabilityUuid;
                                // TODO: it wont work due to refactoring.
                                $vulns[$vulnerabilityUuid] = $amv->get($k)->getJsonArray($vulsObj);
                                break;
                            case 'asset':
                                $amvs[$k] = $amv->getAsset()->getUuid();
                                break;
                            case 'measures':
                                $measuresList = $amv->getMeasures();
                                if (\count($measuresList) > 0) {
                                    foreach ($measuresList as $measure) {
                                        $measureUuid = $measure->getUuid();
                                        $measures[$measureUuid] = $measure->getJsonArray($measuresObj);
                                        $measures[$measureUuid]['category'] = $measure->getCategory()
                                            ? $measure->getCategory()->getJsonArray($soacategoriesObj)
                                            : '';
                                        $measures[$measureUuid]['referential'] = $measure->getReferential(
                                        )->getJsonArray($referentialObj);
                                        $amvs[$k][] = $measureUuid;
                                    }
                                }
                                break;
                        }
                    }
                    break;
            }
        }

        return [
            $amvs,
            $threats,
            $vulns,
            $themes,
            $measures,
        ];
    }

    public function generateExportMospArray(Entity\AmvSuperClass $amv, $anrId, $languageCode): array
    {
        $language = $this->getLanguage();

        $amvObj = [
            'uuid' => 'v',
            'threat' => 'o',
            'asset' => 'o',
            'vulnerability' => 'o',
            'measures' => 'o',
        ];
        $treatsObj = [
            'uuid' => 'uuid',
            'theme' => 'theme',
            'code' => 'code',
            'label' => 'label' . $language,
            'description' => 'description' . $language,
            'c' => 'c',
            'i' => 'i',
            'a' => 'a',
        ];
        $vulsObj = [
            'uuid' => 'uuid',
            'code' => 'code',
            'label' => 'label' . $language,
            'description' => 'description' . $language,
        ];
        $measuresObj = [
            'uuid' => 'uuid',
            'category' => 'category',
            'referential' => 'referential',
            'referential_label' => 'referential_label',
            'code' => 'code',
            'label' => 'label',
        ];

        $amvs = $threats = $vulns = $measures = [];

        foreach ($amvObj as $k => $v) {
            switch ($v) {
                case 'v':
                    $amvs[$k] = $amv->get($k);
                    break;
                case 'o':
                    $o = $amv->get($k);
                    if (empty($o)) {
                        $amvs[$k] = null;
                    } else {
                        switch ($k) {
                            case 'threat':
                                $threatUuid = $amv->getThreat()->getUuid();
                                $amvs[$k] = $threatUuid;
                                $threats[$threatUuid] = $amv->get($k)->getJsonArray($treatsObj);
                                if (!empty($threats[$threatUuid]['theme'])) {
                                    $threats[$threatUuid]['theme'] = $threats[$threatUuid]['theme']->getJsonArray();
                                    $threats[$threatUuid]['theme'] = $threats[$threatUuid]['theme']['label' . $language];
                                    $threats[$threatUuid]['label'] = $threats[$threatUuid]['label' . $language];
                                    $threats[$threatUuid]['description'] = $threats[$threatUuid]['description' . $language] ?? '';
                                    $threats[$threatUuid]['c'] = boolval($threats[$threatUuid]['c']);
                                    $threats[$threatUuid]['i'] = boolval($threats[$threatUuid]['i']);
                                    $threats[$threatUuid]['a'] = boolval($threats[$threatUuid]['a']);
                                    $threats[$threatUuid]['language'] = $languageCode;
                                    unset($threats[$threatUuid]['label' . $language]);
                                    unset($threats[$threatUuid]['description' . $language]);

                                }
                                break;
                            case 'vulnerability':
                                // TODO: it wont work due to refactoring.
                                $vulnerabilityUuid = $amv->getVulnerability()->getUuid();
                                $amvs[$k] = $vulnerabilityUuid;
                                $vulns[$vulnerabilityUuid] = $amv->get($k)->getJsonArray($vulsObj);
                                $vulns[$vulnerabilityUuid]['label'] = $vulns[$vulnerabilityUuid]['label' . $language];
                                $vulns[$vulnerabilityUuid]['description'] = $vulns[$vulnerabilityUuid]['description' . $language] ?? '';
                                $vulns[$vulnerabilityUuid]['language'] = $languageCode;
                                unset($vulns[$vulnerabilityUuid]['label' . $language]);
                                unset($vulns[$vulnerabilityUuid]['description' . $language]);
                                break;
                            case 'asset':
                                $amvs[$k] = $amv->getAsset()->getUuid();
                                break;
                            case 'measures':
                                $measuresList = $amv->getMeasures();
                                if (\count($measuresList) > 0) {
                                    foreach ($measuresList as $measure) {
                                        $measureUuid = $measure->getUuid();
                                        $measures[$measureUuid] = $measure->getJsonArray($measuresObj);
                                        $measures[$measureUuid]['label'] = $measure->getLabel($language);
                                        $measures[$measureUuid]['category'] = $measure->getCategory()
                                            ->getLabel($language);
                                        $measures[$measureUuid]['referential'] = $measure->getReferential()->getUuid();
                                        $measures[$measureUuid]['referential_label'] = $measure->getReferential()
                                            ->getLabel($language);
                                        $amvs[$k][] = $measureUuid;
                                    }
                                }
                                break;
                        }
                    }
                    break;
            }
        }

        return [
            $amvs,
            $threats,
            $vulns,
            $measures,
        ];
    }

    protected function historize(Entity\AmvSuperClass $amv, string $action, string $label, string $details)
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

    /**
     * Creates the amv items (assets, threats, vulnerabilities) to use them for AMVs creation later.
     */
    public function createAmvItems(array $data): array
    {
        $createdItems = [];
        foreach ($data as $amvItem) {
            if (!isset($amvItem['asset']['uuid'], $amvItem['threat']['uuid'], $amvItem['vulnerability']['uuid'])
                || $this->amvTable->findByAmvItemsUuids(
                    $amvItem['asset']['uuid'],
                    $amvItem['threat']['uuid'],
                    $amvItem['vulnerability']['uuid'],
                ) !== null
            ) {
                continue;
            }

            $asset = $this->getOrCreateAssetObject($amvItem['asset']);
            $threat = $this->getOrCreateThreatObject($amvItem['threat']);
            $vulnerability = $this->getOrCreateVulnerabilityObject($amvItem['vulnerability']);

            $amv = (new Entity\Amv())
                ->setAsset($asset)
                ->setThreat($threat)
                ->setVulnerability($vulnerability)
                ->setCreator($this->connectedUserService->getConnectedUser()->getEmail());
            $this->amvTable->save($amv);

            $createdItems[] = $amv->getUuid();
        }

        return $createdItems;
    }

    private function getOrCreateAssetObject(array $assetData): Entity\Asset
    {
        if (!empty($assetData['uuid'])) {
            try {
                return $this->assetTable->findByUuid($assetData['uuid']);
            } catch (EntityNotFoundException $e) {
            }
        }

        return $this->assetService->create($assetData, false);
    }

    private function getOrCreateThreatObject(array $threatData): Entity\Threat
    {
        if (!empty($threatData['uuid'])) {
            try {
                return $this->threatTable->findByUuid($threatData['uuid']);
            } catch (EntityNotFoundException $e) {
            }
        }

        $labelKey = 'label1';
        if (!empty($threatData['theme'][$labelKey])) {
            $theme = $this->themeTable->findByLabel($labelKey, $threatData['theme'][$labelKey]);
            if ($theme === null) {
                $theme = $this->themeService->create($threatData['theme'], false);
            }
            $threatData['theme'] = $theme;
        } else {
            unset($threatData['theme']);
        }

        return $this->threatService->create($threatData, false);
    }

    private function getOrCreateVulnerabilityObject(array $vulnerabilityData): Entity\Vulnerability
    {
        if (!empty($vulnerabilityData['uuid'])) {
            try {
                return $this->vulnerabilityTable->findByUuid($vulnerabilityData['uuid']);
            } catch (EntityNotFoundException $e) {
            }
        }

        return $this->vulnerabilityService->create($vulnerabilityData);
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
                $maxPositionByAsset = $this->amvTable->findMaxPosition(['asset' => $amv->getAsset()]);
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
            'asset' => [
                'uuid' => $asset->getUuid(),
                'code' => $asset->getCode(),
                'label1' => $asset->getLabel(1),
                'label2' => $asset->getLabel(2),
                'label3' => $asset->getLabel(3),
                'label4' => $asset->getLabel(4),
            ],
            'threat' => [
                'uuid' => $threat->getUuid(),
                'code' => $threat->getCode(),
                'label1' => $threat->getLabel(1),
                'label2' => $threat->getLabel(2),
                'label3' => $threat->getLabel(3),
                'label4' => $threat->getLabel(4),
            ],
            'vulnerability' => [
                'uuid' => $vulnerability->getUuid(),
                'code' => $vulnerability->getCode(),
                'label1' => $vulnerability->getLabel(1),
                'label2' => $vulnerability->getLabel(2),
                'label3' => $vulnerability->getLabel(3),
                'label4' => $vulnerability->getLabel(4),
            ],
        ];
    }

    private function createInstanceRiskForInstances(Entity\Asset $asset): void
    {
        $instances = $this->instanceTable->findByAsset($asset);
        foreach ($instances as $instance) {
            $this->instanceRiskService->createInstanceRisks($instance, $instance->getAnr(), $instance->getObject());
        }
    }
}
