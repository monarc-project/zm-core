<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * User Role Service Factory
 *
 * Class UserRoleServiceFactory
 * @package MonarcCore\Service
 */
class UserRoleServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'userRoleTable' => '\MonarcCore\Model\Table\UserRoleTable',
        'userRoleEntity' => '\MonarcCore\Model\Entity\UserRole',
        'userTokenTable' => '\MonarcCore\Model\Table\UserTokenTable',
    ];
}