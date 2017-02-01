<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * User Profile Service Factory
 *
 * Class UserProfileServiceFactory
 * @package MonarcCore\Service
 */
class UserProfileServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\UserTable',
        'entity' => 'MonarcCore\Model\Entity\User',
        'securityService' => '\MonarcCore\Service\SecurityService',
    ];
}