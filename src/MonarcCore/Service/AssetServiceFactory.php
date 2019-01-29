<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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
        'MonarcObjectTable' => 'MonarcCore\Model\Table\MonarcObjectTable',
        'objectObjectTable' => 'MonarcCore\Model\Table\ObjectObjectTable',
        'assetExportService' => 'MonarcCore\Service\AssetExportService',
    ];
}