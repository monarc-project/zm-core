<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\ObjectCategory;
use Monarc\Core\Model\Table;

/**
 * Object Category Service Factory
 *
 * Class ObjectCategoryServiceFactory
 * @package Monarc\Core\Service
 */
class ObjectCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => Table\ObjectCategoryTable::class,
        'entity' => ObjectCategory::class,
        'anrObjectCategoryTable' => Table\AnrObjectCategoryTable::class,
        'monarcObjectTable' => Table\MonarcObjectTable::class,
        'anrTable' => Table\AnrTable::class,
    ];
}
