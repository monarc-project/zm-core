<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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
     * Authenticate
     *
     * @param $data
     * @param null $token
     * @param null $uid
     * @param null $language
     * @return bool
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
     * Logout
     *
     * @param $data
     * @return bool
     */
    public function logout($data)
    {
        if (!empty($data['token'])) {
            if ($this->get('storage')->hasItem($data['token'])) {
                $this->get('storage')->removeItem($data['token']);
                return true;
            }
        }
        return false;
    }

    /**
     * Check Connect
     *
     * @param $data
     * @return bool
     */
    public function checkConnect($data)
    {
        if (!empty($data['token'])) {
            if ($this->get('storage')->hasItem($data['token'])) {
                $dd = $this->get('storage')->getItem($data['token']);
                if ($dd->get('dateEnd')->getTimestamp() < time()) {
                    $this->logout($data);
                    return false;
                } else {
                    $this->get('storage')->replaceItem($data['token'], $dd);
                    return true;
                }
            }
        }
        return false;
    }
}