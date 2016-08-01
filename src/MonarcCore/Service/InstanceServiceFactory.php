<?php
namespace MonarcCore\Service;

class InstanceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\InstanceTable',
        'entity' => 'MonarcCore\Model\Entity\Instance',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'assetTable' => 'MonarcCore\Model\Table\AssetTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'objectObjectService' => 'MonarcCore\Service\ObjectObjectService',
    );
}
