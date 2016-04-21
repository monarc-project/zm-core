<?php
namespace MonarcCore\Service;

class RoleService extends AbstractService
{

    protected $roleEntity;
    protected $config;

    public function __construct($serviceFactory = null)
    {
        if (is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                $this->set($k,$v);
            }
        } else {
            $this->serviceFactory = $serviceFactory;
        }
    }

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