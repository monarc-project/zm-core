<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Object Category Service Factory
 *
 * Class ObjectCategoryServiceFactory
 * @package MonarcCore\Service
 */
class ObjectCategoryServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => '\MonarcCore\Model\Table\ObjectCategoryTable',
        'entity' => '\MonarcCore\Model\Entity\ObjectCategory',
        'anrObjectCategoryTable' => '\MonarcCore\Model\Table\AnrObjectCategoryTable',
        'objectTable' => '\MonarcCore\Model\Table\ObjectTable',
        'rootTable' => 'MonarcCore\Model\Table\ObjectCategoryTable',
        'parentTable' => 'MonarcCore\Model\Table\ObjectCategoryTable',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
    ];
}