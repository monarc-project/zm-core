<?php
namespace MonarcCore\Service;

/**
 * Guide Item Service Factory
 *
 * Class GuideItemServiceFactory
 * @package MonarcCore\Service
 */
class GuideItemServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\GuideItemTable',
        'entity' => 'MonarcCore\Model\Entity\GuideItem',
        'guideTable' => 'MonarcCore\Model\Table\GuideTable',
    ];
}