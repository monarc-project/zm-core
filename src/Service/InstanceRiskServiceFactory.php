<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Instance Risk Service Factory
 *
 * Class InstanceRiskServiceFactory
 * @package Monarc\Core\Service
 */
class InstanceRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\InstanceRiskTable',
        'entity' => 'Monarc\Core\Model\Entity\InstanceRisk',
        'amvTable' => 'Monarc\Core\Model\Table\AmvTable',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'assetTable' => 'Monarc\Core\Model\Table\AssetTable',
        'instanceTable' => 'Monarc\Core\Model\Table\InstanceTable',
        'MonarcObjectTable' => 'Monarc\Core\Model\Table\MonarcObjectTable',
        'scaleTable' => 'Monarc\Core\Model\Table\ScaleTable',
        'threatTable' => 'Monarc\Core\Model\Table\ThreatTable',
        'vulnerabilityTable' => 'Monarc\Core\Model\Table\VulnerabilityTable',
    ];
}
