<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Amv Service Factory
 *
 * Class AmvServiceFactory
 * @package MonarcCore\Service
 */
class AmvServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\AmvTable',
        'entity' => 'MonarcCore\Model\Entity\Amv',
        'anrTable' => '\MonarcCore\Model\Table\AnrTable',
        'assetTable' => '\MonarcCore\Model\Table\AssetTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'measureTable' => '\MonarcCore\Model\Table\MeasureTable',
        'modelTable' => '\MonarcCore\Model\Table\ModelTable',
        'threatTable' => '\MonarcCore\Model\Table\ThreatTable',
        'vulnerabilityTable' => '\MonarcCore\Model\Table\VulnerabilityTable',
        'historicalService' => 'MonarcCore\Service\HistoricalService',
    ];
}