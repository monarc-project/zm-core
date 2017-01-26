<?php
namespace MonarcCore\Service;

/**
 * Country Service Factory
 *
 * Class CountryServiceFactory
 * @package MonarcCore\Service
 */
class CountryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\CountryTable',
        'entity' => 'MonarcCore\Model\Entity\Country',
    ];
}