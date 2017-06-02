<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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