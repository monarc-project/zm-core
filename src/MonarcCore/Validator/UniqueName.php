<?php

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;

class UniqueName extends AbstractValidator
{
	protected $options = array(
        'entity' => null,
        'field' => 'name1',
	);

	const ALREADYUSED = "ALREADYUSED";

	protected $messageTemplates = array(
		self::ALREADYUSED => 'This name is already used',
	);

	public function __construct(array $options = array()){
       parent::__construct($options);
    }

    /**
     * Is valid
     *
     * @param mixed $value
     * @return bool
     */
	public function isValid($value){

		if (empty($this->options['entity'])) {
			return false;
		} else {
		    $entity = $this->options['entity'];
            $method = 'findOneBy' . ucfirst($this->options['field']);
            $res = $entity->getDbAdapter()->getRepository(get_class($entity))->$method($value);
			if(!empty($res) && $entity->getId() != $res->get('id')){
				$this->error(self::ALREADYUSED);
				return false;
			}
		}

		return true;
	}
}
