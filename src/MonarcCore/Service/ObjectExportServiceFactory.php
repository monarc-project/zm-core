<?php
namespace MonarcCore\Service;

class ObjectExportServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectTable',
        'entity'=> '\MonarcCore\Model\Entity\Object',
        'assetExportService' => 'MonarcCore\Service\AssetExportService',
        'objectObjectService' => 'MonarcCore\Service\ObjectObjectService',
        'categoryTable' => '\MonarcCore\Model\Table\ObjectCategoryTable',
    );

}
