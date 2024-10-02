<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export\Traits;

use Monarc\Core\Entity;

trait InformationRiskExportTrait
{
    use MeasureExportTrait;
    use AssetExportTrait;
    use ThreatExportTrait;
    use VulnerabilityExportTrait;

    private function prepareInformationRiskData(Entity\Amv $amv): array
    {
        /** @var Entity\Asset $asset */
        $asset = $amv->getAsset();
        /** @var Entity\Threat $threat */
        $threat = $amv->getThreat();
        /** @var Entity\Vulnerability $vulnerability */
        $vulnerability = $amv->getVulnerability();

        $measuresData = [];
        foreach ($amv->getMeasures() as $measure) {
            $measuresData[] = $this->prepareMeasureData($measure);
        }

        return [
            'uuid' => $amv->getUuid(),
            'asset' => $this->prepareAssetData($asset),
            'threat' => $this->prepareThreatData($threat),
            'vulnerability' => $this->prepareVulnerabilityData($vulnerability),
            'measures' => $measuresData,
            'status' => $amv->getStatus(),
        ];
    }
}
