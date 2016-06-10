<?php
namespace MonarcCore\Service;

class ObjectRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ObjectRiskTable',
        'entity'=> '\MonarcCore\Model\Entity\ObjectRisk',
        'objectTable' => '\MonarcCore\Model\Table\ObjectTable',
        'amvTable' => '\MonarcCore\Model\Table\AmvTable',
        'assetTable' => '\MonarcCore\Model\Table\AssetTable',
        'threatTable' => '\MonarcCore\Model\Table\ThreatTable',
        'vulnerabilityTable' => '\MonarcCore\Model\Table\VulnerabilityTable',
    );

}
