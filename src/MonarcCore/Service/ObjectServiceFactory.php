<?php
namespace MonarcCore\Service;

class ObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectTable',
        'entity'=> '\MonarcCore\Model\Entity\Object',
        'assetTable'=> '\MonarcCore\Model\Table\AssetTable',
        'amvTable'=> '\MonarcCore\Model\Table\AmvTable',
        'objectRiskTable' => '\MonarcCore\Model\Table\ObjectRiskTable',
        'categoryTable'=> '\MonarcCore\Model\Table\ObjectCategoryTable',
        'rolfTagTable'=> '\MonarcCore\Model\Table\RolfTagTable',
        'modelService'=> 'MonarcCore\Service\ModelService',
        'riskEntity' => '\MonarcCore\Model\Entity\ObjectRisk',
        'objectObjectService'=> 'MonarcCore\Service\ObjectObjectService',
        'objectRiskService'=> 'MonarcCore\Service\ObjectRiskService',
    );

}
