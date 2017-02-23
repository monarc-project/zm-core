<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Entity\User;
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
    protected $userRoleEntity;
    protected $userTokenTable;
    protected $passwordTokenTable;
    protected $mailService;

    protected $filterColumns = ['firstname', 'lastname', 'email', 'phone'];

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
     * Create
     *
     * @param $data
     * @param bool $last
     * @return mixed
     */
    public function create($data, $last = true)
    {
        $user = $this->get('entity');
        $data['status'] = 1;

        if (empty($data['language'])) {
            $data['language'] = $this->getLanguage();
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
    public function update($id, $data)
    {
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
    public function patch($id, $data)
    {
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
    protected function validatePassword($data)
    {
        $password = $data['password'];

        $passwordValidator = new PasswordStrength();
        if (!$passwordValidator->isValid($password)) {
            $errors = [];
            foreach ($passwordValidator->getMessages() as $message) {
                $errors[] = $message;
            }

            throw new \Exception("Password must " . implode($errors, ', ') . ".", 412);
        }
    }

    /**
     * Manage Roles
     *
     * @param $user
     * @param $data
     * @throws \Exception
     */
    protected function manageRoles($user, $data)
    {
        if (!empty($data['role'])) {
            $userRoleTable = $this->get('roleTable');
            $userRoleTable->deleteByUser($user->id);

            foreach ($data['role'] as $role) {
                $roleData = [
                    'user' => $user,
                    'role' => $role,
                ];

                $class = $this->get('userRoleEntity');

                $userRoleEntity = new $class();
                $userRoleEntity->setLanguage($this->getLanguage());
                $userRoleEntity->setDbAdapter($this->get('table')->getDb());
                $userRoleEntity->exchangeArray($roleData);

                $userRoleTable->save($userRoleEntity);
            }
        } else {
            throw new \Exception("You must select one or more roles", 412);
        }
    }

    /**
     * Get Entity
     *
     * @param $id
     * @return array
     */
    public function getEntity($id)
    {
        $user = $this->get('table')->get($id);
        $roles = $this->get('roleTable')->getRepository()->findByUser($user['id']);
        $user['role'] = [];
        if (!empty($roles)) {
            foreach ($roles as $r) {
                $user['role'][] = $r->get('role');
            }
        }
        return $user;
    }
}