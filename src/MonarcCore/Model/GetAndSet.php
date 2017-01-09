<?php
namespace MonarcCore\Model;

trait GetAndSet
{
    public function __isset($key){
        return property_exists($this,$key);
    }
    public function __get($key){
        if($this->__isset($key)){
            return $this->{$key};
        }else{
            return null;
        }
    }
    public function __set($key,$value){
        if($this->__isset($key)){
            $this->{$key} = $value;
        }
        return $this;
    }

    public function get($key){
        return $this->__get($key);
    }

    public function set($key,$value){
        if (!property_exists($this, $key)) {
            http_response_code(500);
            die("EXCEPTION: Trying to magically set $key, but the property doesn't exist in " . get_class($this));
        }

        return $this->__set($key,$value);
    }
}
