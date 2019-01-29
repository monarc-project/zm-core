<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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
        'table' => '\MonarcCore\Model\Table\MonarcObjectTable',
        'entity' => '\MonarcCore\Model\Entity\MonarcObject',
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
