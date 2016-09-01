<?php
namespace MonarcCore\Service;

class RolfRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\RolfRiskTable',
        'entity' => 'MonarcCore\Model\Entity\RolfRisk',
        'rolfTagTable' => 'MonarcCore\Model\Table\RolfTagTable',
    );
}
