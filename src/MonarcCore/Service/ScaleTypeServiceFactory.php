<?php
namespace MonarcCore\Service;

class ScaleTypeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ScaleTypeTable',
        'entity' => 'MonarcCore\Model\Entity\ScaleType',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
    );
}
