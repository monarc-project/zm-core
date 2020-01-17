<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\User;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Table\UserTable;

/**
 * User Service
 *
 * Class UserService
 * @package Monarc\Core\Service
 */
class UserService
{
    protected $filterColumns = ['firstname', 'lastname', 'email'];

    /** @var UserTable */
    protected $userTable;

    /** @var int */
    protected $defaultLanguageIndex;

    public function __construct(
        UserTable $userTable,
        array $config
    ) {
        $this->userTable = $userTable;
        $this->defaultLanguageIndex = $config['defaultLanguageIndex'];
    }

    /**
     * @throws ORMException
     */
    public function create(array $data): UserSuperClass
    {
        if (empty($data['language'])) {
            $data['language'] = $this->defaultLanguageIndex;
        }

        $data['creator'] = $this->userTable->getConnectedUser()->getFirstname() . ' '
            . $this->userTable->getConnectedUser()->getLastname();

        $user = new User($data);
        $this->userTable->saveEntity($user);

        return $user;
    }

    /**
     * @throws EntityNotFoundException
     * @throws ORMException
     */
    public function update(int $userId, array $data): UserSuperClass
    {
        $user = $this->getUpdatedUser($userId, $data);

        $this->userTable->saveEntity($user);

        return $user;
    }

    /**
     * @throws EntityNotFoundException
     * @throws ORMException
     */
    public function patch($userId, $data): UserSuperClass
    {
        $user = $this->getUpdatedUser($userId, $data);

        $this->userTable->saveEntity($user);

        return $user;
    }

    /**
     * @throws Exception
     */
    public function getByEmail(string $email): UserSuperClass
    {
        return $this->userTable->getByEmail($email);
    }

    /**
     * TODO: The following code is copied from AbstractService. To be cleaned up.
     */

    public function getFilteredCount($filter = null, $filterAnd = null)
    {
        return count($this->getList(1, null, null, $filter, $filterAnd));
    }

    /**
     * TODO: remove me and use table for filters queries.
     *
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

        return $this->userTable->fetchAllFiltered(
            ['id', 'status', 'firstname', 'lastname', 'email', 'language', 'roles'],
            $page,
            $limit,
            $this->parseFrontendOrder($order),
            $this->parseFrontendFilter($filter, $this->filterColumns),
            $filterAnd,
            $filterJoin,
            $filterLeft
        );
    }

    /**
     * @inheritdoc
     */
    public function delete(int $userId)
    {
        $user = $this->userTable->findById($userId);
        if ($user->isSystemUser()) {
            throw new Exception('You can not remove the "System" user', 412);
        }

        $this->userTable->deleteEntity($user);
    }

    /**
     * @throws EntityNotFoundException
     * @throws ORMException
     */
    protected function getUpdatedUser(int $userId, array $data): UserSuperClass
    {
        $user = $this->userTable->findById($userId);

        if (isset($data['firstname'])) {
            $user->setFirstname($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $user->setLastname($data['lastname']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['status'])) {
            $user->setStatus($data['status']);
        }
        $user->setUpdater($this->userTable->getConnectedUser()->getFirstname() . ' '
            . $this->userTable->getConnectedUser()->getLastname());

        if (!empty($data['role'])) {
            $user->setRoles($data['role']);
        }

        return $user;
    }

    /**
     * Parses the filter value coming from the frontend and returns an array of columns to filter. Basically, this
     * method will construct an array where the keys are the columns, and the value of each key is the filter parameter.
     * @param string $filter The value to look for
     * @param array $columns An array of columns in which the value is searched
     * @return array Key/pair array as per the description
     */
    protected function parseFrontendFilter($filter, $columns = [])
    {
        $output = [];
        if ($filter !== null && $columns) {
            foreach ($columns as $c) {
                $output[$c] = $filter;
            }
        }

        return $output;
    }

    /**
     * Parses the order from the frontend in order to build SQL-compliant ORDER BY. The order passed by the frontend
     * is the name of the column that we should sort the data with, eventually prepended with '-' when we need it in
     * descending order (ascending otherwise).
     * @param string $order The order requested by the frontend/API call
     * @return array|null Returns null if $order is null, otherwise an array ['columnName', 'ASC/DESC']
     */
    protected function parseFrontendOrder($order)
    {
        // Fields in the ORM are using a CamelCase notation, whereas JSON fields use underscores. Convert it here in
        // case there's a value not filtered.
        if (strpos($order, '_') !== false) {
            $o = explode('_', $order);
            $order = '';
            foreach ($o as $n => $oo) {
                if ($n <= 0) {
                    $order = $oo;
                } else {
                    $order .= ucfirst($oo);
                }
            }
        }

        if ($order === null) {
            return null;
        } elseif (strpos($order, '-') === 0) {
            return [substr($order, 1), 'DESC'];
        }

        return [$order, 'ASC'];
    }
}
