<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Amv Service Factory
 *
 * Class AmvServiceFactory
 * @package Monarc\Core\Service
 */
class AmvServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\AmvTable',
        'entity' => 'Monarc\Core\Model\Entity\Amv',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'assetTable' => 'Monarc\Core\Model\Table\AssetTable',
        'instanceTable' => 'Monarc\Core\Model\Table\InstanceTable',
        'measureTable' => 'Monarc\Core\Model\Table\MeasureTable',
        'referentialTable' => 'Monarc\Core\Model\Table\ReferentialTable',
        'modelTable' => 'Monarc\Core\Model\Table\ModelTable',
        'threatTable' => 'Monarc\Core\Model\Table\ThreatTable',
        'vulnerabilityTable' => 'Monarc\Core\Model\Table\VulnerabilityTable',
        'historicalService' => 'Monarc\Core\Service\HistoricalService',
    ];
}
