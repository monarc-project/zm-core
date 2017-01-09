<?php
namespace MonarcCore\Service;

class ScaleImpactTypeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'entity' => 'MonarcCore\Model\Entity\ScaleImpactType',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'instanceConsequenceService' => 'MonarcCore\Service\InstanceConsequenceService',
    );
}
