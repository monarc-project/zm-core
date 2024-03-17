<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\NonUniqueResultException;
use Monarc\Core\Exception\UserNotLoggedInException;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Table\UserTable;
use Monarc\Core\Table\UserTokenTable;

class UserRoleService
{
    protected UserTable $userTable;

    protected UserTokenTable $userTokenTable;

    public function __construct(
        UserTable $userTable,
        UserTokenTable $userTokenTable
    ) {
        $this->userTable = $userTable;
        $this->userTokenTable = $userTokenTable;
    }

    /**
     * @throws UserNotLoggedInException
     * @throws NonUniqueResultException
     */
    public function getUserRolesByToken(string $token): array
    {
        $userToken = $this->userTokenTable->findByToken($token);
        if ($userToken === null) {
            throw new UserNotLoggedInException();
        }

        return $this->getUserRolesByUser($userToken->getUser());
    }

    public function getUserRolesByUserId(int $userId): array
    {
        /** @var UserSuperClass $user */
        $user = $this->userTable->findById($userId);

        return $this->getUserRolesByUser($user);
    }

    private function getUserRolesByUser(UserSuperClass $user): array
    {
        $userRoles = [];
        foreach ($user->getRoles() as $role) {
            $userRoles[] = ['id' => $role->getId(), 'role' => $role->getRole()];
        }

        return $userRoles;
    }
}
