<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Object Category Service Factory
 *
 * Class ObjectCategoryServiceFactory
 * @package Monarc\Core\Service
 */
class ObjectCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\ObjectCategoryTable',
        'entity' => 'Monarc\Core\Model\Entity\ObjectCategory',
        'anrObjectCategoryTable' => 'Monarc\Core\Model\Table\AnrObjectCategoryTable',
        'MonarcObjectTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'rootTable' => 'Monarc\Core\Model\Table\ObjectCategoryTable',
        'parentTable' => 'Monarc\Core\Model\Table\ObjectCategoryTable',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
    ];
}
