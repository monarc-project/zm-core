<?php
namespace MonarcCore\Service;

class RoleServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'roleEntity'=> '\MonarcCore\Model\Entity\Role',
        'config'=> 'Config',
    );

}
