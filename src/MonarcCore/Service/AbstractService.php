<?php
namespace MonarcCore\Service;

abstract class AbstractService
{
    use \MonarcCore\Model\GetAndSet;

    protected $serviceFactory;

    public function __construct($serviceFactory = null)
    {
        if($serviceFactory instanceof \MonarcCore\Model\Table\AbstractEntityTable || $serviceFactory instanceof \MonarcCore\Model\Entity\AbstractEntity){
            $this->serviceFactory = $serviceFactory;
        }elseif(is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                if($v instanceof \MonarcCore\Model\Table\AbstractEntityTable || $v instanceof \MonarcCore\Model\Entity\AbstractEntity){
                    $this->set($k,$v);
                }
            }
        }
    }

    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }
}
