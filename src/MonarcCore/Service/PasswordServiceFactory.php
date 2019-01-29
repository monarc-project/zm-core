<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Password Service Factory
 *
 * Class PasswordServiceFactory
 * @package MonarcCore\Service
 */
class PasswordServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'entity' => 'MonarcCore\Model\Entity\PasswordToken',
        'table' => 'MonarcCore\Model\Table\PasswordTokenTable',
        'userTable' => 'MonarcCore\Model\Table\UserTable',
        'userService' => 'MonarcCore\Service\UserService',
        'mailService' => 'MonarcCore\Service\MailService',
        'securityService' => 'MonarcCore\Service\SecurityService',
        'configService' => 'MonarcCore\Service\ConfigService',
    ];
}