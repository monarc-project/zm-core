<?php
namespace MonarcCore\Service;

class AnrObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'             => 'MonarcCore\Model\Table\ObjectTable',
        'entity'            => 'MonarcCore\Model\Entity\Object',
        'objectObjectTable' => 'MonarcCore\Model\Table\ObjectObjectTable',
        'objectService'     => 'MonarcCore\Service\ObjectService'
    );
}
