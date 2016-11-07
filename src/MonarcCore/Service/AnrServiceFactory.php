<?php
namespace MonarcCore\Service;

class AnrServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\AnrTable',
        'entity' => 'MonarcCore\Model\Entity\Anr',
        'scaleService' => 'MonarcCore\Service\ScaleService',
        'anrObjectCategoryTable' => 'MonarcCore\Model\Table\AnrObjectCategoryTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'instanceConsequenceTable' => 'MonarcCore\Model\Table\InstanceConsequenceTable',
        'instanceRiskTable' => 'MonarcCore\Model\Table\InstanceRiskTable',
        'instanceRiskOpTable' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'scaleCommentTable' => 'MonarcCore\Model\Table\ScaleCommentTable',
        'instanceService' => 'MonarcCore\Service\InstanceService',
    );
}
