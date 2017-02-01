<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Doc Models Service Factory
 *
 * Class DeliveriesModelsServiceFactory
 * @package MonarcCore\Service
 */
class DeliveriesModelsServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\DeliveriesModelsTable',
        'entity' => 'MonarcCore\Model\Entity\DeliveriesModels',
    ];
}