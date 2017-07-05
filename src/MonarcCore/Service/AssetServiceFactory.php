<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Asset Service Factory
 *
 * Class AssetServiceFactory
 * @package MonarcCore\Service
 */
class AssetServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\AssetTable',
        'entity' => 'MonarcCore\Model\Entity\Asset',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'modelTable' => 'MonarcCore\Model\Table\ModelTable',
        'amvService' => 'MonarcCore\Service\AmvService',
        'modelService' => 'MonarcCore\Service\ModelService',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'objectObjectTable' => 'MonarcCore\Model\Table\ObjectObjectTable',
        'assetExportService' => 'MonarcCore\Service\AssetExportService',
    ];
}