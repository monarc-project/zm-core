<?php
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
    ];
}