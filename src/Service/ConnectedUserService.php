<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\Core\Model\Entity\UserSuperClass;
use Laminas\Http\PhpEnvironment\Request;
use Monarc\Core\Table\UserTokenTable;

/**
 * Determines and returns the system logged-in user.
 *
 * Class ConnectedUserService
 * @package Monarc\Core\Service
 */
class ConnectedUserService
{
    protected ?UserSuperClass $connectedUser = null;

    private Request $request;

    private UserTokenTable $userTokenTable;

    public function __construct(Request $request, UserTokenTable $userTokenTable)
    {
        $this->request = $request;
        $this->userTokenTable = $userTokenTable;
    }

    /**
     * Returns User's object instance when user is logged-in.
     */
    public function getConnectedUser(): ?UserSuperClass
    {
        if ($this->connectedUser === null) {
            $token = $this->request->getHeader('token');
            if (!empty($token)) {
                $userToken = $this->userTokenTable->findByToken($token->getFieldValue());
                if ($userToken !== null) {
                    $this->connectedUser = $userToken->getUser();
                }
            }
        }

        return $this->connectedUser;
    }
}
