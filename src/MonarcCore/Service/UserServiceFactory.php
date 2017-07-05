<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * User Service Factory
 *
 * Class UserServiceFactory
 * @package MonarcCore\Service
 */
class UserServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => '\MonarcCore\Model\Table\UserTable',
        'entity' => '\MonarcCore\Model\Entity\User',
        'userRoleEntity' => '\MonarcCore\Model\Entity\UserRole',
        'roleTable' => '\MonarcCore\Model\Table\UserRoleTable',
        'userTokenTable' => '\MonarcCore\Model\Table\UserTokenTable',
        'passwordTokenTable' => '\MonarcCore\Model\Table\PasswordTokenTable',
        'mailService' => '\MonarcCore\Service\MailService',
    ];
}