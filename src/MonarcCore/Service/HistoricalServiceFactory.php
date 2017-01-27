<?php
namespace MonarcCore\Service;

/**
 * Historical Service Factory
 *
 * Class HistoricalServiceFactory
 * @package MonarcCore\Service
 */
class HistoricalServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\HistoricalTable',
        'entity' => 'MonarcCore\Model\Entity\Historical',
    ];
}