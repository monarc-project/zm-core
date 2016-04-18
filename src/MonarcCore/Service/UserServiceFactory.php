<?php
namespace MonarcCore\Service;

class UserServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'userTable'=> '\MonarcCore\Model\Table\UserTable',
        'userEntity'=> '\MonarcCore\Model\Entity\User',
    );
}
