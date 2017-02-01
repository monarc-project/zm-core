<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Model Object Service Factory
 *
 * Class ModelObjectServiceFactory
 * @package MonarcCore\Service
 */
class ModelObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ObjectTable',
        'entity' => 'MonarcCore\Model\Entity\Object',
        'assetTable' => '\MonarcCore\Model\Table\AssetTable',
        'categoryTable' => '\MonarcCore\Model\Table\ObjectCategoryTable',
        'rolfTagTable' => '\MonarcCore\Model\Table\RolfTagTable',
        'sourceTable' => 'MonarcCore\Model\Table\ObjectTable',
        'modelTable' => 'MonarcCore\Model\Table\ModelTable',
    ];
}