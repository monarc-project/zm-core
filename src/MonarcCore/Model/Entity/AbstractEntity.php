<?php

namespace MonarcCore\Model\Entity;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

abstract class AbstractEntity implements InputFilterAwareInterface
{
    use \MonarcCore\Model\GetAndSet;
    protected $inputFilter;

    protected $dbadapter;
    protected $parameters = array();

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

    public function setDbAdpater($dbadapter){
        $this->dbadapter = $dbadapter;
        return $this;
    }
    public function getDbAdapter(){
        return $this->dbadapter;
    }

    public function exchangeArray(array $options)
    {
        $filter = $this->getInputFilter()
            ->setData($options)
            ->setValidationGroup(InputFilterInterface::VALIDATE_ALL);
        $isValid = $filter->isValid();
        if(!$isValid){
            // TODO: ici on pourrait remonter la liste des champs qui ne vont pas
            throw new \Exception("Invalid data set");
        }
        $options = $filter->getValues();
        foreach($options as $k => $v){
            $this->set($k,$v);
        }
        return $this;
    }

    public function toArray()
    {
      return array(get_object_vars($this));
    }

    public function getInputFilter(){
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
