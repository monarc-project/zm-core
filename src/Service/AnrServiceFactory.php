<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Table;
use Monarc\Core\Model\Table as DeprecatedTable;
use Monarc\Core\Model\Entity\Anr;

/**
 * Anr Service Factory
 *
 * Class AnrServiceFactory
 * @package Monarc\Core\Service
 */
class AnrServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => DeprecatedTable\AnrTable::class,
        'entity' => Anr::class,
        'scaleService' => ScaleService::class,
        'instanceService' => InstanceService::class,
        'anrObjectCategoryTable' => Table\AnrObjectCategoryTable::class,
        'instanceTable' => DeprecatedTable\InstanceTable::class,
        'instanceConsequenceTable' => DeprecatedTable\InstanceConsequenceTable::class,
        'instanceRiskTable' => DeprecatedTable\InstanceRiskTable::class,
        'instanceRiskOpTable' => DeprecatedTable\InstanceRiskOpTable::class,
        'MonarcObjectTable' => Table\MonarcObjectTable::class,
        'scaleTable' => DeprecatedTable\ScaleTable::class,
        'scaleImpactTypeTable' => DeprecatedTable\ScaleImpactTypeTable::class,
        'scaleCommentTable' => DeprecatedTable\ScaleCommentTable::class,
        'operationalRiskScaleTable' => Table\OperationalRiskScaleTable::class,
        'operationalRiskScaleTypeTable' => Table\OperationalRiskScaleTypeTable::class,
        'operationalRiskScaleCommentTable' => Table\OperationalRiskScaleCommentTable::class,
        'translationTable' => Table\TranslationTable::class,
        'operationalRiskScaleService' => OperationalRiskScaleService::class,
        'configService' => ConfigService::class,
        'operationalRiskScalesExportService' => OperationalRiskScalesExportService::class,
    ];
}
