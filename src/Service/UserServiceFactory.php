<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * User Service Factory
 *
 * Class UserServiceFactory
 * @package Monarc\Core\Service
 */
class UserServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => '\Monarc\Core\Model\Table\UserTable',
        'entity' => '\Monarc\Core\Model\Entity\User',
        'userRoleEntity' => '\Monarc\Core\Model\Entity\UserRole',
        'roleTable' => '\Monarc\Core\Model\Table\UserRoleTable',
        'userTokenTable' => '\Monarc\Core\Model\Table\UserTokenTable',
        'passwordTokenTable' => '\Monarc\Core\Model\Table\PasswordTokenTable',
        'mailService' => '\Monarc\Core\Service\MailService',
    ];
}
