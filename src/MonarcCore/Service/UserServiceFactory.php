<?php
namespace MonarcCore\Service;

class UserServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table'=> '\MonarcCore\Model\Table\UserTable',
        'entity'=> '\MonarcCore\Model\Entity\User',
        'roleTable'=> '\MonarcCore\Model\Table\UserRoleTable',
        'mailService'=> '\MonarcCore\Service\MailService',
    );
}
