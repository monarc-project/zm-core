<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Helper\EncryptDecryptHelperTrait;
use Monarc\Core\Entity;
use Monarc\Core\Service;
use Monarc\Core\Table\MonarcObjectTable;

/** The service is used only on the BO side. */
class ObjectExportService
{
    use EncryptDecryptHelperTrait;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        private MonarcObjectTable $monarcObjectTable,
        private AssetExportService $assetExportService,
        private Service\ConfigService $configService,
        Service\ConnectedUserService $connectedUserService
    ) {
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
            throw new Exception('Object ID is required for the export operation.', 412);
        }

        $isForMosp = !empty($data['mosp']);
        /** @var Entity\MonarcObject $monarcObject */
        $monarcObject = $this->monarcObjectTable->findByUuid($data['id']);
        if ($isForMosp) {
            $languageIndex = $this->connectedUser->getLanguage();
            $languageCode = $this->configService->getLanguageCodes()[$languageIndex];
            $exportData = $this->prepareExportDataForMosp($monarcObject, $languageIndex, $languageCode);
        } else {
            $exportData = $this->prepareExportData($monarcObject);
        }

        $jsonResult = json_encode($exportData, JSON_THROW_ON_ERROR);

        return [
            'filename' => $this->generateExportFileName($monarcObject, $isForMosp),
            'content' => empty($data['password']) ? $jsonResult : $this->encrypt($jsonResult, $data['password']),
        ];
    }

    private function prepareExportData(Entity\MonarcObject $monarcObject): array
    {
        $rolfRisksData = $monarcObject->hasRolfTag() ? $this->prepareRolfRisksData($monarcObject->getRolfTag()) : [];

        return [
            'type' => 'object',
            'monarc_version' => $this->configService->getAppVersion()['appVersion'],
            'object' => array_merge([
                'uuid' => $monarcObject->getUuid(),
                'mode' => $monarcObject->getMode(),
                'scope' => $monarcObject->getScope(),
                'category' => $monarcObject->hasCategory() ? $monarcObject->getCategory()->getId() : null,
                'asset' => $monarcObject->getAsset()->getUuid(),
                'rolfTag' => $monarcObject->hasRolfTag() ? $monarcObject->getRolfTag()->getId() : null,
            ], $monarcObject->getLabels(), $monarcObject->getNames()),
            'categories' => $monarcObject->hasCategory()
                ? $this->prepareObjectCategoriesData($monarcObject->getCategory())
                : [],
            'asset' => $this->assetExportService->prepareExportData($monarcObject->getAsset()),
            'children' => $monarcObject->hasChildren() ? $this->prepareChildrenObjectsData($monarcObject) : [],
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

    private function prepareExportDataForMosp(
        Entity\MonarcObject $monarcObject,
        int $languageIndex,
        string $languageCode
    ): array {
        $rolfRisksData = [];
        if ($monarcObject->getRolfTag() !== null) {
            foreach ($monarcObject->getRolfTag()->getRisks() as $rolfRisk) {
                $measuresData = [];
                foreach ($rolfRisk->getMeasures() as $measure) {
                    $measuresData[] = [
                        'uuid' => $measure->getUuid(),
                        'code' => $measure->getCode(),
                        'label' => $measure->getLabel($languageIndex),
                        'category' => $measure->getCategory()?->getLabel($languageIndex),
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
        /** @var Entity\Asset $asset */
        $asset = $monarcObject->getAsset();

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
                    ->prepareExportDataForMosp($asset, $languageIndex, $languageCode),
                'children' => $monarcObject->hasChildren()
                    ? $this->prepareChildrenObjectsDataForMosp($monarcObject, $languageIndex, $languageCode)
                    : [],
                'rolfTag' => $monarcObject->getRolfTag() !== null ? [
                    'code' => $monarcObject->getRolfTag()->getCode(),
                    'label' => $monarcObject->getRolfTag()->getLabel($languageIndex),
                ] : null,
                'rolfRisks' => $rolfRisksData,
            ],
        ];
    }

    private function prepareChildrenObjectsData(Entity\MonarcObject $monarcObject): array
    {
        $result = [];
        foreach ($monarcObject->getChildrenLinks() as $childLink) {
            /** @var Entity\MonarcObject $childObject */
            $childObject = $childLink->getChild();
            $result[$childObject->getUuid()] = $this->prepareExportData($childObject);
        }

        return $result;
    }

    private function prepareChildrenObjectsDataForMosp(
        Entity\MonarcObject $monarcObject,
        int $languageIndex,
        string $languageCode
    ): array {
        $result = [];
        foreach ($monarcObject->getChildrenLinks() as $childLink) {
            $childObject = $childLink->getChild();
            $result[$childObject->getUuid()] = $this
                ->prepareExportDataForMosp($childObject, $languageIndex, $languageCode);
        }

        return $result;
    }

    private function prepareObjectCategoriesData(Entity\ObjectCategory $objectCategory): array
    {
        $result[$objectCategory->getId()] = array_merge([
            'id' => $objectCategory->getId(),
        ], $objectCategory->getLabels());
        if ($objectCategory->hasParent()) {
            $result = array_merge($result, $this->prepareObjectCategoriesData($objectCategory->getParent()));
        }

        return $result;
    }

    private function prepareRolfRisksData(Entity\RolfTag $rolfTag): array
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
                    'category' => $measure->getCategory() !== null ? array_merge([
                        'id' => $measure->getCategory()->getId(),
                    ], $measure->getCategory()->getLabels()) : null,
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
        $objectName = $monarcObject->getName($this->connectedUser->getLanguage());

        return preg_replace(
            '/[^a-z0-9._-]+/i',
            '',
            (empty($objectName) ? $monarcObject->getUuid() : $objectName) . ($isForMosp ? '_MOSP' : '')
        );
    }
}
