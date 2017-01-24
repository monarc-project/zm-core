<?php
namespace MonarcCore\Service;

class AssetExportServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\AssetTable',
        'entity' => 'MonarcCore\Model\Entity\Asset',
        'amvService' => 'MonarcCore\Service\AmvService',
    );
}
