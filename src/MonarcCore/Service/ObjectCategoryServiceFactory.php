<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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