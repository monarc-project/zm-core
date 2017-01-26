<?php
namespace MonarcCore\Service;

class PasswordServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'entity' => 'MonarcCore\Model\Entity\PasswordToken',
        'table' => 'MonarcCore\Model\Table\PasswordTokenTable',
        'userTable' => 'MonarcCore\Model\Table\UserTable',
        'userService' => 'MonarcCore\Service\UserService',
        'mailService' => 'MonarcCore\Service\MailService',
    );
}
