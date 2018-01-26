<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Instance Risk Service Factory
 *
 * Class InstanceRiskServiceFactory
 * @package MonarcCore\Service
 */
class InstanceRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\InstanceRiskTable',
        'entity' => 'MonarcCore\Model\Entity\InstanceRisk',
        'amvTable' => 'MonarcCore\Model\Table\AmvTable',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'assetTable' => 'MonarcCore\Model\Table\AssetTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'threatTable' => 'MonarcCore\Model\Table\ThreatTable',
        'vulnerabilityTable' => 'MonarcCore\Model\Table\VulnerabilityTable',
    ];
}
