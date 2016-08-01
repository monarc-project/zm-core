<?php

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;

class UniqueDocModel extends AbstractValidator
{
	protected $options = array(
		'adapter' => null,
		'category' => 0,
		'id' => 0,
	);

	const ALREADYUSED = "ALREADYUSED";

	protected $messageTemplates = array(
		self::ALREADYUSED => 'This category is already used',
	);
	public function __construct(array $options = array()){
       parent::__construct($options);
    }

	public function isValid($value){
		if(empty($this->options['adapter'])){
			return false;
		}else{
			$res = $this->options['adapter']->getRepository('\MonarcCore\Model\Entity\DocModel')->findOneByCategory($value);
			if(!empty($res) && $this->options['id'] != $res->get('id')){
				$this->error(self::ALREADYUSED);
				return false;
			}
		}
		return true;
	}
}
