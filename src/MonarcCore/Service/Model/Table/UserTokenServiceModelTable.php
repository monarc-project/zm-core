<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class UserTokenServiceModelTable
 * @package MonarcCore\Service\Model\Table
 */
class UserTokenServiceModelTable extends AbstractServiceModelTable
{
    protected $dbService = '\MonarcCli\Model\Db';

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new \MonarcCore\Model\Table\UserTokenTable($serviceLocator->get($this->dbService));
    }
}