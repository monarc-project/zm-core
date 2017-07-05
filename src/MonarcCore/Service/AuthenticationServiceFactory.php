<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Authentication Service Factory
 *
 * Class AuthenticationServiceFactory
 * @package MonarcCore\Service
 */
class AuthenticationServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'userTable' => '\MonarcCore\Model\Table\UserTable',
        'storage' => '\MonarcCore\Storage\Authentication',
        'adapter' => '\MonarcCore\Adapter\Authentication',
    ];
}
