<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\DbCli;
use Monarc\Core\Model\Entity\User;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Service\ConnectedUserService;
use Throwable;

/**
 * Class UserTable
 * @package Monarc\Core\Model\Table
 */
class UserTable extends AbstractEntityTable
{
    /** @var UserTokenTable */
    private $userTokenTable;

    /** @var PasswordTokenTable */
    private $passwordTokenTable;

    public function __construct(
        DbCli $db,
        ConnectedUserService $connectedUserService,
        UserTokenTable $userTokenTable,
        PasswordTokenTable $passwordTokenTable
    ) {
        parent::__construct($db, $this->getEntityClass(), $connectedUserService);

        $this->userTokenTable = $userTokenTable;
        $this->passwordTokenTable = $passwordTokenTable;
    }

    public function getEntityClass(): string
    {
        return User::class;
    }

    /**
     * TODO: remove after refactoring.
     */
    public function fetchAllFiltered(
        $fields = [],
        $page = 1,
        $limit = 25,
        $order = null,
        $filter = null,
        $filterAnd = null,
        $filterJoin = null,
        $filterLeft = null
    ) {
        /** @var User[] $users */
        $users = $this->getDb()->fetchAllFiltered(
            $this->getEntityClass(),
            $page,
            $limit,
            $order,
            $filter,
            $filterAnd,
            $filterJoin,
            $filterLeft
        );
        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'status' => $user->getStatus(),
                'language' => $user->getLanguage(),
                'role' => $user->getRoles(),
            ];
        }

        return $result;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findById(int $id): UserSuperClass
    {
        /** @var UserSuperClass|null $user */
        $user = $this->getRepository()->find($id);
        if ($user === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $user;
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

    /**
     * TODO: move it to an abstract table class (also rename the method to save) when we remove AbstractEntityTable.
     * @throws ORMException
     */
    public function saveEntity(UserSuperClass $user): void
    {
        // TODO: EntityManager has to be injected instead of the db class, actually we can remove db classes at all.
        $em = $this->db->getEntityManager();
        $em->persist($user);
        $em->flush();
    }

    /**
     * @throws Throwable
     */
    public function deleteEntity(UserSuperClass $user): bool
    {
        $this->getDb()->beginTransaction();

        try {
            // TODO: has to be done automatically by cascade delete relation execution by doctrine.
            $this->userTokenTable->deleteByUser($user->getId());
            $this->passwordTokenTable->deleteByUser($user->getId());

            $em = $this->getDb()->getEntityManager();
            $em->remove($user);
            $em->flush();

            $this->getDb()->commit();
        } catch (Throwable $e) {
            $this->getDb()->rollBack();

            throw $e;
        }

        // TODO: Should be void, we will remove when abstract implementation is removed.
        return true;
    }
}
