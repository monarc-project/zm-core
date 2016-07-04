<?php

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;

class UniqueCode extends AbstractValidator
{
	protected $options = array(
        'entity' => null
	);

	const ALREADYUSED = "ALREADYUSED";

	protected $messageTemplates = array(
		self::ALREADYUSED => 'This code is already used',
	);
	public function __construct(array $options = array()){
       parent::__construct($options);
    }

	public function isValid($value){

		if(empty($this->options['entity'])){
			return false;
		}else{
            $res = $this->options['entity']->getDbAdapter()->getRepository(get_class($this->options['entity']))->findOneByCode($value);
			if(!empty($res) && $this->options['entity']->getId() != $res->get('id')){
				$this->error(self::ALREADYUSED);
				return false;
			}
		}
		return true;
	}
}
