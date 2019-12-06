<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Table\UserRoleTable;
use Monarc\Core\Model\Table\UserTokenTable;
use Zend\Http\Header\GenericHeader;

/**
 * User Role Service
 *
 * Class UserRoleService
 * @package Monarc\Core\Service
 */
class UserRoleService
{
    /** @var UserRoleTable */
    protected $userRoleTable;

    /** @var UserTokenTable */
    protected $userTokenTable;

    public function __construct(
        UserRoleTable $userRoleTable,
        UserTokenTable $userTokenTable
    ) {
        $this->userRoleTable = $userRoleTable;
        $this->userTokenTable = $userTokenTable;
    }

    /**
     * TODO: it doesn't really return the entity but an array or false.
     * @inheritdoc
     */
    public function getEntity($id)
    {
        return $this->userRoleTable->get($id);
    }

    /**
     * Get roles by user ID
     * @param int $userId THe user ID
     * @return array The roles
     */
    public function getByUserId($userId)
    {
        return $this->userRoleTable
            ->getRepository()
            ->createQueryBuilder('t')
            ->select(['t.id', 't.role'])
            ->where('t.user = :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get roles by user authentication token
     * @param string $token The authentication token
     * @return array The roles
     * @throws \Exception If token is invalid
     */
    public function getByUserToken($token)
    {
        return $this->getByUserId($this->getUserIdByToken($token));
    }

    /**
     * Get User Id By Token
     * @param string $token The token
     * @return int The user ID
     * @throws \Exception If token is invalid
     */
    protected function getUserIdByToken($token)
    {
        if ($token instanceof GenericHeader) {
            $token = $token->getFieldValue();
        }

        $userToken = $this->userTokenTable
            ->getRepository()
            ->createQueryBuilder('t')
            ->select(['t.id', 'IDENTITY(t.user) as userId', 't.token', 't.dateEnd'])
            ->where('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getResult();

        if (empty($userToken)) {
            throw new Exception('User with the token is not found');
        }

        return $userToken[0]['userId'];
    }
}
