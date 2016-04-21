<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Table\UserRoleTable;

class UserRoleService extends AbstractService
{
    protected $userRoleTable;
    protected $userRoleEntity;

    public function getList($page = 1, $limit = 25, $order = null, $filter = null)
    {
        /** @var UserRoleTable $userRoleTable */
        $userRoleTable = $this->get('userRoleTable');

        return $userRoleTable->fetchAllFiltered(
            array('id', 'user_id', 'role'),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, array('user_id'))
        );
    }

    public function getEntity($id)
    {
        return $this->get('userRoleTable')->get($id);
    }

}