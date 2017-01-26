<?php
namespace MonarcCore\Service;

/**
 * Doc Models Service Factory
 *
 * Class DeliveriesModelsServiceFactory
 * @package MonarcCore\Service
 */
class DeliveriesModelsServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\DeliveriesModelsTable',
        'entity' => 'MonarcCore\Model\Entity\DeliveriesModels',
    ];
}