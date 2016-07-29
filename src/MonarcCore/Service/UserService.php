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
        $data['status'] = 1;

        if(empty($data['language'])){
            $data['language'] = $this->get('table')->getConnectedUser()['language'];
        }

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

        if (isset($data['role'])) {
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

        if (isset($data['password'])) {
            $this->validatePassword($data);
        }

        $user = $this->get('table')->getEntity($id);

        if (isset($data['role'])) {
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
        if (!empty($data['role'])) {
            foreach ($data['role'] as $role) {
                $roleData = [
                    'user' => $user,
                    'role' => $role,
                ];

                $userRoleEntity = new UserRole();
                $userRoleEntity->exchangeArray($roleData);

                $userRoleTable->save($userRoleEntity);
            }
        }else{
            throw new \Exception("You must select on or more roles", 422);
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
                $user['role'][] = $r->get('role');
            }
        }
        return $user;
    }
}
