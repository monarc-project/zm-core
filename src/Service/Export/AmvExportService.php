<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export;

use Monarc\Core\Entity;

class AmvExportService
{
    /**
     * Currently it can be called from FO (library object export and instance export) and BO (library object export).
     */
    public function prepareExportData(Entity\Amv $amv, bool $withEval = false): array
    {
        $measuresData = [];
        foreach ($amv->getMeasures() as $measure) {
            $measureUuid = $measure->getUuid();
            $measuresData[$measureUuid] = array_merge([
                'uuid' => $measureUuid,
                'code' => $measure->getCode(),
                'referential' => array_merge([
                    'uuid' => $measure->getReferential()->getUuid(),
                ], $measure->getReferential()->getLabels()),
                'category' => $measure->getCategory() !== null ? array_merge([
                    'id' => $measure->getCategory()->getId(),
                    'status' => $measure->getCategory()->getStatus(),
                ], $measure->getCategory()->getLabels()) : null,
                'status' => $measure->getStatus(),
            ], $measure->getLabels());
        }

        $themeData = [];
        $threat = $amv->getThreat();
        if ($threat->getTheme() !== null) {
            $themeData[$threat->getTheme()->getId()] = array_merge([
                'id' => $threat->getTheme()->getId(),
            ], $threat->getTheme()->getLabels());
        }
        $threatEvaluations = [];
        if ($withEval) {
            $threatEvaluations = [
                'trend' => $threat->getTrend(),
                'comment' => $threat->getComment(),
                'qualification' => $threat->getQualification(),
            ];
        }
        $vulnerability = $amv->getVulnerability();

        return [
            'amv' => [
                $amv->getUuid() => [
                    'uuid' => $amv->getUuid(),
                    'asset' => $amv->getAsset()->getUuid(),
                    'threat' => $threat->getUuid(),
                    'vulnerability' => $vulnerability->getUuid(),
                    'measures' => array_keys($measuresData),
                    'status' => $amv->getStatus(),
                ],
            ],
            'threat' => [
                $threat->getUuid() => array_merge([
                    'uuid' => $threat->getUuid(),
                    'theme' => $threat->getTheme()?->getId(),
                    'status' => $threat->getStatus(),
                    'mode' => $threat->getMode(),
                    'code' => $threat->getCode(),
                    'c' => $threat->getConfidentiality(),
                    'i' => $threat->getIntegrity(),
                    'a' => $threat->getAvailability(),
                ], $threat->getLabels(), $threat->getDescriptions(), $threatEvaluations),
            ],
            'theme' => $themeData,
            'vulnerability' => [
                $vulnerability->getUuid() => array_merge([
                    'uuid' => $vulnerability->getUuid(),
                    'status' => $vulnerability->getStatus(),
                    'mode' => $vulnerability->getMode(),
                    'code' => $vulnerability->getCode(),
                ], $vulnerability->getLabels(), $vulnerability->getDescriptions()),
            ],
            'measures' => $measuresData,
        ];
    }

    public function prepareExportDataForMosp(Entity\Amv $amv, int $languageIndex, string $languageCode): array
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
}
