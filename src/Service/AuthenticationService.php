<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2022 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use DateTime;
use Exception;
use Monarc\Core\Adapter\Authentication as AuthenticationAdapter;
use Monarc\Core\Storage\Authentication as AuthenticationStorage;

class AuthenticationService
{
    private AuthenticationStorage $authenticationStorage;

    private AuthenticationAdapter $authenticationAdapter;

    public function __construct(
        AuthenticationStorage $authenticationStorage,
        AuthenticationAdapter $authenticationAdapter
    ) {
        $this->authenticationStorage = $authenticationStorage;
        $this->authenticationAdapter = $authenticationAdapter;
    }

    /**
     * @param array $data The posted data (login/password)
     *
     * @return array
     *
     * @throws Exception
     */
    public function authenticate($data): array
    {
        if (!empty($data['login']) && !empty($data['password'])) {
            $token = '';
            if (isset($data['otp'])) {
                $token = $data['otp'];
            } elseif (isset($data['recoveryCode'])) {
                $token = $data['recoveryCode'];
            }

            $res = $this->authenticationAdapter
                ->setIdentity($data['login'])
                ->setCredential($data['password'])
                ->authenticate($token);

            if ($res->isValid()) {
                $user = $this->authenticationAdapter->getUser();
                $token = "2FARequired";
                if ($res->getCode() !== 2) {
                    $token = uniqid(bin2hex(random_bytes(random_int(20, 40))), true);
                    $this->authenticationStorage->addUserToken($token, $user);
                }

                return compact('token', 'user');
            }
        }

        return [];
    }

    /**
     * Disconnects user and invalidates the token.
     *
     * @param array $data Array with 'token'
     *
     * @return bool True if the token existed and got removed, false otherwise
     */
    public function logout($data): bool
    {
        if (!empty($data['token']) && $this->authenticationStorage->hasUserToken($data['token'])) {
            $this->authenticationStorage->removeUserToken($data['token']);

            return true;
        }

        return false;
    }

    /**
     * Checks if the user is currently connected based on the token passed in $data
     *
     * @param array $data Array with a 'token' key/value
     *
     * @return bool True if the token is valid, false otherwise
     */
    public function checkConnect($data): bool
    {
        if (empty($data['token'])) {
            return false;
        }

        $userToken = $this->authenticationStorage->getUserToken($data['token']);
        if ($userToken !== null && $userToken->getDateEnd() > new DateTime()) {
            $this->authenticationStorage->refreshUserToken($userToken);

            return true;
        }

        if ($userToken !== null) {
            $this->authenticationStorage->removeUserToken($data['token']);
        }

        return false;
    }
}
