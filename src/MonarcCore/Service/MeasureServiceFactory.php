<?php
namespace MonarcCore\Service;

/**
 * Measure Service Factory
 *
 * Class MeasureServiceFactory
 * @package MonarcCore\Service
 */
class MeasureServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\MeasureTable',
        'entity' => 'MonarcCore\Model\Entity\Measure',
    ];
}