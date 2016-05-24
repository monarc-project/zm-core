<?php
namespace MonarcCore\Service;

class HistoricalServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> 'MonarcCore\Model\Table\HistoricalTable',
        'entity'=> 'MonarcCore\Model\Entity\Historical',
    );
}