<?php

namespace MonarcCore\Model\Entity;

use Zend\Http\Response;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

abstract class AbstractEntity implements InputFilterAwareInterface
{
    use \MonarcCore\Model\GetAndSet;

    protected $inputFilter;
    protected $language;
    protected $dbadapter;
    protected $parameters = array();

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    const IS_GENERIC = 0;
    const IS_SPECIFIC = 1;

    const BACK_OFFICE = 'back';
    const FRONT_OFFICE = 'front';

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function getJsonArray($fields = array())
    {
        if (empty($fields)) {
            return get_object_vars($this);
        } else {
            $output = array();
            foreach ($fields as $field) {
                $output[$field] = $this->get($field);
            }

            return $output;
        }
    }

    public function setDbAdapter($dbadapter){
        if ($dbadapter == null) {
            throw new \Exception("Trying to call setDbAdapter with a null adapter");
        }

        $this->dbadapter = $dbadapter;

        return $this;
    }
    public function getDbAdapter(){
        return $this->dbadapter;
    }

    public function getLanguage()
    {
        return empty($this->language)?1:$this->language;
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function exchangeArray(array $options, $partial = false)
    {
        $filter = $this->getInputFilter($partial)
            ->setData($options)
            ->setValidationGroup(InputFilterInterface::VALIDATE_ALL);

        $isValid = $filter->isValid();

        if(!$isValid){
            $field_errors = array();

            foreach ($filter->getInvalidInput() as $field => $error) {
                foreach ($error->getMessages() as $message) {
                    if (!empty($field)) {
                        $field_errors[] = $message;
                    }
                }
            }

            throw new \Exception(implode(", ", $field_errors), '412');
        }

        $options = $filter->getValues();
        foreach($options as $k => $v){
            if (!is_null($v) && $this->__isset($k)) {
                $this->set($k, $v);
            }
        }

        return $this;
    }

    public function toArray()
    {
      return get_object_vars($this);
    }

    public function getInputFilter($partial = false){
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $attributes = get_object_vars($this);
            foreach($attributes as $k => $v){
                switch($k){
                    case 'id':
                        $inputFilter->add(array(
                            'name' => 'id',
                            'required' => false,
                            'filters' => array(
                                array('name' => 'ToInt',),
                            ),
                            'validators' => array(),
                        ));
                        break;
                    case 'updatedAt':
                    case 'updater':
                    case 'createdAt':
                    case 'creator':
                    case 'inputFilter':
                    case 'dbadapter':
                    case 'parameters':
                        break;
                    default:
                        $inputFilter->add(array(
                            'name' => $k,
                            'required' => false,
                            'allow_empty' => true,
                            'continue_if_empty' => true,
                            'filters' => array(),
                            'validators' => array(),
                        ));
                        break;
                }
            }
            $this->inputFilter = $inputFilter;
        }
        return $this->inputFilter;
    }

    public function setInputFilter(InputFilterInterface $inputFilter){
        $this->inputFilter = $inputFilter;
        return $this;
    }
}
