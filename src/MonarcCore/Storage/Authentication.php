<?php
namespace MonarcCore\Storage;

use MonarcCore\Model\Entity\UserToken;

use Zend\Authentication\Storage\StorageInterface;
use Zend\Cache\Storage\Adapter;

class Authentication implements StorageInterface
{
    protected $userTokenTable;
    protected $config;

    public function setUserTokenTable(\MonarcCore\Model\Table\UserTokenTable $userTokenTable){
        $this->userTokenTable = $userTokenTable;
        return $this;
    }

    public function getUserTokenTable(){
        return $this->userTokenTable;
    }

    public function setConfig($config){
        $this->config = $config;
        return $this;
    }

    public function getConfig(){
        return $this->config;
    }

    public function addItem($key, $value)
    {
        $this->clearItems();
        if(!$this->hasItem($key) && !empty($value)){
            $conf = $this->getConfig();
            $ttl = isset($conf['monarc']['ttl'])?$conf['monarc']['ttl']:20;

            $tt = new UserToken();
            $tt->exchangeArray(array(
                'token' => $key,
                'user' => $value,
                'dateEnd' => new \DateTime("+$ttl min"), // date('Y-m-d H:i:s',strtotime("+$ttl min")),
            ));
            $this->getUserTokenTable()->save($tt);
        }
        return false;
    }

    public function getItem($key, & $success = null, & $casToken = null)
    {
        $success = false;
        if($this->hasItem($key)){
            $token = $this->getUserTokenTable()->getRepository()->findOneByToken($key);
            if(!empty($token)){
                $casToken = $key;
                $success = true;
                return $token;
            }
        }
        return null;
    }

    public function replaceItem($key, $value)
    {
        $t = $this->getItem($key,$success);
        if($success){
            $conf = $this->getConfig();
            $ttl = isset($conf['monarc']['ttl'])?$conf['monarc']['ttl']:20;

            $t->set('dateEnd',new \DateTime("+$ttl min"));
            $this->getUserTokenTable()->save($t);
            return true;
        }
        return false;
    }

    public function hasItem($key)
    {
        $token = $this->getUserTokenTable()->getRepository()->findOneByToken($key);
        if (null === $token) {
            return false;
        }
        if (is_scalar($token)) {
            return mb_strlen($token)>0;
        }
        if (is_object($token) && method_exists($token, '__toString') && !$token instanceof \Countable) {
            return mb_strlen((string) $token)>0;
        }
        if ($token instanceof \Countable || is_array($token)) {
            return count($token)>0;
        }
        return true;
    }

    public function removeItem($key)
    {
        $t = $this->getItem($key,$success);
        if($success){
            $this->getUserTokenTable()->delete($t->get('id'));
            return true;
        }
        return false;
    }

    protected function clearItems(){
        $tokenIds = $this->getUserTokenTable()->getRepository()->createQueryBuilder('t')
            ->select('t.id')
            ->where('t.dateEnd < :d')
            ->setParameter(':d',date('Y-m-d H:i:s'))
            ->getQuery()->getResult();
        foreach($tokenIds as $i){
            $this->getUserTokenTable()->delete($i['id']);
        }
    }

    public function isEmpty(){}
    public function read(){}
    public function write($contents){}
    public function clear(){}
}
