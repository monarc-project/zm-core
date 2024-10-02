<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Export\Traits;

use Monarc\Core\Entity;

trait AssetExportTrait
{
    private function prepareAssetData(Entity\Asset $asset): array
    {
        return array_merge($asset->getLabels(), $asset->getDescriptions(), [
            'uuid' => $asset->getUuid(),
            'code' => $asset->getCode(),
            'type' => $asset->getType(),
            'status' => $asset->getStatus(),
        ]);
    }
}
