<?php
namespace MonarcCore\Service;

class GuideServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\GuideTable',
        'entity' => 'MonarcCore\Model\Entity\Guide',
    );
}
