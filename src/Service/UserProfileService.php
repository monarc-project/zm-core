<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
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
        // unauthorized fields
        unset($data['dateStart']);
        unset($data['dateEnd']);
        unset($data['status']);

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

        // TODO: think how to be with updatedAt and updater. We need to check if there are changes in the entity.

        $this->userTable->saveEntity($user);

        return $user;
    }

    public function delete($userId)
    {
        $user = $this->userTable->findById($userId);

        $this->userTable->deleteEntity($user);
    }
}
