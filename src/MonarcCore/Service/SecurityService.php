<?php
namespace MonarcCore\Service;

class SecurityService extends AbstractService
{
    protected $config;

    public function verifyPwd($pwd,$hash){
        $conf = $this->get('config');
        $salt = isset($conf["monarc"]['salt'])?$conf["monarc"]['salt']:'';
        return password_verify($salt.$pwd,$hash);
    }

    public function hashPwd($pwd){
        $conf = $this->get('config');
        $salt = isset($conf["monarc"]['salt'])?$conf["monarc"]['salt']:'';
        return password_hash($salt.$pwd,PASSWORD_BCRYPT);
    }
}
