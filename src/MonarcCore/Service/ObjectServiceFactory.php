<?php
namespace MonarcCore\Service;

class ObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectTable',
        'entity'=> '\MonarcCore\Model\Entity\Object',
        'anrObjectCategoryEntity' => 'MonarcCore\Model\Entity\AnrObjectCategory',
        'assetTable'=> '\MonarcCore\Model\Table\AssetTable',
        'anrTable'=> '\MonarcCore\Model\Table\AnrTable',
        'anrObjectCategoryTable'=> '\MonarcCore\Model\Table\AnrObjectCategoryTable',
        'amvTable'=> '\MonarcCore\Model\Table\AmvTable',
        'categoryTable'=> '\MonarcCore\Model\Table\ObjectCategoryTable',
        'instanceTable'=> '\MonarcCore\Model\Table\InstanceTable',
        'modelTable'=> '\MonarcCore\Model\Table\ModelTable',
        'objectObjectTable'=> '\MonarcCore\Model\Table\ObjectObjectTable',
        'rolfTagTable'=> '\MonarcCore\Model\Table\RolfTagTable',
        'modelService'=> 'MonarcCore\Service\ModelService',
        'objectObjectService'=> 'MonarcCore\Service\ObjectObjectService',
    );

}
