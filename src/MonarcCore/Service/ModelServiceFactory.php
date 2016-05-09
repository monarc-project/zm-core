<?php
namespace MonarcCore\Service;

class ModelServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'modelTable'=> '\MonarcCore\Model\Table\ModelTable',
        'modelEntity'=> '\MonarcCore\Model\Entity\Model',
    );
}
