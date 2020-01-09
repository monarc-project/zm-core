<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Table\UserTable;

/**
 * User profile Service
 *
 * Class UserProfileService
 * @package Monarc\Core\Service
 */
class UserProfileService
{
    /** @var UserTable */
    private $userTable;

    public function __construct(UserTable $userTable)
    {
        $this->userTable = $userTable;
    }

    public function update(UserSuperClass $user, array $data): UserSuperClass
    {
        if (!empty($data['new'])
            && !empty($data['confirm'])
            && !empty($data['old'])
            && $data['new'] === $data['old']
            && password_verify($data['confirm'], $user->getPassword())
        ) {
            $user->setPassword($data['new']);
        }

        if (isset($data['firstname'])) {
            $user->setFirstname($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $user->setLastname($data['lastname']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['language'])) {
            $user->setLanguage((int)$data['language']);
        }

        $user->setUpdater($this->userTable->getConnectedUser()->getFirstname() . ' '
            . $this->userTable->getConnectedUser()->getLastname());

        $this->userTable->saveEntity($user);

        return $user;
    }

    public function delete($userId)
    {
        $user = $this->userTable->findById($userId);

        $this->userTable->deleteEntity($user);
    }
}
