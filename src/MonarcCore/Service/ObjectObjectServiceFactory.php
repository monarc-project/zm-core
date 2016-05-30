<?php
namespace MonarcCore\Service;

class ObjectObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=>  '\MonarcCore\Model\Table\ObjectObjectTable',
        'entity'=> '\MonarcCore\Model\Entity\ObjectObject',
    );

}
