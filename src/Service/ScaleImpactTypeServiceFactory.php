<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Table\InstanceTable;
use Monarc\Core\Model\Table\ScaleTable;
use Monarc\Core\Model\Table\AnrTable;
use Monarc\Core\Model\Entity\ScaleImpactType;
use Monarc\Core\Model\Table\ScaleImpactTypeTable;

/**
 * Scale Impact Type Service Factory
 *
 * Class ScaleImpactTypeServiceFactory
 * @package Monarc\Core\Service
 */
class ScaleImpactTypeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => ScaleImpactTypeTable::class,
        'entity' => ScaleImpactType::class,
        'anrTable' => AnrTable::class,
        'instanceTable' => InstanceTable::class,
        'scaleTable' => ScaleTable::class,
        'instanceConsequenceService' => InstanceConsequenceService::class,
        'instanceService' => InstanceService::class,
    ];
}
