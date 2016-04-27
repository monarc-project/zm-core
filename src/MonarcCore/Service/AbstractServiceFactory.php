<?php
namespace MonarcCore\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractServiceFactory implements FactoryInterface
{
    protected $ressources;

    public function createService(ServiceLocatorInterface $serviceLocator){
        $c = substr(get_class($this),0,-7);
        if(class_exists($c)){
            $fn = $this->getRessources();
            if(empty($fn)){
                return new $c();    
            }elseif(is_array($fn)){
                $sls = array();
                foreach ($fn as $k => $v) {
                    $sls[$k] = $serviceLocator->get($v);
                }
                return new $c($sls);
            }else{
                return new $c($serviceLocator->get($fn));
            }
        }else{
            return false;
        }
    }

    public function getRessources(){
        return $this->ressources;
    }
}