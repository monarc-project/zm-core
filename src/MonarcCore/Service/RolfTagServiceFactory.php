<?php
namespace MonarcCore\Service;

/**
 * Rolf Tag Service Factory
 *
 * Class RolfTagServiceFactory
 * @package MonarcCore\Service
 */
class RolfTagServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\RolfTagTable',
        'entity' => 'MonarcCore\Model\Entity\RolfTag',
    ];
}