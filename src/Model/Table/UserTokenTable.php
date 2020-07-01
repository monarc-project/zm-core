<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\DbCli;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Entity\UserToken;
use Monarc\Core\Model\Entity\UserTokenSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class UserTokenTable
 * @package Monarc\Core\Model\Table
 */
class UserTokenTable extends AbstractEntityTable
{
    public function __construct(DbCli $db, ConnectedUserService $connectedUserService)
    {
        parent::__construct($db, UserToken::class, $connectedUserService);
    }

    /**
     * Delete By User
     *
     * @param $userId
     */
    public function deleteByUser($userId)
    {
        $this->getRepository()->createQueryBuilder('ut')
            ->delete()
            ->where('ut.user = :user')
            ->setParameter(':user', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByToken(string $token): ?UserToken
    {
        return $this->getRepository()
            ->createQueryBuilder('ut')
            ->where('ut.token = :token')
            ->setParameter('token', $token)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUser(UserSuperClass $user): array
    {
        return $this->getRepository()
            ->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws ORMException
     */
    public function saveEntity(UserTokenSuperClass $userToken): void
    {
        $this->db->getEntityManager()->persist($userToken);
        $this->db->getEntityManager()->flush();
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEntity(UserTokenSuperClass $userToken, bool $flush = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($userToken);
        if ($flush) {
            $em->flush();
        }
    }
}
