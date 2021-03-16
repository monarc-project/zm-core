<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model;

use Monarc\Core\Exception\Exception;

/**
 * Simple helper for elements for get & set property
 * Trait GetAndSet
 * @package Monarc\Core\Model
 */
trait GetAndSet
{
    /**
     * Test if property exist for this element
     *
     * @param string $name The name of the property
     *
     * @return boolean Return true if property exist
     */
    public function __isset($name)
    {
        return property_exists($this, $name);
    }

    /**
     * Get property
     *
     * @param string $name The name of the property
     *
     * @return mixed The value of the property
     */
    public function __get($name)
    {
        if ($this->__isset($name)) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * Set value to property
     *
     * @param string $name The name of the property
     * @param mixed $value The value of the property
     *
     * @return mixed The element
     */
    public function __set($name, $value)
    {
        if ($this->__isset($name)) {
            $this->{$name} = $value;
        }

        return $this;
    }

    /**
     * Get property (alias of __get)
     *
     * @param string $name The name of the property
     *
     * @return mixed The value of the property
     */
    public function get($name)
    {
        return $this->__get($name);
    }

    /**
     * Set value to property (alias of __set)
     *
     * @param string $name The name of the property
     * @param mixed $value The value of the property
     *
     * @return mixed The element
     * @throws Exception
     */
    public function set($name, $value)
    {
        if (!property_exists($this, $name)) {
            throw new Exception(
                'Trying to magically set $name, but the property does not exist in ' . \get_class($this)
            );
        }

        return $this->__set($name, $value);
    }
}
