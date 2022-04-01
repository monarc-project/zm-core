<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\InstanceRisk;
use Monarc\Core\Table;

class InstanceRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => Table\InstanceRiskTable::class,
        'entity' => InstanceRisk::class,
        'amvTable' => Table\AmvTable::class,
        'anrTable' => Table\AnrTable::class,
        'assetTable' => Table\AssetTable::class,
        'instanceTable' => Table\InstanceTable::class,
        'instanceRiskOwnerTable' => InstanceRiskOwnerTable::class,
        'monarcObjectTable' => Table\MonarcObjectTable::class,
        'scaleTable' => Table\ScaleTable::class,
        'threatTable' => Table\ThreatTable::class,
    ];
}
