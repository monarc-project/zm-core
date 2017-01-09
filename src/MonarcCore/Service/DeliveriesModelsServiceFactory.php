<?php
namespace MonarcCore\Service;

class DeliveriesModelsServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> 'MonarcCore\Model\Table\DeliveriesModelsTable',
        'entity'=> 'MonarcCore\Model\Entity\DeliveriesModels',
    );
}