<?php
namespace MonarcCore\Adapter;

use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result;

class Authentication extends AbstractAdapter
{
    protected $userTable;
    protected $user;

    public function setUserTable(\MonarcCore\Model\Table\UserTable $userTable){
        $this->userTable = $userTable;
        return $this;
    }
    public function getUserTable(){
        return $this->userTable;
    }

    public function setUser($user){
        $this->user = $user;
        return $this;
    }
    public function getUser(){
        return $this->user;
    }

    public function authenticate()
    {
        $identity = $this->getIdentity();
        $credential = $this->getCredential();
        $users = $this->getUserTable()->getRepository()->findByEmail($identity);
        switch (count($users)) {
            case 0:
                return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, $this->getIdentity());
                break;
            case 1:
                $user = current($users);
                //$now = mktime();
                // TODO: faire le test sur dateStart && dateEnd
                if($user->get('status')){
                    if($this->verifyPwd($credential,$user->get('password'))){
                        $this->setUser($user);
                        return new Result(Result::SUCCESS, $this->getIdentity());
                    }else{
                        return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->getIdentity());
                    }
                }else{
                    return new Result(Result::FAILURE_UNCATEGORIZED, $this->getIdentity());
                }
                break;
            default:
                return new Result(Result::FAILURE_IDENTITY_AMBIGUOUS, $this->getIdentity());
                break;
        }
    }

    public function verifyPwd($pwd,$hash){
        return password_verify($pwd,$hash);
    }
}
