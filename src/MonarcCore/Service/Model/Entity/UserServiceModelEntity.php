<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service\Model\Entity;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class UserServiceModelEntity
 * @package MonarcCore\Service\Model\Entity
 */
class UserServiceModelEntity extends AbstractServiceModelEntity
{
    protected $ressources = ['setDbAdapter' => '\MonarcCli\Model\Db'];
}