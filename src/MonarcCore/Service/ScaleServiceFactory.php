<?php
namespace MonarcCore\Service;

/**
 * Scale Service Factory
 *
 * Class ScaleServiceFactory
 * @package MonarcCore\Service
 */
class ScaleServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'config' => 'MonarcCore\Service\ConfigService',
        'table' => 'MonarcCore\Model\Table\ScaleTable',
        'entity' => 'MonarcCore\Model\Entity\Scale',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'instanceConsequenceTable' => 'MonarcCore\Model\Table\InstanceConsequenceTable',
        'instanceConsequenceService' => 'MonarcCore\Service\InstanceConsequenceService',
        'instanceRiskOpTable' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'instanceRiskOpService' => 'MonarcCore\Service\InstanceRiskOpService',
        'instanceRiskTable' => 'MonarcCore\Model\Table\InstanceRiskTable',
        'instanceRiskService' => 'MonarcCore\Service\InstanceRiskService',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'scaleImpactTypeService' => 'MonarcCore\Service\ScaleImpactTypeService',
        'commentTable' => 'MonarcCore\Model\Table\ScaleCommentTable',
    ];
}
