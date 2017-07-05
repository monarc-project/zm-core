<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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
        'table' => '\MonarcCore\Model\Table\ObjectTable',
        'entity' => '\MonarcCore\Model\Entity\Object',
        'assetExportService' => 'MonarcCore\Service\AssetExportService',
        'objectObjectService' => 'MonarcCore\Service\ObjectObjectService',
        'categoryTable' => '\MonarcCore\Model\Table\ObjectCategoryTable',
        'anrObjectCategoryTable' => '\MonarcCore\Model\Table\AnrObjectCategoryTable',
        'rolfTagTable' => '\MonarcCore\Model\Table\RolfTagTable',
        'rolfRiskTable' => '\MonarcCore\Model\Table\RolfRiskTable',
    ];
}