<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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
        return password_verify($pwd, $hash);
    }

    /**
     * Hashes the passed password
     * @param string $pwd The password to hash
     * @return bool|string The hashed password or false in case of error
     */
    public function hashPwd($pwd)
    {
        return password_hash($pwd, PASSWORD_BCRYPT);
    }
}
