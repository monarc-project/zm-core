<?php
namespace MonarcCore\Service;

class ObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectTable',
        'entity'=> '\MonarcCore\Model\Entity\Object',
        'objectObjectService'=> 'MonarcCore\Service\ObjectObjectService',
    );

}
