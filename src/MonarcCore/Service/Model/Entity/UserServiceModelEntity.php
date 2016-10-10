<?php
namespace MonarcCore\Service\Model\Entity;

use Zend\ServiceManager\ServiceLocatorInterface;

class UserServiceModelEntity extends AbstractServiceModelEntity
{
	public function createService(ServiceLocatorInterface $serviceLocator){
		$instance = parent::createService($serviceLocator);
		$conf = $serviceLocator->get('Config');
        $salt = isset($conf['monarc']['salt'])?$conf['monarc']['salt']:'';
        $instance->setUserSalt($salt);
        return $instance;
	}
}
