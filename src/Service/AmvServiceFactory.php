<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Table;
use Monarc\Core\Model\Entity\Amv;

/**
 * Amv Service Factory
 *
 * Class AmvServiceFactory
 * @package Monarc\Core\Service
 */
class AmvServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => Table\AmvTable::class,
        'entity' => Amv::class,
        'anrTable' => Table\AnrTable::class,
        'assetTable' => Table\AssetTable::class,
        'instanceTable' => Table\InstanceTable::class,
        'measureTable' => Table\MeasureTable::class,
        'referentialTable' => Table\ReferentialTable::class,
        'modelTable' => Table\ModelTable::class,
        'threatTable' => Table\ThreatTable::class,
        'vulnerabilityTable' => Table\VulnerabilityTable::class,
        'historicalService' => HistoricalService::class,
        'assetService' => AssetService::class,
        'threatService' => ThreatService::class,
        'vulnerabilityService' => VulnerabilityService::class,
        'themeTable' => Table\ThemeTable::class,
        'instanceRiskService' => InstanceRiskService::class,
    ];
}
