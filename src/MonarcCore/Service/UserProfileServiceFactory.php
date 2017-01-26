<?php
namespace MonarcCore\Service;

/**
 * User Profile Service Factory
 *
 * Class UserProfileServiceFactory
 * @package MonarcCore\Service
 */
class UserProfileServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'MonarcCore\Model\Table\UserTable',
        'entity' => 'MonarcCore\Model\Entity\User',
        'securityService' => '\MonarcCore\Service\SecurityService',
    ];
}