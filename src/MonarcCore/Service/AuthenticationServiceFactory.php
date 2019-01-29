<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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
