<?php
namespace MonarcCore\Service;

class AssetServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> 'MonarcCore\Model\Table\AssetTable',
        'entity'=> 'MonarcCore\Model\Entity\Asset',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'modelTable' => 'MonarcCore\Model\Table\ModelTable',
        'amvService' => 'MonarcCore\Service\AmvService',
        'modelService' => 'MonarcCore\Service\ModelService',
        'objectService' => 'MonarcCore\Service\ObjectService',
    );
}

