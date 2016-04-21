<?php
namespace MonarcCore\Service;

class ConnectedUserService
{
	protected $connectedUser;

	public function getConnectedUser(){
		return $this->connectedUser;
	}
	public function setConnectedUser($connectedUser){
		if(! $connectedUser instanceof \MonarcCore\Model\Entity\User){
			$connectedUser = new \MonarcCore\Model\Entity\User();
		}
		$this->connectedUser = current($connectedUser->toArray());
		return $this;
	}
}
