<?php
namespace MonarcCore\Service;

class AmvServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\AmvTable',
        'entity' => 'MonarcCore\Model\Entity\Amv',
        'anrTable' => '\MonarcCore\Model\Table\AnrTable',
        'assetTable' => '\MonarcCore\Model\Table\AssetTable',
        'instanceTable'=> 'MonarcCore\Model\Table\InstanceTable',
        'measureTable' => '\MonarcCore\Model\Table\MeasureTable',
        'modelTable' => '\MonarcCore\Model\Table\ModelTable',
        'threatTable' => '\MonarcCore\Model\Table\ThreatTable',
        'vulnerabilityTable' => '\MonarcCore\Model\Table\VulnerabilityTable',
        'historicalService'=> 'MonarcCore\Service\HistoricalService',
    );
}
