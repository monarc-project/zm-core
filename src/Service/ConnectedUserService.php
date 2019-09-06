<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\User;
use Monarc\Core\Model\Entity\UserToken;
use Monarc\Core\Storage\Authentication as AuthenticationStorage;
use Zend\Http\PhpEnvironment\Request;

/**
 * Determines and returns the system logged in user.
 *
 * Class ConnectedUserService
 * @package Monarc\Core\Service
 */
class ConnectedUserService
{
    /** @var User|null */
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

    public function getConnectedUser(): ?User
    {
        if ($this->connectedUser === null) {
            $token = $this->request->getHeader('token');
            if (!empty($token)) {
                /** @var UserToken $userToken */
                $userToken = $this->authenticationStorage->getItem($token->getFieldValue());
                if ($userToken) {
                    $this->connectedUser = $userToken->getUser();
                }
            }
        }

        return $this->connectedUser;
    }
}
