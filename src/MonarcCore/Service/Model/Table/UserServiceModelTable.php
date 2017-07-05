<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class UserServiceModelTable
 * @package MonarcCore\Service\Model\Table
 */
class UserServiceModelTable extends AbstractServiceModelTable
{
    protected $dbService = '\MonarcCli\Model\Db';

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $instance = parent::createService($serviceLocator);

        if ($instance !== false) {
            $instance->setUserRoleTable($serviceLocator->get('\MonarcCore\Model\Table\UserRoleTable'));
            $instance->setUserTokenTable($serviceLocator->get('\MonarcCore\Model\Table\UserTokenTable'));
            $instance->setPasswordTokenTable($serviceLocator->get('\MonarcCore\Model\Table\PasswordTokenTable'));
        }
        return $instance;
    }
}