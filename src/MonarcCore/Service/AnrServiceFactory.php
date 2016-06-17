<?php
namespace MonarcCore\Service;

class AnrServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\AnrTable',
        'entity' => 'MonarcCore\Model\Entity\Anr',
    );
}
