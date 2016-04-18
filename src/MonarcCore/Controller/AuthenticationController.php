<?php
namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

class AuthenticationController extends AbstractController
{
	public function create($data){
		$t = null;
		if($this->getService()->authenticate($data,$t)){
			$this->response->setStatusCode(200);
			return new JsonModel(array('token'=>$t));
		}else{
			$this->response->setStatusCode(405);
			return new JsonModel(array());
		}
	}
}
