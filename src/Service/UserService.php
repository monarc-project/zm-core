<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\ActionForbiddenException;
use Monarc\Core\InputFormatter\FormattedInputParams;
use Monarc\Core\Entity\User;
use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Table\UserTable;

class UserService
{
    protected UserSuperClass $connectedUser;

    protected int $defaultLanguageIndex;

    public function __construct(
        protected UserTable $userTable,
        ConnectedUserService $connectedUserService,
        array $config
    ) {
        $this->connectedUser = $connectedUserService->getConnectedUser();
        $this->defaultLanguageIndex = (int)$config['defaultLanguageIndex'];
    }

    public function getList(FormattedInputParams $params): array
    {
        /** @var UserSuperClass[] $users */
        $users = $this->userTable->findByParams($params);

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'status' => $user->getStatus(),
                'language' => $user->getLanguage(),
                'role' => $user->getRolesArray(),
                'isTwoFactorAuthEnabled' => $user->isTwoFactorAuthEnabled(),
            ];
        }

        return $result;
    }

    public function getCount(FormattedInputParams $params): int
    {
        return $this->userTable->countByParams($params);
    }

    public function getData(int $id): array
    {
        /** @var UserSuperClass $user */
        $user = $this->userTable->findById($id);

        return [
            'id' => $user->getId(),
            'status' => $user->getStatus(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'language' => $user->getLanguage(),
            'role' => $user->getRolesArray(),
            'isTwoFactorAuthEnabled' => $user->isTwoFactorAuthEnabled(),
        ];
    }

    public function create(array $data): UserSuperClass
    {
        if (empty($data['language'])) {
            $data['language'] = $this->defaultLanguageIndex;
        }
        $data['creator'] = $this->connectedUser->getEmail();

        $user = new User($data);
        $this->userTable->save($user);

        return $user;
    }

    public function update(int $userId, array $data): UserSuperClass
    {
        $user = $this->getUpdatedUser($userId, $data);

        $this->userTable->save($user);

        return $user;
    }

    public function patch(int $userId, array $data): UserSuperClass
    {
        $user = $this->getUpdatedUser($userId, $data);

        $this->userTable->save($user);

        return $user;
    }

    public function delete(int $userId)
    {
        /** @var UserSuperClass $user */
        $user = $this->userTable->findById($userId);
        if ($user->isSystemUser()) {
            throw new ActionForbiddenException('You can not remove the "System" user');
        }

        $this->userTable->remove($user);
    }

    public function resetTwoFactorAuth(int $userId): UserSuperClass
    {
        /** @var UserSuperClass $user */
        $user = $this->userTable->findById($userId);

        $user->setTwoFactorAuthEnabled(false)
            ->setSecretKey('')
            ->setRecoveryCodes([]);
        $user->setUpdater($this->connectedUser->getEmail());

        $this->userTable->save($user);

        return $user;
    }

    protected function getUpdatedUser(int $userId, array $data): UserSuperClass
    {
        /** @var UserSuperClass $user */
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
            $user->setStatus((int)$data['status']);
        }
        /*
         * TODO: dateStart and dateEnd are not used for the moment.
        if (isset($data['dateEnd'])) {
            $data['dateEnd'] = new DateTime($data['dateEnd']);
        }
        if (isset($data['dateStart'])) {
            $data['dateStart'] = new DateTime($data['dateStart']);
        }
        */
        $user->setUpdater($this->connectedUser->getEmail());

        if (!empty($data['role'])) {
            $user->setRoles($data['role']);
        }

        return $user;
    }
}
