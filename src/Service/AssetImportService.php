<?php

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\Asset;
use Monarc\Core\Model\Table\AssetTable;

class AssetImportService
{
    /** @var AssetTable */
    private $assetTable;

    public function __construct(AssetTable $assetTable)
    {
        $this->assetTable = $assetTable;
    }

    /**
     * TODO: It's currently not used. As the objects import concept from MOSP is not stabilized on BO side.
     */
    public function importFromMosp(array $data): ?Asset
    {
        if (!isset($data['type']) || $data['type'] !== 'asset') {
            return null;
        }

        $asset = $this->assetTable->findByUuid($data['asset']['uuid']);
        // TODO: validate if the code exists.
        if ($asset === null) {
            $asset = (new Asset())
                ->setUuid($data['asset']['uuid'])
                ->setLabels($data['asset'])
                ->setDescriptions($data['asset'])
                ->setStatus($data['asset']['status'] ?? 1)
                ->setMode($data['asset']['mode'] ?? 0)
                ->setType($data['asset']['type'])
                ->setCode($data['asset']['code']);

            $this->assetTable->saveEntity($asset);
        }

        return $asset;
    }
}
