<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Table;
use Monarc\Core\Service;
use Monarc\Core\Model\Entity\Amv;

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
        'historicalService' => Service\HistoricalService::class,
        'assetService' => Service\AssetService::class,
        'threatService' => Service\ThreatService::class,
        'vulnerabilityService' => Service\VulnerabilityService::class,
        'themeTable' => Table\ThemeTable::class,
    ];
}
