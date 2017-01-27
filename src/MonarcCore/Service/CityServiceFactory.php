<?php
namespace MonarcCore\Service;

/**
 * City Service Factory
 *
 * Class CityServiceFactory
 * @package MonarcCore\Service
 */
class CityServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\CityTable',
        'entity' => 'MonarcCore\Model\Entity\City',
    ];
}
