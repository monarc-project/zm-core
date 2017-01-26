<?php
namespace MonarcCore\Service;

/**
 * Asset Export Service Factory
 *
 * Class AssetExportServiceFactory
 * @package MonarcCore\Service
 */
class AssetExportServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\AssetTable',
        'entity' => 'MonarcCore\Model\Entity\Asset',
        'amvService' => 'MonarcCore\Service\AmvService',
    ];
}
