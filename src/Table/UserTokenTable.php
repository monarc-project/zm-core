<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NonUniqueResultException;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Entity\UserToken;
use Monarc\Core\Entity\UserTokenSuperClass;

class UserTokenTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = UserToken::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByToken(string $token): ?UserTokenSuperClass
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
}
