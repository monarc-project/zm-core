<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Object Service Factory
 *
 * Class ObjectServiceFactory
 * @package MonarcCore\Service
 */
class ObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => '\MonarcCore\Model\Table\ObjectTable',
        'entity' => '\MonarcCore\Model\Entity\Object',
        'anrObjectCategoryEntity' => 'MonarcCore\Model\Entity\AnrObjectCategory',
        'assetTable' => '\MonarcCore\Model\Table\AssetTable',
        'assetService' => 'MonarcCore\Service\AssetService',
        'anrTable' => '\MonarcCore\Model\Table\AnrTable',
        'anrObjectCategoryTable' => '\MonarcCore\Model\Table\AnrObjectCategoryTable',
        'amvTable' => '\MonarcCore\Model\Table\AmvTable',
        'categoryTable' => '\MonarcCore\Model\Table\ObjectCategoryTable',
        'instanceTable' => '\MonarcCore\Model\Table\InstanceTable',
        'instanceRiskOpTable' => '\MonarcCore\Model\Table\InstanceRiskOpTable',
        'modelTable' => '\MonarcCore\Model\Table\ModelTable',
        'objectObjectTable' => '\MonarcCore\Model\Table\ObjectObjectTable',
        'rolfTagTable' => '\MonarcCore\Model\Table\RolfTagTable',
        'modelService' => 'MonarcCore\Service\ModelService',
        'objectObjectService' => 'MonarcCore\Service\ObjectObjectService',
        'objectExportService' => 'MonarcCore\Service\ObjectExportService',
        'instanceRiskOpService' => 'MonarcCore\Service\InstanceRiskOpService',
    ];
}