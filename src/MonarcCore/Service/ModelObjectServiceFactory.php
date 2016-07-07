<?php
namespace MonarcCore\Service;

class ModelObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ObjectTable',
        'entity' => 'MonarcCore\Model\Entity\Object',
    );
}
