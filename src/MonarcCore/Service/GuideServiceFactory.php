<?php
namespace MonarcCore\Service;

/**
 * Guide Service Factory
 *
 * Class GuideServiceFactory
 * @package MonarcCore\Service
 */
class GuideServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\GuideTable',
        'entity' => 'MonarcCore\Model\Entity\Guide',
    ];
}
