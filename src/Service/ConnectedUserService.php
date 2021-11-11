<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Exception\UserNotLoggedInException;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Storage\Authentication as AuthenticationStorage;
use Laminas\Http\PhpEnvironment\Request;

/**
 * Determines and returns the system logged in user.
 *
 * Class ConnectedUserService
 * @package Monarc\Core\Service
 */
class ConnectedUserService
{
    /** @var UserSuperClass|null */
    protected $connectedUser;

    /** @var Request */
    private $request;

    /** @var AuthenticationStorage */
    private $authenticationStorage;

    public function __construct(Request $request, AuthenticationStorage $authenticationStorage)
    {
        $this->request = $request;
        $this->authenticationStorage = $authenticationStorage;
    }

    /**
     * Returns User's object instance when user is logged-in.
     *
     * @throws UserNotLoggedInException
     */
    public function getConnectedUser(): UserSuperClass
    {
        if ($this->connectedUser === null) {
            $token = $this->request->getHeader('token', null);
            if ($token !== null) {
                $userToken = $this->authenticationStorage->getUserToken($token->getFieldValue());
                if ($userToken !== null) {
                    $this->connectedUser = $userToken->getUser();
                }
            }

            if ($this->connectedUser === null) {
                throw new UserNotLoggedInException();
            }
        }

        return $this->connectedUser;
    }
}
