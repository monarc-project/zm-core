<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

// TODO: optimize import section when all tables are refactored. To use Table\, Service\.
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\Amv;
use Monarc\Core\Model\Entity\AmvSuperClass;
use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Model\Entity\Model;
use Monarc\Core\Model\Entity\Theme;
use Monarc\Core\Model\Entity\Threat;
use Monarc\Core\Model\Entity\ThreatSuperClass;
use Monarc\Core\Model\Entity\Vulnerability;
use Monarc\Core\Model\Entity\VulnerabilitySuperClass;
use Monarc\Core\Service\Traits\QueryParamsFormatterTrait;
use Monarc\Core\Table\AmvTable;
use Monarc\Core\Model\Table\AssetTable;
use Monarc\Core\Model\Table\InstanceTable;
use Monarc\Core\Model\Table\MeasureTable;
use Monarc\Core\Model\Table\ReferentialTable;
use Monarc\Core\Model\Table\ThreatTable;
use Monarc\Core\Service\Interfaces\PositionUpdatableServiceInterface;
use Monarc\Core\Service\Traits\PositionUpdateTrait;
use Monarc\Core\Table\ModelTable;
use Monarc\Core\Model\Table\ThemeTable;
use Monarc\Core\Table\VulnerabilityTable;

class AmvService implements PositionUpdatableServiceInterface
{
    use PositionUpdateTrait;
    use QueryParamsFormatterTrait;

    protected static array $searchFields = [
        'asset.code',
        'asset.label1',
        'asset.label2',
        'asset.label3',
        'asset.label4',
        'asset.description1',
        'asset.description2',
        'asset.description3',
        'asset.description4',
        'threat.code',
        'threat.label1',
        'threat.label2',
        'threat.label3',
        'threat.label4',
        'threat.description1',
        'threat.description2',
        'threat.description3',
        'threat.description4',
        'vulnerability.code',
        'vulnerability.label1',
        'vulnerability.label2',
        'vulnerability.label3',
        'vulnerability.label4',
        'vulnerability.description1',
        'vulnerability.description2',
        'vulnerability.description3',
        'vulnerability.description4',
    ];

    private AmvTable $amvTable;

    private InstanceTable $instanceTable;

    private AssetTable $assetTable;

    private ThreatTable $threatTable;

    private VulnerabilityTable $vulnerabilityTable;

    private MeasureTable $measureTable;

    private ReferentialTable $referentialTable;

    private ModelTable $modelTable;

    private ThemeTable $themeTable;

    private HistoricalService $historicalService;

    private AssetService $assetService;

    private ThreatService $threatService;

    private VulnerabilityService $vulnerabilityService;

    private ConnectedUserService $connectedUserService;

    public function __construct(
        AmvTable $amvTable,
        InstanceTable $instanceTable,
        AssetTable $assetTable,
        ThreatTable $threatTable,
        VulnerabilityTable $vulnerabilityTable,
        MeasureTable $measureTable,
        ReferentialTable $referentialTable,
        ModelTable $modelTable,
        ThemeTable $themeTable,
        HistoricalService $historicalService,
        AssetService $assetService,
        ThreatService $threatService,
        VulnerabilityService $vulnerabilityService,
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
        $this->vulnerabilityService = $vulnerabilityService;
        $this->connectedUserService = $connectedUserService;
    }

    public function getList(string $searchString, array $filter, string $orderField): array
    {
        $result = [];

        $params = $this->getFormattedFilterParams($searchString, $filter);
        $order = $this->getFormattedOrder($orderField);

        /** @var Amv[] $amvs */
        $amvs = $this->vulnerabilityTable->findByParams($params, $order);
        foreach ($amvs as $amv) {
            $result[] = $this->prepareAmvDataResult($amv);
        }

        return $result;
    }

    // TODO: We probably don't need this
    public function getFilteredCount($filter = null, $filterAnd = null)
    {
        [$filterJoin, $filterLeft, $filtersCol] = $this->getFilters();

        return $this->amvTable->countFiltered(
            $this->parseFrontendFilter($filter, $filtersCol),
            $filterAnd,
            $filterJoin,
            $filterLeft
        );
    }

    public function getAmvData(string $uuid): array
    {
        $amv = $this->amvTable->findByUuid($uuid);

        return $this->prepareAmvDataResult($amv);
    }

    public function create($data)
    {
        if ($this->amvTable->findByAmvItemsUuids($data['asset'], $data['threat'], $data['vulnerability']) !== null) {
            throw new Exception('The informational risk already exists.', 412);
        }

        $asset = $this->assetTable->findByUuid($data['asset']);
        $threat = $this->threatTable->findByUuid($data['threat']);
        $vulnerability = $this->vulnerabilityTable->findById($data['vulnerability']);

        $amv = (new Amv())
            ->setAsset($asset)
            ->setThreat($threat)
            ->setVulnerability($vulnerability)
            ->setCreator($this->connectedUserService->getConnectedUser()->getEmail());

        foreach ($data['measures'] as $measureUuid) {
            $amv->addMeasure($this->measureTable->findByUuid($measureUuid));
        }

        $this->updatePositions($amv, $this->amvTable, $data);

        $this->validateAmvCompliesRequirements($amv);

        $this->amvTable->save($amv);

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
        $amv = $this->amvTable->findByUuid($id);

        $labelForHistory = $amv->getAsset()->getCode() . ' - ' . $amv->getThreat()->getCode() . ' - '
            . $amv->getVulnerability()->getCode();
        $changedData = [];
        if ($data['asset'] !== $amv->getAsset()->getUuid()) {
            $asset = $this->assetTable->findByUuid($data['asset']);
            $changedData['asset'] = $amv->getAsset()->getCode() . ' => ' . $asset->getCode();

            $amv->setAsset($asset);
        }
        if ($data['threat'] !== $amv->getThreat()->getUuid()) {
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

        // TODO: handle position and check the compliesRequirement
        $this->validateAmvCompliesRequirements($amv);

        if (!empty($changedData)) {
            $this->historize(
                $amv,
                'create',
                $labelForHistory,
                implode(' / ', $changedData)
            );
        }

        $amv->setUpdater($this->connectedUserService->getConnectedUser()->getEmail());

        $this->amvTable->save($amv);
    }

    public function patch(string $id, array $data)
    {
        $amv = $this->amvTable->findByUuid($id);

        if (isset($data['status'])) {
            $amv->setStatus((int)$data['status']);
        }

        $amv->setUpdater($this->connectedUserService->getConnectedUser()->getEmail());

        $this->amvTable->save($amv);
    }

    public function delete($id)
    {
        $amv = $this->amvTable->findByUuid($id);

        $this->historize(
            $amv,
            'delete',
            $amv->getAsset()->getCode() . ' - ' . $amv->getThreat()->getCode() . ' - '
            . $amv->getVulnerability()->getCode(),
            'asset => ' . $amv->getAsset()->getCode() . ' /  threat => ' . $amv->getThreat()->getCode()
            . ' / vulnerability => ' . $amv->getVulnerability()->getCode()
        );

        $this->amvTable->delete($amv);
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
                if ($measureLink->getReferential()->getUuid() == $sourceUuid) {
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
        AmvSuperClass $amv,
        ?AssetSuperClass $asset = null,
        array $assetModels = [],
        ?ThreatSuperClass $threat = null,
        array $threatModels = [],
        ?VulnerabilitySuperClass $vulnerability = null,
        array $vulnerabilityModels = []
    ): void {
        $asset = $asset ?? $amv->getAsset();
        if ($asset->getType() === Asset::TYPE_PRIMARY) {
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
                /** @var Model $model */
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
        ?AssetSuperClass $asset = null,
        ?ThreatSuperClass $threat = null,
        ?VulnerabilitySuperClass $vulnerability = null
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
    public function checkModelsInstantiation(Asset $asset, array $newModelsIds): bool
    {
        if ($asset->isModeSpecific() && !$asset->getModels()->isEmpty()) {
            $instances = $this->instanceTable->findByAsset($asset);

            if (!empty($instances)) {
                $anrIds = [];
                foreach ($instances as $instance) {
                    $anrId = $instance->getAnr()->getId();
                    $anrIds[$anrId] = $anrId;
                }

                $modelsIds = array_flip($newModelsIds);
                $models = $this->modelTable->findByAnrIds($anrIds);
                foreach ($models as $model) {
                    if (!isset($modelsIds[$model->getId()])) {
                        // Don't remove asset of specific model if it's linked to asset by an instance in anr by object.
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Enforces Amv to follow evolution.
     *
     * @param Model[] $models Models
     * @param Asset|null $asset Asset
     * @param Threat|null $threat Threat
     * @param Vulnerability|null $vulnerability Vulnerability
     */
    public function enforceAmvToFollow(
        $models,
        ?Asset $asset = null,
        ?Threat $threat = null,
        ?Vulnerability $vulnerability = null
    ): void {
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
     * @param Model[] $models The models in which the AMV link will be applicable
     * @param Asset|null $asset The asset
     * @param Threat|null $threat The threat
     * @param Vulnerability|null $vulnerability The vulnerability
     * @param bool $follow Whether the AMV link follows changes
     */
    public function checkAmvIntegrityLevel(
        array $models,
        ?AssetSuperClass $asset = null,
        ?ThreatSuperClass $threat = null,
        ?VulnerabilitySuperClass $vulnerability = null,
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
     * Generate an array ready for export
     *
     * @param Amv $amv The AMV entity to export
     * @param bool $withEval
     *
     * @return array The exported array
     */
    public function generateExportArray($amv, $anrId, $withEval = false)
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

    /**
     * Generate an array ready for export
     *
     * @param Amv $amv The AMV entity to export
     *
     * @return array The exported array
     */
    public function generateExportMospArray($amv, $anrId, $languageCode)
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
                                        $measures[$measureUuid]['label'] = $measure->{'getLabel' . $language}();
                                        $measures[$measureUuid]['category'] = $measure->getCategory(
                                        )->{'getLabel' . $language}();
                                        $measures[$measureUuid]['referential'] = $measure->getReferential()->getUuid();
                                        $measures[$measureUuid]['referential_label'] = $measure->getReferential(
                                        )->{'getLabel' . $language}();
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

    protected function historize(AmvSuperClass $amv, string $action, string $label, string $details)
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

            $amv = (new Amv())
                ->setAsset($asset)
                ->setThreat($threat)
                ->setVulnerability($vulnerability)
                ->setCreator($this->connectedUserService->getConnectedUser()->getEmail());
            $this->amvTable->save($amv);

            $createdItems[] = $amv->getUuid();
        }

        return $createdItems;
    }

    private function getOrCreateAssetObject(array $assetData): Asset
    {
        if (!empty($assetData['uuid'])) {
            try {
                return $this->assetTable->findByUuid($assetData['uuid']);
            } catch (EntityNotFoundException $e) {
            }
        }

        // TODO: move the creation to AssetService when its refactored.
        $asset = (new Asset())
            ->setLabels($assetData)
            ->setDescriptions($assetData)
            ->setCode($assetData['code'])
            ->setType($assetData['type'])
            ->setCreator($this->connectedUserService->getConnectedUser()->getEmail());

        $this->assetTable->save($asset);

        return $asset;
    }

    private function getOrCreateThreatObject(array $threatData): Threat
    {
        if (!empty($threatData['uuid'])) {
            try {
                return $this->threatTable->findByUuid($threatData['uuid']);
            } catch (EntityNotFoundException $e) {
            }
        }

        // TODO: move the creation ThreatService when its refactored.
        $threat = (new Threat())
            ->setCode($threatData['code'])
            ->setLabels($threatData)
            ->setDescriptions($threatData)
            ->setConfidentiality((int)$threatData['c'])
            ->setIntegrity((int)$threatData['i'])
            ->setAvailability((int)$threatData['a'])
            ->setCreator($this->connectedUserService->getConnectedUser()->getEmail());

        if (!empty($threatData['theme'])) {
            $labelKey = 'label1';
            $theme = $this->themeTable->findByLabel($labelKey, $threatData['theme'][$labelKey]);
            if ($theme === null) {
                $theme = new Theme();
                $theme->setLabels($threatData['theme']);
                $this->themeTable->save($theme, false);
            }
            $threat->setTheme($theme);
        }

        $this->themeTable->save($threat);

        return $threat;
    }

    private function getOrCreateVulnerabilityObject(array $vulnerabilityData): Vulnerability
    {
        if (!empty($vulnerabilityData['uuid'])) {
            try {
                return $this->vulnerabilityTable->findByUuid($vulnerabilityData['uuid']);
            } catch (EntityNotFoundException $e) {
            }
        }

        return $this->vulnerabilityService->create($vulnerabilityData);
    }

    public function prepareAmvDataResult(Amv $amv): array
    {
        $measures = [];
        foreach ($amv->getMeasures() as $measure) {
            $measures[] = [
                'label1' => $measure->getLabel(1),
                'label2' => $measure->getLabel(2),
                'label3' => $measure->getLabel(3),
                'label4' => $measure->getLabel(4),
            ];
        }

        return [
            'uuid' => $amv->getUuid(),
            'asset' => [
                'uuid' => $amv->getAsset()->getUuid(),
                'label1' => $amv->getAsset()->getLabel(1),
                'label2' => $amv->getAsset()->getLabel(2),
                'label3' => $amv->getAsset()->getLabel(3),
                'label4' => $amv->getAsset()->getLabel(4),
            ],
            'threat' => [
                'uuid' => $amv->getThreat()->getUuid(),
                'label1' => $amv->getThreat()->getLabel(1),
                'label2' => $amv->getThreat()->getLabel(2),
                'label3' => $amv->getThreat()->getLabel(3),
                'label4' => $amv->getThreat()->getLabel(4),
            ],
            'vulnerability' => [
                'uuid' => $amv->getVulnerability()->getUuid(),
                'label1' => $amv->getVulnerability()->getLabel(1),
                'label2' => $amv->getVulnerability()->getLabel(2),
                'label3' => $amv->getVulnerability()->getLabel(3),
                'label4' => $amv->getVulnerability()->getLabel(4),
            ],
            'measures' => $measures,
            'status' => $amv->getStatus(),
        ];
    }
}
