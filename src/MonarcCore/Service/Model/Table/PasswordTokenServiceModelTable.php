<?php
namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

class PasswordTokenServiceModelTable extends AbstractServiceModelTable
{
    protected $dbService = '\MonarcCli\Model\Db';

    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new \MonarcCore\Model\Table\PasswordTokenTable($serviceLocator->get($this->dbService));
    }
}
