<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Table;
use Monarc\Core\Model\Table as DeprecatedTable;
use Monarc\Core\Model\Entity\Scale;

/**
 * Scale Service Factory
 *
 * Class ScaleServiceFactory
 * @package Monarc\Core\Service
 */
class ScaleServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'config' => ConfigService::class,
        'table' => DeprecatedTable\ScaleTable::class,
        'entity' => Scale::class,
        'anrTable' => DeprecatedTable\AnrTable::class,
        'instanceTable' => Table\InstanceTable::class,
        'instanceConsequenceTable' => Table\InstanceConsequenceTable::class,
        'instanceRiskTable' => DeprecatedTable\InstanceRiskTable::class,
        'instanceRiskService' => InstanceRiskService::class,
        'scaleImpactTypeService' => ScaleImpactTypeService::class,
        'commentTable' => DeprecatedTable\ScaleCommentTable::class,
    ];
}
