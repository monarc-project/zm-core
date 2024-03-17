<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export;

use Monarc\Core\Entity\Asset;

class AssetExportService
{
    private AmvExportService $amvExportService;

    public function __construct(AmvExportService $amvExportService)
    {
        $this->amvExportService = $amvExportService;
    }

    public function generateExportArray(Asset $asset, bool $withEval = false): array
    {
        $assetData = [
            'type' => 'asset',
            'asset' => array_merge($asset->getLabels(), $asset->getDescriptions(), [
                'uuid' => $asset->getUuid(),
                'status' => $asset->getStatus(),
                'mode' => $asset->getMode(),
                'type' => $asset->getType(),
                'code' => $asset->getCode(),
            ]),
            'amvs' => [],
            'threats' => [],
            'themes' => [],
            'vuls' => [],
            'measures' => [],
        ];

        foreach ($asset->getAmvs() as $amv) {
            $amvResult = $this->amvExportService->generateExportArray($amv, $withEval);
            $assetData['amvs'] += $amvResult['amv'];
            $assetData['threats'] += $amvResult['threat'];
            $assetData['themes'] += $amvResult['theme'];
            $assetData['vuls'] += $amvResult['vulnerability'];
            $assetData['measures'] += $amvResult['measures'];
        }

        return $assetData;
    }

    public function generateExportMospArray(Asset $asset, int $languageIndex, string $languageCode): array
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
            $amvResult = $this->amvExportService->generateExportMospArray($amv, $languageIndex, $languageCode);
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
}
