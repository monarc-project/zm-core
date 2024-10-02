<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export\Traits;

use Monarc\Core\Entity;

trait ThreatExportTrait
{
    private function prepareThreatData(Entity\Threat $threat): array
    {
        return array_merge($threat->getLabels(), $threat->getDescriptions(), [
            'uuid' => $threat->getUuid(),
            'theme' => $threat->getTheme() !== null
                ? array_merge(['id' => $threat->getTheme()->getId()], $threat->getTheme()->getLabels())
                : null,
            'status' => $threat->getStatus(),
            'mode' => $threat->getMode(),
            'code' => $threat->getCode(),
            'confidentiality' => $threat->getConfidentiality(),
            'integrity' => $threat->getIntegrity(),
            'availability' => $threat->getAvailability(),
            'trend' => 0,
            'comment' => '',
            'qualification' => -1,
        ]);
    }
}
