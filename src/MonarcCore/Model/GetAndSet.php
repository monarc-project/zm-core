<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */
namespace MonarcCore\Model;

/**
 * Simple helper for elements for get & set property
 * Trait GetAndSet
 * @package MonarcCore\Model
 */
trait GetAndSet
{
    /**
     * Test if property exist for this element
     * @param string $key The name of the property
     * @return boolean Return true if property exist
     */
    public function __isset($key){
        return property_exists($this,$key);
    }
    /**
     * Get property
     * @param string $key The name of the property
     * @return mixed The value of the property
     */
    public function __get($key){
        if($this->__isset($key)){
            return $this->{$key};
        }else{
            return null;
        }
    }
    /**
     * Set value to property
     * @param string $key The name of the property
     * @param mixed $value The value of the property
     * @return mixed The element
     */
    public function __set($key,$value){
        if($this->__isset($key)){
            $this->{$key} = $value;
        }
        return $this;
    }

    /**
     * Get property (alias of __get)
     * @param string $key The name of the property
     * @return mixed The value of the property
     */
    public function get($key){
        return $this->__get($key);
    }

    /**
     * Set value to property (alias of __set)
     * @param string $key The name of the property
     * @param mixed $value The value of the property
     * @return mixed The element
     */
    public function set($key,$value){
        if (!property_exists($this, $key)) {
            http_response_code(500);
            die("EXCEPTION: Trying to magically set $key, but the property doesn't exist in " . get_class($this));
        }

        return $this->__set($key,$value);
    }
}
