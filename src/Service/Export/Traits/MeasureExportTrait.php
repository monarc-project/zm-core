<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export\Traits;

use Monarc\Core\Entity;

trait MeasureExportTrait
{
    private function prepareMeasureData(Entity\Measure $measure): array
    {
        return array_merge($measure->getLabels(), [
            'uuid' => $measure->getUuid(),
            'code' => $measure->getCode(),
            'referential' => array_merge([
                'uuid' => $measure->getReferential()->getUuid(),
            ], $measure->getReferential()->getLabels()),
            'category' => $measure->getCategory()?->getLabels(),
        ]);
    }
}
