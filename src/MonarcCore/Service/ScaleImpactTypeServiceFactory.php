<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Scale Impact Type Service Factory
 *
 * Class ScaleImpactTypeServiceFactory
 * @package MonarcCore\Service
 */
class ScaleImpactTypeServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\ScaleImpactTypeTable',
        'entity' => 'MonarcCore\Model\Entity\ScaleImpactType',
        'anrTable' => 'MonarcCore\Model\Table\AnrTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'scaleTable' => 'MonarcCore\Model\Table\ScaleTable',
        'instanceConsequenceService' => 'MonarcCore\Service\InstanceConsequenceService',
    ];
}