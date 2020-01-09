<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Object Export Service Factory
 *
 * Class ObjectExportServiceFactory
 * @package Monarc\Core\Service
 */
class ObjectExportServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'entity' => 'Monarc\Core\Model\Entity\MonarcObject',
        'assetExportService' => 'Monarc\Core\Service\AssetExportService',
        'objectObjectService' => 'Monarc\Core\Service\ObjectObjectService',
        'categoryTable' => 'Monarc\Core\Model\Table\ObjectCategoryTable',
        'anrObjectCategoryTable' => 'Monarc\Core\Model\Table\AnrObjectCategoryTable',
        'rolfTagTable' => 'Monarc\Core\Model\Table\RolfTagTable',
        'rolfRiskTable' => 'Monarc\Core\Model\Table\RolfRiskTable',
        'configService' => 'Monarc\Core\Service\ConfigService',
    ];
}
