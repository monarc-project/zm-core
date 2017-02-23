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
     * Returns the total amount of users
     * @return int The amount of users
     */
    public function getTotalCount()
    {
        return $this->get('table')->count();
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
<<<<<<< HEAD
     * @inheritdoc
=======
     * Patch
     *
     * @param $id
     * @param $data
     * @return mixed
     * @throws \MonarcCore\Exception\Exception
>>>>>>> 31636fee3b3c0800213cd753ca5d7380f54fb056
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
     * Get an user by email
     * @param string $email The e-mail address
     * @return array The users matching the e-mail address
     */
    public function getByEmail($email)
    {
        return $this->get('table')->getByEmail($email);
    }

    /**
<<<<<<< HEAD
     * Validates that the password matches the required strength policy (special chars, lower/uppercase, number)
     * @param string $data An array with a password key containing the password
     * @throws \Exception If password is invalid
=======
     * Validate password
     *
     * @param $data
     * @throws \MonarcCore\Exception\Exception
>>>>>>> 31636fee3b3c0800213cd753ca5d7380f54fb056
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

            throw new \MonarcCore\Exception\Exception("Password must " . implode($errors, ', ') . ".", 412);
        }
    }

    /**
     * Manage Roles
     * @param User $user The user to manage
     * @param array $data The new user roles
     * @throws \Exception In case of invalid roles selected
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
            throw new \MonarcCore\Exception\Exception("You must select one or more roles", 412);
        }
    }

    /**
     * @inheritdoc
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