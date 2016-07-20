<?php
namespace MonarcCore\Controller;

use Zend\View\Model\JsonModel;

class AuthenticationController extends AbstractController
{
	public function create($data){

		$t = null;
		$uid = null;
		if ($this->getService()->authenticate($data, $t, $uid)) {
			$this->response->setStatusCode(200);
			return new JsonModel(array('token' => $t, 'uid' => $uid));
		} else {
			$this->response->setStatusCode(405);
			return new JsonModel(array());
		}
	}

	public function deleteList($data){

		$request = $this->getRequest();
		$token = $request->getHeader('token');

		$this->getService()->logout(array('token'=>$token->getFieldValue()));
		return new JsonModel(array());
	}
}
