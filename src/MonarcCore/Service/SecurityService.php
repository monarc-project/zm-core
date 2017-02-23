<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Security Service
 *
 * Class SecurityService
 * @package MonarcCore\Service
 */
class SecurityService extends AbstractService
{
    protected $config;

    /**
     * Verifies if the password matches the provided hash
     * @param string $pwd The password
     * @param string $hash The password hash
     * @return bool True if it matches, false otherwise
     */
    public function verifyPwd($pwd, $hash)
    {
        $conf = $this->get('config');
        // FIXME: don't allow to disable salting
        $salt = isset($conf["monarc"]['salt']) ? $conf["monarc"]['salt'] : '';
        return password_verify($salt . $pwd, $hash);
    }

    /**
     * Hashes the passed password
     * @param string $pwd The password to hash
     * @return bool|string The hashed password or false in case of error
     */
    public function hashPwd($pwd)
    {
        $conf = $this->get('config');
        // FIXME: don't allow to disable salting
        $salt = isset($conf["monarc"]['salt']) ? $conf["monarc"]['salt'] : '';
        return password_hash($salt . $pwd, PASSWORD_BCRYPT);
    }
}
