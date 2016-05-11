<?php
namespace MonarcCore\Service;

class ModelServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ModelTable',
        'entity' => 'MonarcCore\Model\Entity\Model',
    );
}
