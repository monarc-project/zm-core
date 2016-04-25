<?php
namespace MonarcCore\Service;

class PasswordServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'userService'=> 'MonarcCore\Service\UserService',
        'mailService'=> 'MonarcCore\Service\MailService',
        'passwordTokenTable'=> '\MonarcCore\Model\Table\PasswordTokenTable',
    );
}
