<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Instance Service Factory
 *
 * Class InstanceServiceFactory
 * @package MonarcCore\Service
 */
class InstanceServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\InstanceTable',
        'entity' => 'MonarcCore\Model\Entity\Instance',
        'instanceConsequenceEntity' => 'MonarcCore\Model\Entity\InstanceConsequence',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'amvTable' => 'MonarcCore\Model\Table\AmvTable',
        'assetTable' => 'MonarcCore\Model\Table\AssetTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'instanceConsequenceTable' => 'MonarcCore\Model\Table\InstanceConsequenceTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'rolfRiskTable' => 'MonarcCore\Model\Table\RolfRiskTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'scaleImpactTypeTable' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'instanceConsequenceService' => 'MonarcCore\Service\InstanceConsequenceService',
        'instanceRiskService' => 'MonarcCore\Service\InstanceRiskService',
        'instanceRiskOpService' => 'MonarcCore\Service\InstanceRiskOpService',
        'objectObjectService' => 'MonarcCore\Service\ObjectObjectService',
        'objectExportService' => 'MonarcCore\Service\ObjectExportService',
        'amvService' => 'MonarcCore\Service\AmvService',
        'translateService' => 'MonarcCore\Service\TranslateService',
    ];
}