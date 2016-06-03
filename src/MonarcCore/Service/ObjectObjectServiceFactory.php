<?php
namespace MonarcCore\Service;

class ObjectObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=>  '\MonarcCore\Model\Table\ObjectObjectTable',
        'objectTable'=> '\MonarcCore\Model\Table\ObjectTable',
        'entity'=> '\MonarcCore\Model\Entity\ObjectObject',
    );

}
