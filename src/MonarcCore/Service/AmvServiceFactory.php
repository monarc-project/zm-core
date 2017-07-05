<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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