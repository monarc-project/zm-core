<?php
namespace MonarcCore\Service;

class UserRoleServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'userRoleTable'=> '\MonarcCore\Model\Table\UserRoleTable',
        'userRoleEntity'=> '\MonarcCore\Model\Entity\UserRole',
    );
}
