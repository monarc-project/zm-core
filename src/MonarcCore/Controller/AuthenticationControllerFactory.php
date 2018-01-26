<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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