<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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