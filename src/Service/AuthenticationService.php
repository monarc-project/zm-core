<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Exception;
use Monarc\Core\Adapter\Authentication as AuthenticationAdapter;
use Monarc\Core\Storage\Authentication as AuthenticationStorage;

/**
 * Authentication Service
 *
 * Class AuthenticationService
 * @package Monarc\Core\Service
 */
class AuthenticationService
{
    /** @var AuthenticationStorage */
    private $authenticationStorage;

    /** @var AuthenticationAdapter */
    private $authenticationAdapter;

    public function __construct(
        AuthenticationStorage $authenticationStorage,
        AuthenticationAdapter $authenticationAdapter
    ) {
        $this->authenticationStorage = $authenticationStorage;
        $this->authenticationAdapter = $authenticationAdapter;
    }


    /**
     * @param array $data The posted data (login/password)
     * @param string|null $token Reference variable in which the token value will be set
     * @param string|null $uid Reference variable in which the user ID will be set
     * @param string|null $language Reference variable in which the user language will be set
     *
     * @return bool True if the authentication succeeded, false otherwise
     *
     * @throws Exception
     */
    public function authenticate($data, &$token = null, &$uid = null, &$language = null)
    {
        if (!empty($data['login']) && !empty($data['password'])) {
            $res = $this->authenticationAdapter
                ->setIdentity($data['login'])
                ->setCredential($data['password'])
                ->authenticate();

            if ($res->isValid()) {
                $user = $this->authenticationAdapter->getUser();
                $token = uniqid(bin2hex(openssl_random_pseudo_bytes(random_int(20, 40))), true);
                $uid = $user->get('id');
                $language = $user->get('language');
                $this->authenticationStorage->addItem($token, $user);

                return true;
            }
        }

        return false;
    }

    /**
     * Disconnects an user and invalidates the token
     *
     * @param array $data Array with 'token'
     *
     * @return bool True if the token existed and got removed, false otherwise
     */
    public function logout($data)
    {
        if (!empty($data['token']) && $this->authenticationStorage->hasItem($data['token'])) {
            $this->authenticationStorage->removeItem($data['token']);

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
    public function checkConnect($data)
    {
        if (!empty($data['token']) && $this->authenticationStorage->hasItem($data['token'])) {
            $dd = $this->authenticationStorage->getItem($data['token']);
            if ($dd->get('dateEnd')->getTimestamp() < time()) {
                $this->logout($data);
                return false;
            }

            $this->authenticationStorage->replaceItem($data['token'], $dd);

            return true;
        }

        return false;
    }
}
