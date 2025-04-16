<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Entity\User;
use Monarc\Core\Entity\UserSuperClass;

class UserTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = User::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByEmail(string $email, ?string $excludeEmail = null): UserSuperClass
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email);
        if ($excludeEmail !== null) {
            $queryBuilder->andWhere('u.email <> :excludeEmail')
                ->setParameter('excludeEmail', $excludeEmail);
        }

        $user = $queryBuilder->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($user === null) {
            throw new EntityNotFoundException(sprintf('User with email "%s" does not exist', $email));
        }

        return $user;
    }
}
