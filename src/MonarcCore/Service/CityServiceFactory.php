<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * City Service Factory
 *
 * Class CityServiceFactory
 * @package MonarcCore\Service
 */
class CityServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\CityTable',
        'entity' => 'MonarcCore\Model\Entity\City',
    ];
}
