<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class PasswordTokenServiceModelTable
 * @package MonarcCore\Service\Model\Table
 */
class PasswordTokenServiceModelTable extends AbstractServiceModelTable
{
    protected $dbService = '\MonarcCli\Model\Db';

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new \MonarcCore\Model\Table\PasswordTokenTable($serviceLocator->get($this->dbService));
    }
}
