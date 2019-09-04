<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Anr Service Factory
 *
 * Class AnrServiceFactory
 * @package Monarc\Core\Service
 */
class AnrServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\AnrTable',
        'entity' => 'Monarc\Core\Model\Entity\Anr',
        'scaleService' => 'Monarc\Core\Service\ScaleService',
        'anrObjectCategoryTable' => 'Monarc\Core\Model\Table\AnrObjectCategoryTable',
        'instanceTable' => 'Monarc\Core\Model\Table\InstanceTable',
        'instanceConsequenceTable' => 'Monarc\Core\Model\Table\InstanceConsequenceTable',
        'instanceRiskTable' => 'Monarc\Core\Model\Table\InstanceRiskTable',
        'instanceRiskOpTable' => 'Monarc\Core\Model\Table\InstanceRiskOpTable',
        'MonarcObjectTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'scaleTable' => 'Monarc\Core\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'Monarc\Core\Model\Table\ScaleImpactTypeTable',
        'scaleCommentTable' => 'Monarc\Core\Model\Table\ScaleCommentTable',
        'instanceService' => 'Monarc\Core\Service\InstanceService',
    ];
}
