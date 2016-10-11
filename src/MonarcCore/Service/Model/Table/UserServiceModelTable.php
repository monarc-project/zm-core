<?php
namespace MonarcCore\Service\Model\Table;

use Zend\ServiceManager\ServiceLocatorInterface;

class UserServiceModelTable extends AbstractServiceModelTable
{
	protected $dbService = '\MonarcCli\Model\Db';
	public function createService(ServiceLocatorInterface $serviceLocator){
		$instance = parent::createService($serviceLocator);

		if($instance !== false){
	        $instance->setUserRoleTable($serviceLocator->get('\MonarcCore\Model\Table\UserRoleTable'));
	        $instance->setUserTokenTable($serviceLocator->get('\MonarcCore\Model\Table\UserTokenTable'));
	        $instance->setPasswordTokenTable($serviceLocator->get('\MonarcCore\Model\Table\PasswordTokenTable'));
	    }
        return $instance;
	}
}
