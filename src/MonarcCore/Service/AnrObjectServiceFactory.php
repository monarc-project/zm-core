<?php
namespace MonarcCore\Service;

/**
 * Anr Object Service Factory
 *
 * Class AnrObjectServiceFactory
 * @package MonarcCore\Service
 */
class AnrObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ObjectTable',
        'entity' => 'MonarcCore\Model\Entity\Object',
        'objectObjectTable' => 'MonarcCore\Model\Table\ObjectObjectTable',
        'objectService' => 'MonarcCore\Service\ObjectService'
    ];
}
