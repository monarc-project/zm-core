<?php
namespace MonarcCore\Filter;

use Zend\Filter\AbstractFilter;

class Password extends AbstractFilter
{
	protected $options = array(
		'salt' => '',
	);

    public function __construct($options = null){
        $this->options['salt'] = isset($options['salt'])?$options['salt']:'';
    }

	public function filter($value)
    {
    	if(!empty($value)){
    		$value = password_hash($this->options['salt'].$value,PASSWORD_BCRYPT);
    	}
    	return $value;
    }
}
