<?php
namespace MonarcCore\Service;

/**
 * Asset Service Factory
 *
 * Class AssetServiceFactory
 * @package MonarcCore\Service
 */
class AssetServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\AssetTable',
        'entity' => 'MonarcCore\Model\Entity\Asset',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'modelTable' => 'MonarcCore\Model\Table\ModelTable',
        'amvService' => 'MonarcCore\Service\AmvService',
        'modelService' => 'MonarcCore\Service\ModelService',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'objectObjectTable' => 'MonarcCore\Model\Table\ObjectObjectTable',
        'assetExportService' => 'MonarcCore\Service\AssetExportService',
    ];
}