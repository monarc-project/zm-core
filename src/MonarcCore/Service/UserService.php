<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\User;
use MonarcCore\Model\Entity\UserRole;
use MonarcCore\Model\Table\UserTable;
use MonarcCore\Validator\PasswordStrength;

/**
 * User Service
 *
 * Class UserService
 * @package MonarcCore\Service
 */
class UserService extends AbstractService
{
    protected $roleTable;
    protected $userTokenTable;
    protected $passwordTokenTable;
    protected $mailService;

    /**
     * Get Total Count
     *
     * @return bool|mixed
     */
    public function getTotalCount()
    {
        return $this->get('table')->count();
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
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null) {

        return $this->get('table')->countFiltered($page, $limit, $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, array('firstname', 'lastname', 'email')));
    }



    /**
     * Create
     *
     * @param $data
     * @throws \Exception
     */
    public function create($data)
    {
        $user = $this->get('entity');
        $date['status'] = 1;
        $user->exchangeArray($data);

        $id = $this->get('table')->save($user);

        $this->manageRoles($user, $data);

        return $id;
    }

    /**
     * Update
     *
     * @param $id
     * @param $data
     * @return mixed
     */
    public function update($id,$data){
        /** @var User $user */
        $user = $this->get('table')->getEntity($id);

        if (array_key_exists('role', $data)) {
            $this->manageRoles($user, $data);
        }

        return parent::update($id, $data);
    }


    /**
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function patch($id, $data){

        if (array_key_exists('password', $data)) {
            $this->validatePassword($data);
        }

        $user = $this->get('table')->getEntity($id);

        if (array_key_exists('role', $data)) {
            $this->manageRoles($user, $data);
        }

        return parent::patch($id, $data);
    }


    /**
     * Get By Email
     *
     * @param $email
     * @return array
     */
    public function getByEmail($email)
    {
        return $this->get('table')->getByEmail($email);
    }

    /**
     * Validate password
     *
     * @param $data
     * @throws \Exception
     */
    protected function validatePassword($data) {

        $password = $data['password'];

        $passwordValidator = new PasswordStrength();
        if (! $passwordValidator->isValid($password)) {
            $errors = [];
            foreach ($passwordValidator->getMessages() as $messageId => $message) {
                $errors[] = $message;
            }

            throw new \Exception(implode($errors, ', '), 422);
        }
    }

    /**
     * Manage Roles
     *
     * @param $user
     * @param $data
     * @throws \Exception
     */
    protected function manageRoles($user, $data) {

        $userRoleTable = $this->get('roleTable');
        $userRoleTable->deleteByUser($user->id);
        if (array_key_exists('role', $data)) {
            foreach ($data['role'] as $role) {
                $roleData = [
                    'user' => $user,
                    'role' => $role,
                ];

                $userRoleEntity = new UserRole();
                $userRoleEntity->exchangeArray($roleData);

                $userRoleTable->save($userRoleEntity);
            }
        }
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id){
        $user = $this->get('table')->get($id);
        $roles = $this->get('roleTable')->getRepository()->findByUser($user['id']);
        $user['role'] = array();
        if(!empty($roles)){
            foreach($roles as $r){
                $user['role'][$r->get('role')] = $r->get('role');
            }
        }
        return $user;
    }
}
