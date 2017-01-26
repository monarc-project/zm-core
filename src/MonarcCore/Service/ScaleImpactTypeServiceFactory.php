<?php
namespace MonarcCore\Service;

/**
 * Scale Impact Type Service Factory
 *
 * Class ScaleImpactTypeServiceFactory
 * @package MonarcCore\Service
 */
class ScaleImpactTypeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'entity' => 'MonarcCore\Model\Entity\ScaleImpactType',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'instanceConsequenceService' => 'MonarcCore\Service\InstanceConsequenceService',
    ];
}