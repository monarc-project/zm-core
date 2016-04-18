<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\User;
use MonarcCore\Model\Table\UserTable;

class UserService extends AbstractService
{
    protected $userTable;
    protected $userEntity;

    public function getTotalCount()
    {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');
        return $userTable->count();
    }

    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        return $userTable->countFiltered($page, $limit, $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, array('firstname', 'lastname', 'email')));
    }

    public function getList($page = 1, $limit = 25, $order = null, $filter = null)
    {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        return $userTable->fetchAllFiltered(
            array('id', 'firstname', 'lastname', 'email', 'phone', 'status'),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, array('firstname', 'lastname', 'email'))
        );
    }

    public function getEntity($id)
    {
        return $this->get('userTable')->get($id);
    }

    public function create($data)
    {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        $entity = new User();
        $entity->exchangeArray($data);

        $userTable->save($entity);
    }

    public function update($data) {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        /** @var User $entity */
        $entity = $userTable->getEntity($data['id']);

        if ($entity != null) {
            $entity->exchangeArray($data);
            $userTable->save($entity);
            return true;
        } else {
            return false;
        }
    }

    public function delete($id)
    {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        $userTable->delete($id);
    }
}