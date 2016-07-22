<?php
namespace MonarcCore\Service;

class ObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectTable',
        'assetTable'=> '\MonarcCore\Model\Table\AssetTable',
        'amvTable'=> '\MonarcCore\Model\Table\AmvTable',
        'objectRiskTable' => '\MonarcCore\Model\Table\ObjectRiskTable',
        'categoryTable'=> '\MonarcCore\Model\Table\ObjectCategoryTable',
        'rolfTagTable'=> '\MonarcCore\Model\Table\RolfTagTable',
        'entity'=> '\MonarcCore\Model\Entity\Object',
        'objectObjectService'=> 'MonarcCore\Service\ObjectObjectService',
    );

}
