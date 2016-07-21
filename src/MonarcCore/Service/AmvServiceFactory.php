<?php
namespace MonarcCore\Service;

class AmvServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\AmvTable',
        'entity' => 'MonarcCore\Model\Entity\Amv',
        'assetTable' => '\MonarcCore\Model\Table\AssetTable',
        'measureTable' => '\MonarcCore\Model\Table\MeasureTable',
        'modelTable' => '\MonarcCore\Model\Table\ModelTable',
        'threatTable' => '\MonarcCore\Model\Table\ThreatTable',
        'vulnerabilityTable' => '\MonarcCore\Model\Table\VulnerabilityTable',
        'modelService'=> 'MonarcCore\Service\ModelService',
        'objectService'=> 'MonarcCore\Service\ObjectService',
        'historicalService'=> 'MonarcCore\Service\HistoricalService',
    );
}
