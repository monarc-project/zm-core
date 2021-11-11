<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\UserNotLoggedInException;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Table\UserTable;

/**
 * User profile Service
 *
 * Class UserProfileService
 * @package Monarc\Core\Service
 */
class UserProfileService
{
    private UserTable $userTable;

    private ConnectedUserService $connectedUserService;

    public function __construct(UserTable $userTable, ConnectedUserService $connectedUserService)
    {
        $this->userTable = $userTable;
        $this->connectedUserService = $connectedUserService;
    }

    /**
     * @throws ORMException
     * @throws UserNotLoggedInException
     */
    public function updateMyData(array $data): UserSuperClass
    {
        $connectedUser = $this->connectedUserService->getConnectedUser();
        if (!empty($data['new'])
            && !empty($data['confirm'])
            && !empty($data['old'])
            && $data['new'] === $data['old']
            && password_verify($data['confirm'], $connectedUser->getPassword())
        ) {
            $connectedUser->setPassword($data['new']);
        }

        if (isset($data['firstname'])) {
            $connectedUser->setFirstname($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $connectedUser->setLastname($data['lastname']);
        }
        if (isset($data['email'])) {
            $connectedUser->setEmail($data['email']);
        }
        if (isset($data['language'])) {
            $connectedUser->setLanguage((int)$data['language']);
        }
        if (isset($data['mospApiKey'])) {
            $connectedUser->setMospApiKey($data['mospApiKey']);
        }

        $connectedUser->setUpdater($connectedUser->getEmail());

        $this->userTable->save($connectedUser);

        return $connectedUser;
    }

    /**
     * @throws ORMException
     * @throws UserNotLoggedInException
     */
    public function deleteMe(int $userId): void
    {
        $this->userTable->remove($this->connectedUserService->getConnectedUser());
    }
}
