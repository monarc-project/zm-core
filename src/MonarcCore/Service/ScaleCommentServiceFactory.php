<?php
namespace MonarcCore\Service;

class ScaleCommentServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ScaleCommentTable',
        'entity' => 'MonarcCore\Model\Entity\ScaleComment',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'scaleService' => 'MonarcCore\Service\ScaleService',
        'scaleImpactTypeService' => 'MonarcCore\Service\ScaleImpactTypeService',
    );
}
