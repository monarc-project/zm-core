<?php
namespace MonarcCore\Service;

class ModelObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ObjectTable',
        'entity' => 'MonarcCore\Model\Entity\Object',

        'assetTable'=> '\MonarcCore\Model\Table\AssetTable',
        'categoryTable'=> '\MonarcCore\Model\Table\ObjectCategoryTable',
        'rolfTagTable'=> '\MonarcCore\Model\Table\RolfTagTable',
        'sourceTable' => 'MonarcCore\Model\Table\ObjectTable',
        'modelTable' => 'MonarcCore\Model\Table\ModelTable',
    );
}
