<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Threat Service Factory
 *
 * Class ThreatServiceFactory
 * @package MonarcCore\Service
 */
class ThreatServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => '\MonarcCore\Model\Table\ThreatTable',
        'entity' => '\MonarcCore\Model\Entity\Threat',
        'anrTable' => '\MonarcCore\Model\Table\AnrTable',
        'instanceRiskService' => 'MonarcCore\Service\InstanceRiskService',
        'instanceRiskTable' => '\MonarcCore\Model\Table\InstanceRiskTable',
        'modelTable' => '\MonarcCore\Model\Table\ModelTable',
        'modelService' => 'MonarcCore\Service\ModelService',
        'themeTable' => '\MonarcCore\Model\Table\ThemeTable',
        'amvService' => 'MonarcCore\Service\AmvService',
    ];
}