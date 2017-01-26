<?php
namespace MonarcCore\Service;

/**
 * Rolf Category Service Factory
 *
 * Class RolfCategoryServiceFactory
 * @package MonarcCore\Service
 */
class RolfCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\RolfCategoryTable',
        'entity' => 'MonarcCore\Model\Entity\RolfCategory',
    ];
}