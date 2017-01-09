<?php
namespace MonarcCore\Service;

class AuthenticationServiceFactory extends AbstractServiceFactory
{
	protected $ressources = array(
        'userTable'=> '\MonarcCore\Model\Table\UserTable',
        'storage'=> '\MonarcCore\Storage\Authentication',
        'adapter'=> '\MonarcCore\Adapter\Authentication',
    );
}
