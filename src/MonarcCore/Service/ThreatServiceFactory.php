<?php
namespace MonarcCore\Service;

class ThreatServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'threatTable'=> '\MonarcCore\Model\Table\ThreatTable',
        'threatEntity'=> '\MonarcCore\Model\Entity\Threat',
        'modelTable' => '\MonarcCore\Model\Table\ModelTable',
    );
}

