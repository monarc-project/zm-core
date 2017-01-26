<?php
namespace MonarcCore\Service;

/**
 * Instance Consequence Service Factory
 *
 * Class InstanceConsequenceServiceFactory
 * @package MonarcCore\Service
 */
class InstanceConsequenceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\InstanceConsequenceTable',
        'entity' => 'MonarcCore\Model\Entity\InstanceConsequence',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
    ];
}
