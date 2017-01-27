<?php
namespace MonarcCore\Service;

/**
 * Theme Service Factory
 *
 * Class ThemeServiceFactory
 * @package MonarcCore\Service
 */
class ThemeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ThemeTable',
        'entity' => 'MonarcCore\Model\Entity\Theme',
    ];
}