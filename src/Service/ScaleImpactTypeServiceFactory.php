<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Scale Impact Type Service Factory
 *
 * Class ScaleImpactTypeServiceFactory
 * @package Monarc\Core\Service
 */
class ScaleImpactTypeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\ScaleImpactTypeTable',
        'entity' => 'Monarc\Core\Model\Entity\ScaleImpactType',
        'anrTable' => 'Monarc\Core\Model\Table\AnrTable',
        'instanceTable' => 'Monarc\Core\Model\Table\InstanceTable',
        'scaleTable' => 'Monarc\Core\Model\Table\ScaleTable',
        'instanceConsequenceService' => 'Monarc\Core\Service\InstanceConsequenceService',
    ];
}
