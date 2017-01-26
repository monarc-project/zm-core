<?php
namespace MonarcCore\Service;

/**
 * Instance Risk Op Service Factory
 *
 * Class InstanceRiskOpServiceFactory
 * @package MonarcCore\Service
 */
class InstanceRiskOpServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'entity' => 'MonarcCore\Model\Entity\InstanceRiskOp',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'modelTable' => 'MonarcCore\Model\Table\ModelTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'rolfRiskTable' => 'MonarcCore\Model\Table\RolfRiskTable',
        'rolfTagTable' => 'MonarcCore\Model\Table\RolfTagTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
    ];
}