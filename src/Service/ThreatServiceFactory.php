<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Threat Service Factory
 *
 * Class ThreatServiceFactory
 * @package Monarc\Core\Service
 */
class ThreatServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\ThreatTable',
        'entity' => 'Monarc\Core\Model\Entity\Threat',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'instanceRiskService' => 'Monarc\Core\Service\InstanceRiskService',
        'instanceRiskTable' => 'Monarc\Core\Model\Table\InstanceRiskTable',
        'modelTable' => 'Monarc\Core\Model\Table\ModelTable',
        'modelService' => 'Monarc\Core\Service\ModelService',
        'themeTable' => 'Monarc\Core\Model\Table\ThemeTable',
        'amvService' => 'Monarc\Core\Service\AmvService',
    ];
}
