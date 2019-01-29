<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

/**
 * Authentication Service
 *
 * Class AuthenticationService
 * @package MonarcCore\Service
 */
class AuthenticationService extends AbstractService
{
    protected $userTable;
    protected $storage;
    protected $adapter;

    /**
     * Authenticates the user
     * @param array $data The posted data (login/password)
     * @param string|null $token Reference variable in which the token value will be set
     * @param string|null $uid Reference variable in which the user ID will be set
     * @param string|null $language Reference variable in which the user language will be set
     * @return bool True if the authentication succeeded, false otherwise
     */
    public function authenticate($data, &$token = null, &$uid = null, &$language = null)
    {
        if (!empty($data['login']) && !empty($data['password'])) {
            $res = $this->get('adapter')->setIdentity($data['login'])->setCredential($data['password'])->setUserTable($this->get('userTable'))->authenticate();
            if ($res->isValid()) {
                $user = $this->get('adapter')->getUser();
                $token = uniqid(bin2hex(openssl_random_pseudo_bytes(rand(20, 40))), true);
                $uid = $user->get('id');
                $language = $user->get('language');
                $this->get('storage')->addItem($token, $user);
                return true;
            }
        }
        return false;
    }

    /**
     * Disconnnects an user and invalidates the token
     * @param array $data Array with 'token'
     * @return bool True if the token existed and got removed, false otherwise
     */
    public function logout($data)
    {
        if (!empty($data['token']) && $this->get('storage')->hasItem($data['token'])) {
            $this->get('storage')->removeItem($data['token']);
            return true;
        }
        return false;
    }

    /**
     * Checks if the user is currently connected based on the token passed in $data
     * @param array $data Array with a 'token' key/value
     * @return bool True if the token is valid, false otherwise
     */
    public function checkConnect($data)
    {
        if (!empty($data['token']) && $this->get('storage')->hasItem($data['token'])) {
            $dd = $this->get('storage')->getItem($data['token']);
            if ($dd->get('dateEnd')->getTimestamp() < time()) {
                $this->logout($data);
                return false;
            } else {
                $this->get('storage')->replaceItem($data['token'], $dd);
                return true;
            }
        }
        return false;
    }
}
