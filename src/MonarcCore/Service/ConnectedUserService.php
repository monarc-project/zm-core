<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Connected User Service
 *
 * Class ConnectedUserService
 * @package MonarcCore\Service
 */
class ConnectedUserService
{
    protected $connectedUser;

    /**
     * @return array The current connected user information
     */
    public function getConnectedUser()
    {
        return $this->connectedUser;
    }

    /**
     * Sets the currently connected user information
     * @param \MonarcCore\Model\Entity\User $connectedUser The current user
     * @return $this For chaining calls
     */
    public function setConnectedUser($connectedUser)
    {
        if (!$connectedUser instanceof \MonarcCore\Model\Entity\User) {
            $connectedUser = new \MonarcCore\Model\Entity\User();
        }
        $this->connectedUser = $connectedUser->toArray();
        return $this;
    }
}