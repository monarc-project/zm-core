<?php

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Table\MonarcObjectTable;
use Monarc\Core\Table\ObjectCategoryTable;

class ObjectImportService
{
    /** @var AssetImportService */
    private $assetImportService;

    /** @var ObjectCategoryTable */
    private $objectCategoryTable;

    /** @var MonarcObjectTable */
    private $monarcObjectTable;

    public function __construct(
        AssetImportService $assetImportService,
        ObjectCategoryTable $objectCategoryTable,
        MonarcObjectTable $monarcObjectTable
    ) {
        $this->assetImportService = $assetImportService;
        $this->objectCategoryTable = $objectCategoryTable;
        $this->monarcObjectTable = $monarcObjectTable;
    }

    /**
     * TODO: Is going to be implemented later.
     */
    public function importFromMosp(array $data): ?MonarcObject
    {
        if (!isset($data['type'], $data['object']) || $data['type'] !== 'object') {
            return null;
        }

        $objectData = $data['object'];

        try {
            $this->monarcObjectTable->findByUuid($objectData['uuid']);

            throw new Exception(sprintf('The object with UUID "%s" already exists.', $objectData['uuid']));
        } catch (EntityNotFoundException $e) {
        }

        $asset = $this->assetImportService->importFromMosp($data['asset']);
        if ($asset === null) {
            return null;
        }

        $objectCategory = $this->objectCategoryTable->findById((int)$objectData['category']);

        $monarcObject = (new MonarcObject())
            ->setUuid($objectData['uuid'])
            ->setAsset($asset)
            ->setCategory($objectCategory)
            //->setRolfTag($rolfTag)
            ->setMode($objectData['mode'])
            ->setScope($objectData['scope'])
            ->setPosition($objectData['position']);

        return $monarcObject;
    }
}
