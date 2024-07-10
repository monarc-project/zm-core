<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export;

use Monarc\Core\Entity\Asset;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Helper\EncryptDecryptHelperTrait;
use Monarc\Core\Entity;
use Monarc\Core\Service;
use Monarc\Core\Table\MonarcObjectTable;

/** The service is used only on the BO side. */
class ObjectExportService
{
    use EncryptDecryptHelperTrait;
    use Service\Export\Traits\ObjectExportTrait;

    private Entity\UserSuperClass $connectedUser;

    public function __construct(
        private MonarcObjectTable $monarcObjectTable,
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

    /** The method is called also from the FrontOffice\ObjectImportService::importFromCommonDatabase. */
    public function prepareExportData(Entity\MonarcObject $object): array
    {
        return $this->prepareObjectData($object);
    }

    /** Prepare export data to be published on MOSP. */
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
                'asset' => $this->prepareAssetExportDataForMosp($asset, $languageIndex, $languageCode),
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

    private function prepareChildrenObjectsDataForMosp(
        Entity\MonarcObject $monarcObject,
        int $languageIndex,
        string $languageCode
    ): array {
        $result = [];
        foreach ($monarcObject->getChildrenLinks() as $childLink) {
            /** @var Entity\MonarcObject $childObject */
            $childObject = $childLink->getChild();
            $result[$childObject->getUuid()] = $this
                ->prepareExportDataForMosp($childObject, $languageIndex, $languageCode);
        }

        return $result;
    }

    public function prepareAssetExportDataForMosp(Asset $asset, int $languageIndex, string $languageCode): array
    {
        $assetData = [
            'asset' => [
                'uuid' => $asset->getUuid(),
                'label' => $asset->getLabel($languageIndex),
                'description' => $asset->getDescription($languageIndex),
                'type' => $asset->getTypeName(),
                'code' => $asset->getCode(),
                'language' => $languageCode,
                'version' => 1,
            ],
            'amvs' => [],
            'threats' => [],
            'vuls' => [],
            'measures' => [],
        ];

        foreach ($asset->getAmvs() as $amv) {
            $amvResult = $this->prepareAmvExportDataForMosp($amv, $languageIndex, $languageCode);
            $assetData['amvs'] += $amvResult['amv'];
            $assetData['threats'] += $amvResult['threat'];
            $assetData['vuls'] += $amvResult['vulnerability'];
            $assetData['measures'] += $amvResult['measures'];
        }
        $assetData['amvs'] = array_values($assetData['amvs']);
        $assetData['threats'] = array_values($assetData['threats']);
        $assetData['vuls'] = array_values($assetData['vuls']);
        $assetData['measures'] = array_values($assetData['measures']);

        return $assetData;
    }

    public function prepareAmvExportDataForMosp(Entity\Amv $amv, int $languageIndex, string $languageCode): array
    {
        $measuresData = [];
        foreach ($amv->getMeasures() as $measure) {
            $measureUuid = $measure->getUuid();
            $measuresData[] = [
                'uuid' => $measureUuid,
                'code' => $measure->getCode(),
                'label' => $measure->getLabel($languageIndex),
                'category' => $measure->getCategory()?->getLabel($languageIndex),
                'referential' => $measure->getReferential()->getUuid(),
                'referential_label' => $measure->getReferential()->getLabel($languageIndex),
            ];
        }
        $threat = $amv->getThreat();
        $vulnerability = $amv->getVulnerability();

        return [
            'amv' => [
                $amv->getUuid() => [
                    'uuid' => $amv->getUuid(),
                    'asset' => $amv->getAsset()->getUuid(),
                    'threat' => $threat->getUuid(),
                    'vulnerability' => $vulnerability->getUuid(),
                    'measures' => array_keys($measuresData),
                ],
            ],
            'threat' => [
                $threat->getUuid() => [
                    'uuid' => $threat->getUuid(),
                    'label' => $threat->getLabel($languageIndex),
                    'description' => $threat->getDescription($languageIndex),
                    'theme' => $threat->getTheme() !== null
                        ? $threat->getTheme()->getLabel($languageIndex)
                        : '',
                    'code' => $threat->getCode(),
                    'c' => (bool)$threat->getConfidentiality(),
                    'i' => (bool)$threat->getIntegrity(),
                    'a' => (bool)$threat->getAvailability(),
                    'language' => $languageCode,
                ],
            ],
            'vulnerability' => [
                $vulnerability->getUuid() => [
                    'uuid' => $vulnerability->getUuid(),
                    'code' => $vulnerability->getCode(),
                    'label' => $vulnerability->getLabel($languageIndex),
                    'description' => $vulnerability->getDescription($languageIndex),
                    'language' => $languageCode,
                ],
            ],
            'measures' => $measuresData,
        ];
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
