<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Object Export Service Factory
 *
 * Class ObjectExportServiceFactory
 * @package MonarcCore\Service
 */
class ObjectExportServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => '\MonarcCore\Model\Table\MonarcObjectTable',
        'entity' => '\MonarcCore\Model\Entity\MonarcObject',
        'assetExportService' => 'MonarcCore\Service\AssetExportService',
        'objectObjectService' => 'MonarcCore\Service\ObjectObjectService',
        'categoryTable' => '\MonarcCore\Model\Table\ObjectCategoryTable',
        'anrObjectCategoryTable' => '\MonarcCore\Model\Table\AnrObjectCategoryTable',
        'rolfTagTable' => '\MonarcCore\Model\Table\RolfTagTable',
        'rolfRiskTable' => '\MonarcCore\Model\Table\RolfRiskTable',
        'configService' => 'MonarcCore\Service\ConfigService',
    ];
}
