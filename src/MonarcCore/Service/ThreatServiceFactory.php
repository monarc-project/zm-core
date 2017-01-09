<?php
namespace MonarcCore\Service;

class ThreatServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ThreatTable',
        'entity'=> '\MonarcCore\Model\Entity\Threat',
        'anrTable' => '\MonarcCore\Model\Table\AnrTable',
        'instanceRiskService' => 'MonarcCore\Service\InstanceRiskService',
        'instanceRiskTable' => '\MonarcCore\Model\Table\InstanceRiskTable',
        'modelTable' => '\MonarcCore\Model\Table\ModelTable',
        'modelService' => 'MonarcCore\Service\ModelService',
        'themeTable' => '\MonarcCore\Model\Table\ThemeTable',
        'amvService' => 'MonarcCore\Service\AmvService',
    );
}

