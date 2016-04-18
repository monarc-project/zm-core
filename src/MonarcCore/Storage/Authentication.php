<?php
namespace MonarcCore\Storage;

use Zend\Authentication\Storage\StorageInterface;
use Zend\Cache\Storage\Adapter;

class Authentication implements StorageInterface
{
    public function isEmpty() {
    }

    public function write($contents) {
    }

    public function clear() {
    }

    public function read() {
        return null;
    }

    public function addItem($token, $value)
    {
        return false;
    }
    public function getItem($token)
    {
        return false;
    }
    public function replaceItem($token, $value)
    {
        return false;
    }
    public function hasItem($token)
    {
        return false;
    }
    public function removeItem($token)
    {
        return false;
    }
}
