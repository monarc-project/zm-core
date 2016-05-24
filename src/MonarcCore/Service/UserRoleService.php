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

        return $userRoleTable->getRepository()->createQueryBuilder('t')
            ->select(array('t.id','t.role'))
            ->where('t.user = :id')
            ->setParameter(':id',$filter)
            ->getQuery()->getResult();
    }

    public function getEntity($id)
    {
        return $this->get('userRoleTable')->get($id);
    }

    public function getByUserId($userId)
    {
        /** @var UserRoleTable $userRoleTable */
        $userRoleTable = $this->get('userRoleTable');

        return $userRoleTable->getRepository()->createQueryBuilder('t')
            ->select(array('t.id','t.role'))
            ->where('t.user = :id')
            ->setParameter(':id',$userId)
            ->getQuery()->getResult();
    }

}