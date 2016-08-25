<?php
namespace MonarcCore\Service;

class InstanceRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\InstanceRiskTable',
        'entity' => 'MonarcCore\Model\Entity\InstanceRisk',
        'amvTable' => 'MonarcCore\Model\Table\AmvTable',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'assetTable' => 'MonarcCore\Model\Table\AssetTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'objectRiskTable' => 'MonarcCore\Model\Table\ObjectRiskTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'threatTable' => 'MonarcCore\Model\Table\ThreatTable',
        'vulnerabilityTable' => 'MonarcCore\Model\Table\VulnerabilityTable',
    );
}
