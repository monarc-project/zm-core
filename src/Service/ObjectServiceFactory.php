<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Object Service Factory
 *
 * Class ObjectServiceFactory
 * @package Monarc\Core\Service
 */
class ObjectServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'entity' => 'Monarc\Core\Model\Entity\MonarcObject',
        'anrObjectCategoryEntity' => 'Monarc\Core\Model\Entity\AnrObjectCategory',
        'assetTable' => 'Monarc\Core\Model\Table\AssetTable',
        'assetService' => 'Monarc\Core\Service\AssetService',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'anrObjectCategoryTable' => 'Monarc\Core\Model\Table\AnrObjectCategoryTable',
        'amvTable' => 'Monarc\Core\Model\Table\AmvTable',
        'categoryTable' => 'Monarc\Core\Model\Table\ObjectCategoryTable',
        'instanceTable' => 'Monarc\Core\Model\Table\InstanceTable',
        'instanceRiskOpTable' => 'Monarc\Core\Model\Table\InstanceRiskOpTable',
        'modelTable' => 'Monarc\Core\Model\Table\ModelTable',
        'objectObjectTable' => 'Monarc\Core\Model\Table\ObjectObjectTable',
        'rolfTagTable' => 'Monarc\Core\Model\Table\RolfTagTable',
        'modelService' => 'Monarc\Core\Service\ModelService',
        'objectObjectService' => 'Monarc\Core\Service\ObjectObjectService',
        'objectExportService' => 'Monarc\Core\Service\ObjectExportService',
//        'objectImportService' => ObjectImportService::class,
        'instanceRiskOpService' => 'Monarc\Core\Service\InstanceRiskOpService',
    ];
}
