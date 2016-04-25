<?php
namespace MonarcCore\Service;

class UserServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'userTable'=> '\MonarcCore\Model\Table\UserTable',
        'roleTable'=> '\MonarcCore\Model\Table\UserRoleTable',
        'userEntity'=> '\MonarcCore\Model\Entity\User',
        'mailService'=> '\MonarcCore\Service\MailService',
    );
}
