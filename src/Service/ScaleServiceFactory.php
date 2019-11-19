<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Scale Service Factory
 *
 * Class ScaleServiceFactory
 * @package Monarc\Core\Service
 */
class ScaleServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'config' => 'Monarc\Core\Service\ConfigService',
        'table' => 'Monarc\Core\Model\Table\ScaleTable',
        'entity' => 'Monarc\Core\Model\Entity\Scale',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'instanceConsequenceTable' => 'Monarc\Core\Model\Table\InstanceConsequenceTable',
        'instanceConsequenceService' => 'Monarc\Core\Service\InstanceConsequenceService',
        'instanceRiskOpTable' => 'Monarc\Core\Model\Table\InstanceRiskOpTable',
        'instanceRiskOpService' => 'Monarc\Core\Service\InstanceRiskOpService',
        'instanceRiskTable' => 'Monarc\Core\Model\Table\InstanceRiskTable',
        'instanceRiskService' => 'Monarc\Core\Service\InstanceRiskService',
        'scaleImpactTypeTable' => 'Monarc\Core\Model\Table\ScaleImpactTypeTable',
        'scaleImpactTypeService' => 'Monarc\Core\Service\ScaleImpactTypeService',
        'commentTable' => 'Monarc\Core\Model\Table\ScaleCommentTable',
    ];
}
