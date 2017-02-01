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
     * Verify Password
     *
     * @param $pwd
     * @param $hash
     * @return bool
     */
    public function verifyPwd($pwd, $hash)
    {
        $conf = $this->get('config');
        $salt = isset($conf["monarc"]['salt']) ? $conf["monarc"]['salt'] : '';
        return password_verify($salt . $pwd, $hash);
    }

    /**
     * Hash Password
     *
     * @param $pwd
     * @return bool|string
     */
    public function hashPwd($pwd)
    {
        $conf = $this->get('config');
        $salt = isset($conf["monarc"]['salt']) ? $conf["monarc"]['salt'] : '';
        return password_hash($salt . $pwd, PASSWORD_BCRYPT);
    }
}