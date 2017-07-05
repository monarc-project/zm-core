<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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