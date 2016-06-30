<?php
namespace MonarcCore\Service;

class PasswordServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'passwordTokenEntity' => 'MonarcCore\Model\Entity\PasswordToken',
        'passwordTokenTable'=> 'MonarcCore\Model\Table\PasswordTokenTable',
        'userService'=> 'MonarcCore\Service\UserService',
        'mailService'=> 'MonarcCore\Service\MailService',
    );
}
