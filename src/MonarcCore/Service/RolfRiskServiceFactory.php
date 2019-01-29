<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Rolf Risk Service Factory
 *
 * Class RolfRiskServiceFactory
 * @package MonarcCore\Service
 */
class RolfRiskServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\RolfRiskTable',
        'entity' => 'MonarcCore\Model\Entity\RolfRisk',
        'rolfTagTable' => 'MonarcCore\Model\Table\RolfTagTable',
        'MonarcObjectTable' => 'MonarcCore\Model\Table\MonarcObjectTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'instanceRiskOpTable' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'instanceRiskOpService' => 'MonarcCore\Service\InstanceRiskOpService',
    ];
}
