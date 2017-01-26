<?php
namespace MonarcCore\Service;

/**
 * Scale Comment Service Factory
 *
 * Class ScaleCommentServiceFactory
 * @package MonarcCore\Service
 */
class ScaleCommentServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ScaleCommentTable',
        'entity' => 'MonarcCore\Model\Entity\ScaleComment',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'scaleService' => 'MonarcCore\Service\ScaleService',
        'scaleImpactTypeService' => 'MonarcCore\Service\ScaleImpactTypeService',
    ];
}