<?php

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;

class UniqueEmail extends AbstractValidator
{
	protected $options = array(
		'adapter' => null,
		'id' => 0,
	);

	const ALREADYUSED = "ALREADYUSED";

	protected $messageTemplates = array(
		self::ALREADYUSED => 'This email is already used',
	);
	public function __construct(array $options = array()){
       parent::__construct($options);
    }

	public function isValid($value){

		if(empty($this->options['adapter'])){
			return false;
		}else{
			$res = $this->options['adapter']->getRepository('\MonarcCore\Model\Entity\User')->findOneByEmail($value);
			if(!empty($res) && $this->options['id'] != $res->get('id')){
				$this->error(self::ALREADYUSED);
				return false;
			}
		}
		return true;
	}
}
