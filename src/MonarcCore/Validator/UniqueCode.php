<?php

namespace MonarcCore\Validator;

use Zend\Validator\AbstractValidator;

class UniqueCode extends AbstractValidator
{
    protected $options = array(
        'entity' => null,
    );

    const ALREADYUSED = "ALREADYUSED";

    protected $messageTemplates = array(
        self::ALREADYUSED => 'This code is already used',
    );

    public function isValid($value){

        if(empty($this->options['entity'])){
            return false;
        }else{
            $fields = [
                'code' => $value,
            ];
            if(isset($this->options['entity']->anr)){
                $fields['anr'] = (isset($this->options['entity']->anr->id)) ? $this->options['entity']->anr->id : null;
            }
            $res = $this->options['entity']->getDbAdapter()->getRepository(get_class($this->options['entity']))->findOneBy($fields);
            if(!empty($res) && $this->options['entity']->getId() != $res->get('id')){
                $this->error(self::ALREADYUSED);
                return false;
            }
        }
        return true;
    }
}
