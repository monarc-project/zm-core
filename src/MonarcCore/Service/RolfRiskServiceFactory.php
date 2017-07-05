<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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
        'rolfCategoryTable' => 'MonarcCore\Model\Table\RolfCategoryTable',
        'rolfTagTable' => 'MonarcCore\Model\Table\RolfTagTable',
        'objectTable' => 'MonarcCore\Model\Table\ObjectTable',
        'instanceTable' => 'MonarcCore\Model\Table\InstanceTable',
        'instanceRiskOpTable' => 'MonarcCore\Model\Table\InstanceRiskOpTable',
        'instanceRiskOpService' => 'MonarcCore\Service\InstanceRiskOpService',
    ];
}
