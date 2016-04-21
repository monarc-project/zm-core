<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\Role;

class RoleService extends AbstractService
{

    protected $roleEntity;
    protected $config;

    public function getFilteredCount() {

        $config = $this->config;
        $roles = $config['roles'];
        
        return count($roles);
    }

    public function getList()
    {
        $roleCollection = [];

        $config = $this->config;
        $roles = $config['roles'];
        foreach($roles as $role => $permissions) {
            $roleCollection[] = ['name' => $role];
        }

        return $roleCollection;
    }

}