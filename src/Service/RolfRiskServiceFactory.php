<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Table\InstanceTable;

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
        'entity' => 'Monarc\Core\Model\Entity\RolfRisk',
        'rolfTagTable' => 'Monarc\Core\Model\Table\RolfTagTable',
        'MonarcObjectTable' => 'Monarc\Core\Table\MonarcObjectTable',
        'instanceTable' => InstanceTable::class,
        'measureTable' => 'Monarc\Core\Model\Table\MeasureTable',
        'instanceRiskOpTable' => 'Monarc\Core\Model\Table\InstanceRiskOpTable',
        'instanceRiskOpService' => 'Monarc\Core\Service\InstanceRiskOpService',
        'referentialTable' => 'Monarc\Core\Model\Table\ReferentialTable'
    ];
}
