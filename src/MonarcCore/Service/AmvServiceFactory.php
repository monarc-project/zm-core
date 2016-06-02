<?php
namespace MonarcCore\Service;

class AmvServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\AmvTable',
        'entity' => 'MonarcCore\Model\Entity\Amv',
        'assetTable' => '\MonarcCore\Model\Table\AssetTable',
        'measureTable' => '\MonarcCore\Model\Table\MeasureTable',
        'threatTable' => '\MonarcCore\Model\Table\ThreatTable',
        'vulnerabilityTable' => '\MonarcCore\Model\Table\VulnerabilityTable',
        'historicalService'=> 'MonarcCore\Service\HistoricalService',
    );
}
