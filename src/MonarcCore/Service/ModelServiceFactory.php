<?php
namespace MonarcCore\Service;

/**
 * Model Service Factory
 *
 * Class ModelServiceFactory
 * @package MonarcCore\Service
 */
class ModelServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ModelTable',
        'entity' => 'MonarcCore\Model\Entity\Model',
        'anrService' => 'MonarcCore\Service\AnrService',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'instanceRiskTable' => 'MonarcCore\Model\Table\InstanceRiskTable',
        'instanceRiskOpTable' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'amvTable' => 'MonarcCore\Model\Table\AmvTable',
    ];
}