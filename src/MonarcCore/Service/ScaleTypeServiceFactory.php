<?php
namespace MonarcCore\Service;

class ScaleTypeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ScaleTypeTable',
        'entity' => 'MonarcCore\Model\Entity\ScaleType',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
    );
}
