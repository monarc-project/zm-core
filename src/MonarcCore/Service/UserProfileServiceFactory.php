<?php
namespace MonarcCore\Service;

class UserProfileServiceFactory extends AbstractServiceFactory
{
    protected $ressources = array(
        'table' => 'MonarcCore\Model\Table\UserTable',
        'entity' => 'MonarcCore\Model\Entity\User',
        'securityService' => '\MonarcCore\Service\SecurityService',
    );
}
