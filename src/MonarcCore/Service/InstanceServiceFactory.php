<?php
namespace MonarcCore\Service;

class InstanceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\InstanceTable',
        'entity' => 'MonarcCore\Model\Entity\Instance',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'amvTable' => 'MonarcCore\Model\Table\AmvTable',
        'assetTable' => 'MonarcCore\Model\Table\AssetTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'instanceConsequenceTable' => 'MonarcCore\Model\Table\InstanceConsequenceTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'rolfRiskTable' => 'MonarcCore\Model\Table\RolfRiskTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleTypeTable',
        'instanceRiskService' => 'MonarcCore\Service\InstanceRiskService',
        'instanceRiskOpService' => 'MonarcCore\Service\InstanceRiskOpService',
        'objectObjectService' => 'MonarcCore\Service\ObjectObjectService',
    );
}
