<?php
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
     * Get Connected User
     *
     * @return mixed
     */
    public function getConnectedUser()
    {
        return $this->connectedUser;
    }

    /**
     * Set Connected User
     *
     * @param $connectedUser
     * @return $this
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