<?php
namespace MonarcCore\Service;

class ThreatServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\ThreatTable',
        'entity'=> '\MonarcCore\Model\Entity\Threat',
        'modelTable' => '\MonarcCore\Model\Table\ModelTable',
        'themeTable' => '\MonarcCore\Model\Table\ThemeTable',
    );
}

