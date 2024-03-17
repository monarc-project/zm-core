<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Entity\UserSuperClass;
use Monarc\Core\Table\UserTable;

class UserProfileService
{
    private UserTable $userTable;

    private UserSuperClass $connectedUser;

    public function __construct(UserTable $userTable, ConnectedUserService $connectedUserService)
    {
        $this->userTable = $userTable;
        $this->connectedUser = $connectedUserService->getConnectedUser();
    }

    public function updateMyData(array $data): UserSuperClass
    {
        if (!empty($data['new'])
            && !empty($data['confirm'])
            && !empty($data['old'])
            && $data['new'] === $data['old']
            && password_verify($data['confirm'], $this->connectedUser->getPassword())
        ) {
            $this->connectedUser->setPassword($data['new']);
        }

        if (isset($data['firstname'])) {
            $this->connectedUser->setFirstname($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $this->connectedUser->setLastname($data['lastname']);
        }
        if (isset($data['email'])) {
            $this->connectedUser->setEmail($data['email']);
        }
        if (isset($data['language'])) {
            $this->connectedUser->setLanguage((int)$data['language']);
        }
        if (isset($data['mospApiKey'])) {
            $this->connectedUser->setMospApiKey($data['mospApiKey']);
        }

        $this->connectedUser->setUpdater($this->connectedUser->getEmail());

        $this->userTable->save($this->connectedUser);

        return $this->connectedUser;
    }

    public function deleteMe(): void
    {
        $this->userTable->remove($this->connectedUser);
    }
}
