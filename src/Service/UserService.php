<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\User;
use Monarc\Core\Model\Entity\UserRole;
use Monarc\Core\Model\Table\PasswordTokenTable;
use Monarc\Core\Model\Table\UserRoleTable;
use Monarc\Core\Model\Table\UserTable;
use Monarc\Core\Model\Table\UserTokenTable;
use Monarc\Core\Validator\PasswordStrength;

/**
 * User Service
 *
 * Class UserService
 * @package Monarc\Core\Service
 */
class UserService
{
//    protected $roleTable;
//    protected $userRoleEntity;
//    protected $userTokenTable;
//    protected $passwordTokenTable;
//    protected $mailService;
    /*
    'table' => '\Monarc\Core\Model\Table\UserTable',
    'entity' => '\Monarc\Core\Model\Entity\User',
    'userRoleEntity' => '\Monarc\Core\Model\Entity\UserRole',
    'roleTable' => '\Monarc\Core\Model\Table\UserRoleTable',
    'userTokenTable' => '\Monarc\Core\Model\Table\UserTokenTable',
    'passwordTokenTable' => '\Monarc\Core\Model\Table\PasswordTokenTable',
    'mailService' => '\Monarc\Core\Service\MailService',
    */
    protected $filterColumns = ['firstname', 'lastname', 'email'];

    /** @var UserTable */
    private $userTable;

    /** @var UserRoleTable */
    private $userRoleTable;

    /** @var UserTokenTable */
    private $userTokenTable;

    /** @var PasswordTokenTable */
    private $passwordTokenTable;

    /** @var MailService */
    private $mailService;

    public function __construct(
        UserTable $userTable,
        UserRoleTable $userRoleTable,
        UserTokenTable $userTokenTable,
        PasswordTokenTable $passwordTokenTable,
        MailService $mailService
    ) {
        $this->userTable = $userTable;
        $this->userRoleTable = $userRoleTable;
        $this->userTokenTable = $userTokenTable;
        $this->passwordTokenTable = $passwordTokenTable;
        $this->mailService = $mailService;
    }

    /**
     * @inheritdoc
     */
    public function create($data, $last = true)
    {
        $user = new User();
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
     * @inheritdoc
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

    public function getByEmail(string $email): User
    {
        return $this->userTable->getByEmail($email);
    }

    /**
     * Validates that the password matches the required strength policy (special chars, lower/uppercase, number)
     *
     * @param string $data An array with a password key containing the password
     *
     * @throws \Exception If password is invalid
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

            throw new Exception("Password must " . implode($errors, ', ') . ".", 412);
        }
    }

    /**
     * Manage Roles
     *
     * @param User $user The user to manage
     * @param array $data The new user roles
     *
     * @throws \Exception In case of invalid roles selected
     */
    protected function manageRoles(User $user, $data)
    {
        if (empty($data['role'])) {
            throw new Exception('You must select one or more roles', 412);
        }

        $this->userRoleTable->deleteByUser($user->id);

        foreach ($data['role'] as $role) {
            $roleData = [
                'user' => $user,
                'role' => $role,
            ];

            $userRoleEntity = new UserRole();
            $userRoleEntity->setLanguage($this->getLanguage());
            $userRoleEntity->setDbAdapter($this->get('table')->getDb());
            $userRoleEntity->exchangeArray($roleData);

            $this->userRoleTable->save($userRoleEntity);
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

    /**
     * TODO: The following code is copied from AbstractService. To be cleaned up.
     */

    public function getFilteredCount($filter = null, $filterAnd = null)
    {
        return count($this->getList(1, null, null, $filter, $filterAnd));
    }

    /**
     * Returns the list of elements based on the provided filters passed in parameters. Results are paginated (using the
     * $page and $limit combo), except when $limit is <= 0, in which case all results will be returned.
     *
     * @param int $page The page number, starting at 1.
     * @param int $limit The maximum number of elements retrieved, or null to retrieve everything
     * @param string|null $order The order in which elements should be retrieved (['column' => 'ASC/DESC'])
     * @param string|null $filter The array of columns => values which should be filtered (in a WHERE.. OR.. fashion)
     * @param array|null $filterAnd The array of columns => values which should be filtered (in a WHERE.. AND.. fashion)
     *
     * @return array An array of elements based on the provided search query
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $filterJoin = $filterLeft = null;
        if (is_callable(array($this->get('entity'), 'getFiltersForService'), false, $name)) {
            [$filterJoin, $filterLeft] = $this->get('entity')->getFiltersForService();
        }
        return $this->get('table')->fetchAllFiltered(
            array_keys($this->get('entity')->getJsonArray()),
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd,
            $filterJoin,
            $filterLeft
        );
    }
}
