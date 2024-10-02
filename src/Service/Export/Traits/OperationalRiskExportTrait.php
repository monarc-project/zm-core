<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export\Traits;

use Monarc\Core\Entity;

trait OperationalRiskExportTrait
{
    use MeasureExportTrait;

    private function prepareOperationalRiskData(Entity\RolfRisk $rolfRisk): array
    {
        $measuresData = [];
        foreach ($rolfRisk->getMeasures() as $measure) {
            $measuresData[] = $this->prepareMeasureData(
                $measure,
            );
        }
        $rolfTagsData = [];
        foreach ($rolfRisk->getTags() as $rolfTag) {
            $rolfTagsData[] = array_merge(['code' => $rolfTag->getCode()], $rolfTag->getLabels());
        }

        return array_merge($rolfRisk->getLabels(), $rolfRisk->getDescriptions(), [
            'id' => $rolfRisk->getId(),
            'code' => $rolfRisk->getCode(),
            'rolfTags' => $rolfTagsData,
            'measures' => $measuresData,
        ]);
    }
}
