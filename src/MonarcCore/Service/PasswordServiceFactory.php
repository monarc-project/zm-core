<?php
namespace MonarcCore\Service;

class PasswordServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'userService'=> 'MonarcCore\Service\UserService',
        'mailService'=> 'MonarcCore\Service\MailService',
        'userEntity' => 'MonarcCore\Model\Entity\User',
        'passwordTokenEntity' => 'MonarcCore\Model\Entity\PasswordToken',
        'passwordTokenTable'=> 'MonarcCore\Model\Table\PasswordTokenTable',
        'userTable'=> 'MonarcCore\Model\Table\UserTable',
    );
}
