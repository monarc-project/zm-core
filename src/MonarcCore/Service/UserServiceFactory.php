<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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