<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Table\AssetTable;

class AssetImportService
{
    private AssetTable $assetTable;

    private AssetService $assetService;

    public function __construct(AssetTable $assetTable, AssetService $assetService)
    {
        $this->assetTable = $assetTable;
        $this->assetService = $assetService;
    }

    /**
     * TODO: It's currently not used. As the objects import concept from MOSP is not stabilized on BO side.
     */
    public function importFromMosp(array $data): ?Asset
    {
        if (!isset($data['type']) || $data['type'] !== 'asset') {
            return null;
        }

        return $this->assetTable->findByUuid($data['asset']['uuid']) ??
            $this->assetService->create($data['asset']);
    }
}
