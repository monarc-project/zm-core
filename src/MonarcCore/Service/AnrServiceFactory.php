<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Anr Service Factory
 *
 * Class AnrServiceFactory
 * @package MonarcCore\Service
 */
class AnrServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\AnrTable',
        'entity' => 'MonarcCore\Model\Entity\Anr',
        'scaleService' => 'MonarcCore\Service\ScaleService',
        'anrObjectCategoryTable' => 'MonarcCore\Model\Table\AnrObjectCategoryTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'instanceConsequenceTable' => 'MonarcCore\Model\Table\InstanceConsequenceTable',
        'instanceRiskTable' => 'MonarcCore\Model\Table\InstanceRiskTable',
        'instanceRiskOpTable' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'MonarcObjectTable' => 'MonarcCore\Model\Table\MonarcObjectTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'scaleCommentTable' => 'MonarcCore\Model\Table\ScaleCommentTable',
        'referentialTable' => 'MonarcCore\Model\Table\ReferentialTable',
        'measureTable' => 'MonarcCore\Model\Table\MeasureTable',
        'instanceService' => 'MonarcCore\Service\InstanceService',
    ];
}
