<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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