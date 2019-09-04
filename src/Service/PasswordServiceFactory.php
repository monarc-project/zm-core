<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * Password Service Factory
 *
 * Class PasswordServiceFactory
 * @package Monarc\Core\Service
 */
class PasswordServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'entity' => 'Monarc\Core\Model\Entity\PasswordToken',
        'table' => 'Monarc\Core\Model\Table\PasswordTokenTable',
        'userTable' => 'Monarc\Core\Model\Table\UserTable',
        'userService' => 'Monarc\Core\Service\UserService',
        'mailService' => 'Monarc\Core\Service\MailService',
        'securityService' => 'Monarc\Core\Service\SecurityService',
        'configService' => 'Monarc\Core\Service\ConfigService',
    ];
}
