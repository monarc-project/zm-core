<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\User;

/**
 * Connected User Service
 *
 * Class ConnectedUserService
 * @package Monarc\Core\Service
 */
class ConnectedUserService
{
    /** @var array */
    protected $connectedUser = [];

    /**
     * @return array The current connected user information
     */
    public function getConnectedUser()
    {
        return $this->connectedUser;
    }

    /**
     * Sets the currently connected user information
     * @param User $connectedUser The current user
     * @return $this For chaining calls
     */
    public function setConnectedUser($connectedUser)
    {
        if (!$connectedUser instanceof User) {
            $connectedUser = new User();
        }

        $this->connectedUser = $connectedUser->toArray();

        return $this;
    }
}
