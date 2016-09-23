<?php
namespace MonarcCore\Service;

class ObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectTable',
        'entity'=> '\MonarcCore\Model\Entity\Object',
        'assetTable'=> '\MonarcCore\Model\Table\AssetTable',
        'anrTable'=> '\MonarcCore\Model\Table\AnrTable',
        'amvTable'=> '\MonarcCore\Model\Table\AmvTable',
        'categoryTable'=> '\MonarcCore\Model\Table\ObjectCategoryTable',
        'instanceTable'=> '\MonarcCore\Model\Table\InstanceTable',
        'rolfTagTable'=> '\MonarcCore\Model\Table\RolfTagTable',
        'modelService'=> 'MonarcCore\Service\ModelService',
        'objectObjectService'=> 'MonarcCore\Service\ObjectObjectService',
    );

}
