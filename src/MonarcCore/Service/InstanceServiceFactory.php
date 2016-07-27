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
        'objectObjectService' => 'MonarcCore\Service\ObjectObjectService',
    );
}
