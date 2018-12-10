<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use MonarcCore\Model\Table\UserRoleTable;
use Zend\Http\Header\GenericHeader;

/**
 * User Role Service
 *
 * Class UserRoleService
 * @package MonarcCore\Service
 */
class UserRoleService extends AbstractService
{
    protected $userRoleTable;
    protected $userTokenTable;
    protected $userRoleEntity;

    /**
     * @inheritdoc
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $options = [], $filterJoin = null)
    {
        /** @var UserRoleTable $userRoleTable */
        $userRoleTable = $this->get('userRoleTable');

        return $userRoleTable->getRepository()->createQueryBuilder('t')
            ->select(['t.id', 't.role'])
            ->where('t.user = :id')
            ->setParameter(':id', $filter)
            ->getQuery()->getResult();
    }

    /**
     * @inheritdoc
     */
    public function getEntity($id)
    {
        return $this->get('userRoleTable')->get($id);
    }

    /**
     * Get roles by user ID
     * @param int $userId THe user ID
     * @return array The roles
     */
    public function getByUserId($userId)
    {
        /** @var UserRoleTable $userRoleTable */
        $userRoleTable = $this->get('userRoleTable');

        return $userRoleTable->getRepository()->createQueryBuilder('t')
            ->select(['t.id', 't.role'])
            ->where('t.user = :id')
            ->setParameter(':id', $userId)
            ->getQuery()->getResult();
    }

    /**
     * Get roles by user authentication token
     * @param string $token The authentication token
     * @return array The roles
     * @throws \Exception If token is invalid
     */
    public function getByUserToken($token)
    {
        $userId = $this->getUserIdByToken($token);
        return $this->getByUserId($userId);
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

        $userTokenTable = $this->get('userTokenTable');
        $userToken = $userTokenTable->getRepository()->createQueryBuilder('t')
            ->select(['t.id', 'IDENTITY(t.user) as userId', 't.token', 't.dateEnd'])
            ->where('t.token = :token')
            ->setParameter(':token', $token)
            ->getQuery()
            ->getResult();

        if (count($userToken)) {
            return $userToken[0]['userId'];
        } else {
            throw new \MonarcCore\Exception\Exception('No user');
        }
    }
}
