<?php
namespace MonarcCore\Service;

/**
 * Authentication Service Factory
 *
 * Class AuthenticationServiceFactory
 * @package MonarcCore\Service
 */
class AuthenticationServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'userTable' => '\MonarcCore\Model\Table\UserTable',
        'storage' => '\MonarcCore\Storage\Authentication',
        'adapter' => '\MonarcCore\Adapter\Authentication',
    ];
}
