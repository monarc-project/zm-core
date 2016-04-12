<?php

namespace MonarcCore\Model\Entity;

abstract class AbstractEntity
{
    use \MonarcCore\Model\GetAndSet;

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function getJsonArray()
    {
        return get_object_vars($this);
    }

    public function exchangeArray(array $options)
    {
        foreach($options as $k => $v){
            $this->set($k,$v);
        }
        return $this;
    }

    public function toArray()
    {
      return array(get_object_vars($this));
    }
}
