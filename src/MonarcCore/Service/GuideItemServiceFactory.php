<?php
namespace MonarcCore\Service;

class GuideItemServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\GuideItemTable',
        'entity' => 'MonarcCore\Model\Entity\GuideItem',
        'guideTable' => 'MonarcCore\Model\Table\GuideTable',
    );
}
