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
            $fields = [
                $this->options['field'] => $value,
                'anr' => ($entity->anr) ? $entity->anr->id : null,
            ];
            $res = $entity->getDbAdapter()->getRepository(get_class($entity))->findOneBy($fields);

            if(!empty($res) && $entity->getId() != $res->get('id')){
                $this->error(self::ALREADYUSED);
                return false;
            }
        }

        return true;
    }
}
