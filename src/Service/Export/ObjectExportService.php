<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Helper\EncryptDecryptHelperTrait;
use Monarc\Core\Model\Entity;
use Monarc\Core\Service;
use Monarc\Core\Table\MonarcObjectTable;

/** The service is used only on the BO side. */
class ObjectExportService
{
    use EncryptDecryptHelperTrait;

    private MonarcObjectTable $monarcObjectTable;

    private AssetExportService $assetExportService;

    private Service\ConfigService $configService;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        MonarcObjectTable $monarcObjectTable,
        AssetExportService $assetExportService,
        Service\ConfigService $configService,
        Service\ConnectedUserService $connectedUserService
    ) {
        $this->monarcObjectTable = $monarcObjectTable;
        $this->assetExportService = $assetExportService;
        $this->configService = $configService;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    /**
     * @return array Result contains:
     * [
     *     'filename' => {the generated filename},
     *     'content' => {json encoded string, encrypted if password is set}
     * ]
     */
    public function export(array $data): array
    {
        if (empty($data['id'])) {
            throw new Exception('Object to export is required', 412);
        }

        $isForMosp = !empty($data['mosp']);
        /** @var Entity\MonarcObject $monarcObject */
        $monarcObject = $this->monarcObjectTable->findByUuid($data['id']);
        if ($isForMosp) {
            $languageIndex = $this->connectedUser->getLanguage();
            $languageCode = $this->configService->getLanguageCodes()[$languageIndex];
            $exportArray = $this->generateExportMospArray($monarcObject, $languageIndex, $languageCode);
        } else {
            $exportArray = $this->generateExportArray($monarcObject);
        }

        $content = json_encode($exportArray, JSON_THROW_ON_ERROR);

        return [
            'filename' => $this->generateExportFileName($monarcObject, $isForMosp),
            'content' => empty($data['password']) ? $content : $this->encrypt($content, $data['password']),
        ];
    }

    public function generateExportArray(Entity\MonarcObject $monarcObject): array
    {
        $rolfRisksData = $monarcObject->hasRolfTag()
            ? $this->generateObjectRolfRisksData($monarcObject->getRolfTag())
            : [];

        return [
            'type' => 'object',
            'monarc_version' => $this->configService->getAppVersion()['appVersion'],
            'object' => array_merge([
                'uuid' => $monarcObject->getUuid(),
                'mode' => $monarcObject->getMode(),
                'scope' => $monarcObject->getScope(),
                'position' => $monarcObject->getPosition(),
                'category' => $monarcObject->hasCategory() ? $monarcObject->getCategory()->getId() : null,
                'asset' => $monarcObject->getAsset()->getUuid(),
                'rolfTag' => $monarcObject->hasRolfTag() ? $monarcObject->getRolfTag()->getId() : null,
            ], $monarcObject->getLabels(), $monarcObject->getNames()),
            'categories' => $monarcObject->hasCategory()
                ? $this->generateObjectCategoriesData($monarcObject->getCategory())
                : [],
            'asset' => $this->assetExportService->generateExportArray($monarcObject->getAsset()),
            'children' => $monarcObject->hasChildren() ? $this->generateChildrenObjectsData($monarcObject) : [],
            'rolfTags' => $monarcObject->hasRolfTag() ? [
                $monarcObject->getRolfTag()->getId() => array_merge([
                    'id' => $monarcObject->getRolfTag()->getId(),
                    'code' => $monarcObject->getRolfTag()->getCode(),
                    'risks' => array_keys($rolfRisksData),
                ], $monarcObject->getRolfTag()->getLabels()),
            ] : [],
            'rolfRisks' => $rolfRisksData,
        ];
    }

    public function generateExportMospArray(
        Entity\MonarcObject $monarcObject,
        int $languageIndex,
        string $languageCode
    ): array {
        $rolfRisksData = [];
        if ($monarcObject->hasRolfTag()) {
            foreach ($monarcObject->getRolfTag()->getRisks() as $rolfRisk) {
                $measuresData = [];
                foreach ($rolfRisk->getMeasures() as $measure) {
                    $measuresData[] = [
                        'uuid' => $measure->getUuid(),
                        'code' => $measure->getCode(),
                        'label' => $measure->getLabel($languageIndex),
                        'category' => $measure->getCategory()->getLabel($languageIndex),
                        'referential' => $measure->getReferential()->getUuid(),
                        'referential_label' => $measure->getReferential()->getLabel($languageIndex),
                    ];
                }
                $rolfRisksData[] = [
                    'code' => $rolfRisk->getCode(),
                    'label' => $rolfRisk->getLabel($languageIndex),
                    'description' => $rolfRisk->getDescription($languageIndex),
                    'measures' => $measuresData,
                ];
            }
        }

        return [
            'object' => [
                'object' => [
                    'uuid' => $monarcObject->getUuid(),
                    'name' => $monarcObject->getName($languageIndex),
                    'label' => $monarcObject->getLabel($languageIndex),
                    'scope' => $monarcObject->getScopeName(),
                    'language' => $languageCode,
                    'version' => 1,
                ],
                'asset' => $this->assetExportService
                    ->generateExportMospArray($monarcObject->getAsset(), $languageIndex, $languageCode),
                'children' => $monarcObject->hasChildren()
                    ? $this->generateChildrenObjectsDataForMosp($monarcObject, $languageIndex, $languageCode)
                    : [],
                'rolfTags' => $monarcObject->hasRolfTag() ? [[
                    'code' => $monarcObject->getRolfTag()->getCode(),
                    'label' => $monarcObject->getRolfTag()->getLabel($languageIndex),
                ]] : [],
                'rolfRisks' => $rolfRisksData,
            ],
        ];
    }

    private function generateChildrenObjectsData(Entity\MonarcObject $monarcObject): array
    {
        $result = [];
        foreach ($monarcObject->getChildrenLinks() as $childLink) {
            $childObject = $childLink->getChild();
            $result[$childObject->getUuid()] = $this->generateExportArray($childObject);
        }

        return $result;
    }

    private function generateChildrenObjectsDataForMosp(
        Entity\MonarcObject $monarcObject,
        int $languageIndex,
        string $languageCode
    ): array {
        $result = [];
        foreach ($monarcObject->getChildrenLinks() as $childLink) {
            $childObject = $childLink->getChild();
            $result[$childObject->getUuid()] = $this
                ->generateExportMospArray($childObject, $languageIndex, $languageCode);
        }

        return $result;
    }

    private function generateObjectCategoriesData(Entity\ObjectCategory $objectCategory): array
    {
        $result[$objectCategory->getId()] = array_merge([
            'id' => $objectCategory->getId(),
        ], $objectCategory->getLabels());
        if ($objectCategory->hasParent()) {
            $result = array_merge($result, $this->generateObjectCategoriesData($objectCategory->getParent()));
        }

        return $result;
    }

    private function generateObjectRolfRisksData(Entity\RolfTag $rolfTag): array
    {
        $rolfRisksData = [];
        foreach ($rolfTag->getRisks() as $rolfRisk) {
            $rolfRiskId = $rolfRisk->getId();
            $measuresData = [];
            foreach ($rolfRisk->getMeasures() as $measure) {
                $measureUuid = $measure->getUuid();
                $measuresData[$measureUuid] = array_merge([
                    'uuid' => $measureUuid,
                    'code' => $measure->getCode(),
                    'referential' => array_merge([
                        'uuid' => $measure->getReferential()->getUuid(),
                    ], $measure->getReferential()->getLabels()),
                    'category' => array_merge([
                        'id' => $measure->getCategory()->getId(),
                        'status' => $measure->getCategory()->getStatus(),
                    ], $measure->getCategory()->getLabels()),
                ], $measure->getLabels());
            }

            $rolfRisksData[$rolfRiskId] = array_merge([
                'id' => $rolfRiskId,
                'code' => $rolfRisk->getCode(),
                'measures' => $measuresData,
            ], $rolfRisk->getLabels(), $rolfRisk->getDescriptions());
        }

        return $rolfRisksData;
    }

    private function generateExportFileName(Entity\MonarcObject $monarcObject, bool $isForMosp = false): string
    {
        return preg_replace(
            '/[^a-z0-9._-]+/i',
            '',
            $monarcObject->getName($this->connectedUser->getLanguage()) . ($isForMosp ? '_MOSP' : '')
        );
    }
}
