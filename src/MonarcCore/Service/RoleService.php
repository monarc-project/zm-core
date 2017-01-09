<?php
namespace MonarcCore\Service;

class RoleService extends AbstractService
{
    protected $config;

    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null) {

        $config = $this->config;
        $roles = $config['roles'];

        return count($roles);
    }

    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
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
