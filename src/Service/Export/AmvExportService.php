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
    public function generateExportArray(Entity\AmvSuperClass $amv, bool $withEval = false): array
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
                'category' => array_merge([
                    'id' => $measure->getCategory()->getId(),
                    'status' => $measure->getCategory()->getStatus(),
                ], $measure->getCategory()->getLabels()),
                'status' => $measure->getStatus(),
            ], $measure->getLabels());
        }

        $themeData = [];
        if ($amv->getThreat()->getTheme() !== null) {
            $themeData[$amv->getThreat()->getTheme()->getId()] = array_merge([
                'id' => $amv->getThreat()->getTheme()->getId(),
            ], $amv->getThreat()->getTheme()->getLabels());
        }
        $threatEvaluations = [];
        if ($withEval) {
            $threatEvaluations = [
                'trend' => $amv->getThreat()->getTrend(),
                'comment' => $amv->getThreat()->getComment(),
                'qualification' => $amv->getThreat()->getQualification(),
            ];
        }

        return [
            'amv' => [
                $amv->getUuid() => [
                    'uuid' => $amv->getUuid(),
                    'asset' => $amv->getAsset()->getUuid(),
                    'threat' => $amv->getThreat()->getUuid(),
                    'vulnerability' => $amv->getVulnerability()->getUuid(),
                    'measures' => array_keys($measuresData),
                    'status' => $amv->getStatus(),
                ],
            ],
            'threat' => [
                $amv->getThreat()->getUuid() => array_merge([
                    'uuid' => $amv->getThreat()->getUuid(),
                    'theme' => $amv->getThreat()->getTheme() !== null ? $amv->getThreat()->getTheme()->getId() : null,
                    'status' => $amv->getThreat()->getStatus(),
                    'mode' => $amv->getThreat()->getMode(),
                    'code' => $amv->getThreat()->getCode(),
                    'c' => $amv->getThreat()->getConfidentiality(),
                    'i' => $amv->getThreat()->getIntegrity(),
                    'a' => $amv->getThreat()->getAvailability(),
                ], $amv->getThreat()->getLabels(), $amv->getThreat()->getDescriptions(), $threatEvaluations),
            ],
            'theme' => $themeData,
            'vulnerability' => [
                $amv->getVulnerability()->getUuid() => array_merge([
                    'uuid' => $amv->getVulnerability()->getUuid(),
                    'status' => $amv->getVulnerability()->getStatus(),
                    'mode' => $amv->getVulnerability()->getMode(),
                    'code' => $amv->getVulnerability()->getCode(),
                ], $amv->getVulnerability()->getLabels(), $amv->getVulnerability()->getDescriptions()),
            ],
            'measures' => $measuresData,
        ];
    }

    public function generateExportMospArray(Entity\AmvSuperClass $amv, int $languageIndex, string $languageCode): array
    {
        $measuresData = [];
        foreach ($amv->getMeasures() as $measure) {
            $measureUuid = $measure->getUuid();
            $measuresData[] = [
                'uuid' => $measureUuid,
                'code' => $measure->getCode(),
                'label' => $measure->getLabel($languageIndex),
                'category' => $measure->getCategory()->getLabel($languageIndex),
                'referential' => $measure->getReferential()->getUuid(),
                'referential_label' => $measure->getReferential()->getLabel($languageIndex),
            ];
        }

        return [
            'amv' => [
                $amv->getUuid() => [
                    'uuid' => $amv->getUuid(),
                    'asset' => $amv->getAsset()->getUuid(),
                    'threat' => $amv->getThreat()->getUuid(),
                    'vulnerability' => $amv->getVulnerability()->getUuid(),
                    'measures' => array_keys($measuresData),
                ],
            ],
            'threat' => [
                $amv->getThreat()->getUuid() => [
                    'uuid' => $amv->getThreat()->getUuid(),
                    'label' => $amv->getThreat()->getLabel($languageIndex),
                    'description' => $amv->getThreat()->getDescription($languageIndex),
                    'theme' => $amv->getThreat()->getTheme() !== null
                        ? $amv->getThreat()->getTheme()->getLabel($languageIndex)
                        : '',
                    'code' => $amv->getThreat()->getCode(),
                    'c' => (bool)$amv->getThreat()->getConfidentiality(),
                    'i' => (bool)$amv->getThreat()->getIntegrity(),
                    'a' => (bool)$amv->getThreat()->getAvailability(),
                    'language' => $languageCode,
                ],
            ],
            'vulnerability' => [
                $amv->getVulnerability()->getUuid() => [
                    'uuid' => $amv->getVulnerability()->getUuid(),
                    'code' => $amv->getVulnerability()->getCode(),
                    'label' => $amv->getVulnerability()->getLabel($languageIndex),
                    'description' => $amv->getVulnerability()->getDescription($languageIndex),
                    'language' => $languageCode,
                ],
            ],
            'measures' => $measuresData,
        ];
    }
}
