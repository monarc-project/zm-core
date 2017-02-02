<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Controller;

/**
 * Authentication Controller Factory
 *
 * Class AuthenticationControllerFactory
 * @package MonarcCore\Controller
 */
class AuthenticationControllerFactory extends AbstractControllerFactory
{
    protected $serviceName = '\MonarcCore\Service\AuthenticationService';
}