<?php
namespace MonarcCore\Service;

class ObjectObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=>  '\MonarcCore\Model\Table\ObjectObjectTable',
        'anrTable'=> '\MonarcCore\Model\Table\AnrTable',
        'instanceTable'=> '\MonarcCore\Model\Table\InstanceTable',
        'objectTable'=> '\MonarcCore\Model\Table\ObjectTable',
        'entity'=> '\MonarcCore\Model\Entity\ObjectObject',
    );

}
