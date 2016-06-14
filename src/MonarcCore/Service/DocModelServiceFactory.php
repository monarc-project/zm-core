<?php
namespace MonarcCore\Service;

class DocModelServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> 'MonarcCore\Model\Table\DocModelTable',
        'entity'=> 'MonarcCore\Model\Entity\DocModel',
    );
}