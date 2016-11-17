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
    /*
    paramaters: auto position
    'implicitPosition' => array(
        'field' => null, // pivots: string
        'root' => null, // root field (if exist else null or not defined)
    ),
    */

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    const MODE_GENERIC = 0;
    const MODE_SPECIFIC = 1;

    const BACK_OFFICE = 'back';
    const FRONT_OFFICE = 'front';

    const CONTEXT_BDC = 'bdc';
    const CONTEXT_ANR = 'anr';

    const SOURCE_COMMON = 'common';
    const SOURCE_CLIENT = 'cli';

    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function getJsonArray($fields = array())
    {
        if (empty($fields)) {
            $array = get_object_vars($this);
            unset($array['inputFilter']);
            unset($array['language']);
            unset($array['dbadapter']);
            unset($array['parameters']);
            return $array;
        } else {
            return array_intersect_key(get_object_vars($this), array_flip($fields));
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
        $keys = array_keys($options);
        $keys = array_combine($keys,$keys);
        $filter = $this->getInputFilter($partial)
            ->setData($options)
            ->setValidationGroup(InputFilterInterface::VALIDATE_ALL);

        $isValid = $filter->isValid();
        if(!$isValid){
            $field_errors = array();

            foreach ($filter->getInvalidInput() as $field => $error) {
                foreach ($error->getMessages() as $message) {
                    if ($message != 'Value is required and can\'t be empty') {
                        $field_errors[] = $message;
                        break;
                    }
                }

                if (!count($field_errors)) {
                    if (!empty($field)) {
                        $field = strtr($field, ['1' => '', '2' => '', '3' => '', '4' => '']);
                        $field_errors[] = ucfirst($field) . ' is required';
                        break;
                    }
                }
            }
            throw new \Exception(implode(", ", $field_errors), '412');
        }

        $options = $filter->getValues();

        foreach($options as $k => $v){
            if ($this->__isset($k) && isset($keys[$k])) {
                $this->set($k, $v);
            }
        }

        return $this;
    }

    public function toArray()
    {
        return $this->getJsonArray();
        //return get_object_vars($this);
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
                    case 'position':
                        $inputFilter->add(array(
                            'name' => 'position',
                            'required' => false,
                            'allow_empty' => true,
                            'continue_if_empty' => true,
                            'filters' => array(),
                            'validators' => array(),
                        ));
                        $inputFilter->add(array(
                            'name' => 'implicitPosition',
                            'required' => false,
                            'allow_empty' => true,
                            'continue_if_empty' => true,
                            'filters' => array(),
                            'validators' => array(
                                array(
                                    'name' => 'InArray',
                                    'options' => array(
                                        'haystack' => [null,1, 2, 3], // null: 0 traitement / 1: start / 2: end / 3: after elem
                                    ),
                                    'default' => null,
                                ),
                                array(
                                    'name' => 'Callback',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\Callback::INVALID_VALUE => 'Implicit position error ?',
                                        ),
                                        'callback' => function($value, $context = array()){
                                            if(empty($value)){
                                                unset($this->parameters['implicitPosition']);
                                            }else{
                                                $this->parameters['implicitPosition']['value'] = $value;
                                                $this->parameters['implicitPosition']['oldPosition'] = $this->get('position');
                                                
                                                $this->parameters['implicitPosition']['previous'] = null;
                                                // field
                                                if(!empty($this->parameters['implicitPosition']['field'])){
                                                    $of = $this->get($this->parameters['implicitPosition']['field']);
                                                    $this->parameters['implicitPosition']['oldField'][$this->parameters['implicitPosition']['field']] = !empty($of)?(is_object($of)?$of->get('id'):$of):null;
                                                    $this->parameters['implicitPosition']['newField'][$this->parameters['implicitPosition']['field']] = isset($context[$this->parameters['implicitPosition']['field']])?(is_object($context[$this->parameters['implicitPosition']['field']])?$context[$this->parameters['implicitPosition']['field']]->get('id'):$context[$this->parameters['implicitPosition']['field']]):null;
                                                }// else position global sur toute l'appli

                                                $this->parameters['implicitPosition']['newPosition'] = null;
                                                switch ($value) {
                                                    case 1:
                                                        $this->parameters['implicitPosition']['newPosition'] = 1;
                                                        break;
                                                    case 2:
                                                    default:
                                                        $this->parameters['implicitPosition']['newPosition'] = null; // Count lors du save
                                                        break;
                                                    case 3: // Calculé avec le previous
                                                        $this->parameters['implicitPosition']['newPosition'] = null;
                                                        break;
                                                }
                                            }
                                            return true;
                                        },
                                    ),
                                ),
                            ),
                        ));
                        $inputFilter->add(array(
                            'name' => 'previous',
                            'required' => false,
                            'allow_empty' => true,
                            'continue_if_empty' => true,
                            'filters' => array(),
                            'validators' => array(
                                array(
                                    'name' => 'Callback',
                                    'options' => array(
                                        'messages' => array(
                                            \Zend\Validator\Callback::INVALID_VALUE => 'Previous element error ?',
                                        ),
                                        'callback' => function($value, $context = array()){
                                            if(!empty($value) && !empty($this->parameters['implicitPosition']) && !empty($context['implicitPosition']) && $context['implicitPosition'] == 3){
                                                $res = $this->getDbAdapter()->getRepository(get_class($this))->createQueryBuilder('a')
                                                    ->where(' a.id = :id ')
                                                    ->setParameter(':id', $value)
                                                    ->getQuery()
                                                    ->getResult();
                                                if(!empty($res) && count($res) > 0){
                                                    $res = $res[0];

                                                    $this->parameters['implicitPosition']['newPosition'] = $res->get('position') +1;
                                                    $this->parameters['implicitPosition']['previous'] = $res->get('id');

                                                    // field
                                                    if(!empty($this->parameters['implicitPosition']['field'])){
                                                        $of = $res->get($this->parameters['implicitPosition']['field']);
                                                        $this->parameters['implicitPosition']['newField'][$this->parameters['implicitPosition']['field']] = !empty($of)?(is_object($of)?$of->get('id'):$of):null;
                                                    }// else position global sur toute l'appli
                                                }else{
                                                    $this->parameters['implicitPosition']['value'] = 2; // on met à la fin par défaut
                                                }
                                            }
                                            return true;
                                        },
                                    ),
                                ),
                            ),
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
