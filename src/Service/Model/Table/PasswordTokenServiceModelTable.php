<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service\Model\Table;

use Monarc\Core\Model\DbCli;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class PasswordTokenServiceModelTable
 * @package Monarc\Core\Service\Model\Table
 */
class PasswordTokenServiceModelTable extends AbstractServiceModelTable
{
    protected $dbService = DbCli::class;

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        // TODO: Password tokens can be stored in for instance in redis by multiple reasons, one is lifetime of the token, second faster storage.
        return new \Monarc\Core\Model\Table\PasswordTokenTable($serviceLocator->get($this->dbService));
    }
}
