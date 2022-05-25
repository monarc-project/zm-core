<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\User;
use Monarc\Core\Model\Entity\UserSuperClass;

class UserTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, $entityName = User::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByEmail(string $email): UserSuperClass
    {
        $user = $this->getRepository()->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter(':email', $email)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();

        if ($user === null) {
            throw new EntityNotFoundException(sprintf('User with email "%s" does not exist', $email));
        }

        return $user;
    }
}
