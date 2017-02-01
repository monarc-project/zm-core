<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $instance = parent::createService($serviceLocator);
        $conf = $serviceLocator->get('Config');
        $salt = isset($conf['monarc']['salt']) ? $conf['monarc']['salt'] : '';
        $instance->setUserSalt($salt);
        return $instance;
    }
}