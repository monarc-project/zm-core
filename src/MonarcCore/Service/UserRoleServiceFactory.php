<?php
namespace MonarcCore\Service;

/**
 * User Role Service Factory
 *
 * Class UserRoleServiceFactory
 * @package MonarcCore\Service
 */
class UserRoleServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'userRoleTable' => '\MonarcCore\Model\Table\UserRoleTable',
        'userRoleEntity' => '\MonarcCore\Model\Entity\UserRole',
        'userTokenTable' => '\MonarcCore\Model\Table\UserTokenTable',
    ];
}