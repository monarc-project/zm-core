<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Asset Service Factory
 *
 * Class AssetServiceFactory
 * @package Monarc\Core\Service
 */
class AssetServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\AssetTable',
        'entity' => 'Monarc\Core\Model\Entity\Asset',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'modelTable' => 'Monarc\Core\Model\Table\ModelTable',
        'amvService' => 'Monarc\Core\Service\AmvService',
        'modelService' => 'Monarc\Core\Service\ModelService',
        'MonarcObjectTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'objectObjectTable' => 'Monarc\Core\Model\Table\ObjectObjectTable',
        'assetExportService' => 'Monarc\Core\Service\AssetExportService',
    ];
}
