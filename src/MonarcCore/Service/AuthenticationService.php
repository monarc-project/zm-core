<?php
namespace MonarcCore\Service;

class AuthenticationService extends AbstractService
{
	protected $userTable;

    protected $storage;
    protected $adapter;

    public function __construct($serviceFactory = null)
    {
        if(is_array($serviceFactory)){
            foreach($serviceFactory as $k => $v){
                $this->set($k,$v);
            }
        }
    }

    public function hash($pwd){
    	return password_hash($pwd,PASSWORD_BCRYPT); // TODO: concaténé un salt privé
    }

    public function verify($pwd,$hash){
    	return password_verify($pwd,$hash);
    }

    public function authenticate($data, &$token = null)
    {
        if(!empty($data['login']) && !empty($data['password'])){
            $res = $this->get('adapter')->setIdentity($data['login'])->setCredential($data['password'])->setUserTable($this->get('userTable'))->authenticate();
            if($res->isValid()){
                $user = $this->get('adapter')->getUser();
                $token = uniqid('',true);
                $this->get('storage')->addItem($token,array(
                    'id' => $user->get('id'),
                    'email' => $user->get('email'),
                    'firstname' => $user->get('firstname'),
                    'lastname' => $user->get('lastname'),
                ));
                return true;
            }
        }
        return false;
    }

    public function logout($data)
    {
        if(!empty($data['token'])){
            if($this->get('storage')->hasItem($data['token'])){
                $this->get('storage')->removeItem($data['token']);
                return true;
            }
        }
        return false;
    }

    public function checkConnect($data){
        if(!empty($data['token'])){
            if($this->get('storage')->hasItem($data['token'])){
                $dd = $this->get('storage')->getItem($data['token']);
                $this->get('storage')->replaceItem($data['token'],$dd);
                return true;
            }
        }
        return false;
    }
}
