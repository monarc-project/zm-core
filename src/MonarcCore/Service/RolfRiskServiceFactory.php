<?php
namespace MonarcCore\Service;

class RolfRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\RolfRiskTable',
        'entity' => 'MonarcCore\Model\Entity\RolfRisk',
        'rolfCategoryTable' => 'MonarcCore\Model\Table\RolfCategoryTable',
        'rolfTagTable' => 'MonarcCore\Model\Table\RolfTagTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'instanceRiskOpTable' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'instanceRiskOpService' => 'MonarcCore\Service\InstanceRiskOpService',
    );
}
