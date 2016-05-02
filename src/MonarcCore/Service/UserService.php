<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\User;
use MonarcCore\Model\Entity\UserRole;
use MonarcCore\Model\Table\UserTable;

/**
 * User Service
 *
 * Class UserService
 * @package MonarcCore\Service
 */
class UserService extends AbstractService
{
    protected $userTable;
    protected $roleTable;
    protected $userEntity;
    protected $mailService;

    /**
     * Get Total Count
     *
     * @return bool|mixed
     */
    public function getTotalCount()
    {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');
        return $userTable->count();
    }

    /**
     * Get Filtered Count
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return bool|mixed
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null) {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        return $userTable->countFiltered($page, $limit, $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, array('firstname', 'lastname', 'email')));
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @return array|bool
     */
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

    /**
     * Get Entity
     *
     * @param $id
     * @return mixed
     */
    public function getEntity($id)
    {
        return $this->get('userTable')->get($id);
    }

    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data)
    {
        //user
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        $userEntity = $this->get('userEntity');//new User();
        $userEntity->exchangeArray($data);

        $userTable->save($userEntity);

        //user role
        /** @var UserRoleTable $userRoleTable */
        $userRoleTable = $this->get('roleTable');
        if (array_key_exists('role', $data)) {
            foreach ($data['role'] as $role) {
                $roleData = [
                    'user' => $userEntity,
                    'role' => $role,
                ];

                $userRoleEntity = new UserRole();
                $userRoleEntity->exchangeArray($roleData);

                $userRoleTable->save($userRoleEntity);
            }
        }
    }

    /**
     * Update
     *
     * @param $data
     * @return bool
     * @throws \Exception
     */
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

    /**
     * Delete
     *
     * @param $id
     */
    public function delete($id)
    {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        $userTable->delete($id);
    }

    /**
     * Get By Email
     * 
     * @param $email
     * @return array
     */
    public function getByEmail($email)
    {
        /** @var UserTable $userTable */
        $userTable = $this->get('userTable');

        return $userTable->getRepository()->createQueryBuilder('u')
            ->select(array('u.id', 'u.firstname', 'u.lastname', 'u.email', 'u.phone', 'u.status'))
            ->where('u.email = :email')
            ->setParameter(':email', $email)
            ->getQuery()->getResult();
    }

}