<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

/**
 * User Profile Service Factory
 *
 * Class UserProfileServiceFactory
 * @package Monarc\Core\Service
 */
class UserProfileServiceFactory extends AbstractServiceFactory
{
    protected $ressources = [
        'table' => 'Monarc\Core\Model\Table\UserTable',
        'entity' => 'Monarc\Core\Model\Entity\User',
        'securityService' => '\Monarc\Core\Service\SecurityService',
    ];
}