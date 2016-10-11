<?php
namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

class UserTokenServiceModelTable extends AbstractServiceModelTable
{
	protected $dbService = '\MonarcCli\Model\Db';

	public function createService(ServiceLocatorInterface $serviceLocator){
		return new \MonarcCore\Model\Table\UserTokenTable($serviceLocator->get($this->dbService));
	}
}
