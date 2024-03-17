<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Table\MonarcObjectTable;
use Monarc\Core\Table\InstanceRiskOpTable;

/**
 * Rolf Risk Service Factory
 *
 * Class RolfRiskServiceFactory
 * @package Monarc\Core\Service
 */
class RolfRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\RolfRiskTable',
        'entity' => 'Monarc\Core\Entity\RolfRisk',
        'rolfTagTable' => 'Monarc\Core\Model\Table\RolfTagTable',
        'MonarcObjectTable' => MonarcObjectTable::class,
        'measureTable' => 'Monarc\Core\Model\Table\MeasureTable',
        'instanceRiskOpTable' => InstanceRiskOpTable::class,
        'instanceRiskOpService' => InstanceRiskOpService::class,
        'referentialTable' => 'Monarc\Core\Model\Table\ReferentialTable'
    ];
}
