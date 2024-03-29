<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Entity;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\GetAndSet;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;
use Doctrine\Common\Util\ClassUtils;

/**
 * TODO: detach the class from its children.
 *  - remove the DB dependency;
 *  - extract the logic to a separate service.
 *
 * Class AbstractEntity
 * @package Monarc\Core\Model\Entity
 */
abstract class AbstractEntity implements InputFilterAwareInterface
{
    use GetAndSet;

    protected $inputFilter;
    protected $user_language;
    protected $dbadapter;
    protected $parameters = [];
    protected $squeezeAutoPositionning = false;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_DELETED = 3;

    const MODE_GENERIC = 0;
    const MODE_SPECIFIC = 1;

    const TYPE_PRIMARY = 1;
    const TYPE_SECONDARY = 2;

    const BACK_OFFICE = 'back';
    const FRONT_OFFICE = 'front';

    const CONTEXT_BDC = 'bdc';
    const CONTEXT_ANR = 'anr';

    const SOURCE_COMMON = 'common';
    const SOURCE_CLIENT = 'cli';

    const IMP_POS_START = 1;
    const IMP_POS_END = 2;
    const IMP_POS_AFTER = 3;


    /**
     * @param mixed $obj (extends AbstractEntity OR array)
     */
    public function __construct($obj = null)
    {
        if (!empty($obj)) {
            if (is_object($obj)) {
                if (is_subclass_of($obj, 'Monarc\Core\Model\Entity\AbstractEntity') && method_exists($obj, 'getJsonArray')) {
                    $obj = $obj->getJsonArray();
                    foreach ($obj as $k => $v) {
                        if ($this->__isset($k)) {
                            $this->set($k, $v);
                        }
                    }
                }
            } elseif (is_array($obj)) {
                foreach ($obj as $k => $v) {
                    if ($this->__isset($k)) {
                        $this->set($k, $v);
                    }
                }
            }
        }
    }

    /**
     * Get Array Copy
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    /**
     * Get Json Array
     *
     * @param array $fields
     * @return array
     */
    public function getJsonArray($fields = array())
    {
        $array = get_object_vars($this);
        if (isset($array['uuid'])) {
            $array['uuid'] = (string)$array['uuid'];
        }

        if (empty($fields)) {
            unset(
                $array['inputFilter'],
                $array['user_language'],
                $array['dbadapter'],
                $array['parameters'],
                $array['squeezeAutoPositionning'],
                $array['__initializer__'],
                $array['__cloner__'],
                $array['__isInitialized__']
            );

            return $array;
        }

        unset($array['password']);

        return array_intersect_key($array, array_flip($fields));
        // array_flip — Exchanges all keys with their associated values in
        // an array
        // A warning will be emitted if a value has the wrong type,
        // and the key/value pair in question will not be included in the result.
    }

    /**
     * @param $dbadapter
     * @return $this
     * @throws \Monarc\Core\Exception\Exception
     */
    public function setDbAdapter($dbadapter)
    {
        if ($dbadapter == null) {
            throw new \Monarc\Core\Exception\Exception("Trying to call setDbAdapter with a null adapter");
        }

        $this->dbadapter = $dbadapter;

        return $this;
    }

    /**
     * @return Db
     */
    public function getDbAdapter()
    {
        return $this->dbadapter;
    }

    /**
     * @return int
     */
    public function getLanguage()
    {
        return empty($this->user_language) ? 1 : $this->user_language;
    }

    /**
     * @param $language
     */
    public function setLanguage($language)
    {
        $this->user_language = $language;
    }

    /**
     * @param array $options
     * @param bool $partial
     * @return $this
     * @throws \Monarc\Core\Exception\Exception
     */
    public function exchangeArray(array $options, $partial = false)
    {
        $keys = array_keys($options);
        $keys = array_combine($keys, $keys);

        $filter = $this->getInputFilter($partial)
            ->setData($options)
            ->setValidationGroup(InputFilterInterface::VALIDATE_ALL);

        $isValid = $filter->isValid();

        if (!$isValid) {
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
            throw new \Monarc\Core\Exception\Exception(implode(", ", $field_errors), '412');
        }

       $options = $filter->getValues();

        if (isset($options['implicitPosition'])) {
            if (isset($options['position'])) {
                unset($options['position']);//position should not be sent by HTTP requests
            }
            if (isset($this->parameters['implicitPosition']['root']) && isset($options[$this->parameters['implicitPosition']['root']])) {
                unset($options[$this->parameters['implicitPosition']['root']]);
            }
        }


        //Abstract handling on recursive trees
        $parent_before = $parent_after = null;

        if (!$this->squeezeAutoPositionning && isset($this->parameters['implicitPosition']['field'])) {
            $parent_before = $this->get($this->parameters['implicitPosition']['field']);
            if (is_object($parent_before)) {
                $parent_before = !$parent_before instanceof AnrSuperClass && $parent_before->get('uuid') !== null
                    ? $parent_before->get('uuid')
                    : $parent_before->get('id');
            }
            $parent_after = array_key_exists($this->parameters['implicitPosition']['field'], $options) ? $options[$this->parameters['implicitPosition']['field']] : null;

            $this->parameters['implicitPosition']['changes'] = [
                'parent' => ['before' => $parent_before, 'after' => $parent_after]
            ];
        }

        //Absact handling of positions
        if (!$this->squeezeAutoPositionning && isset($options['implicitPosition'])) {
            $this->calculatePosition($options['implicitPosition'], isset($options['previous']) ? $options['previous'] : null, $parent_before, $parent_after, $options);
            unset($options['implicitPosition']);
            unset($options['previous']);
        }
        foreach ($options as $k => $v) {
            if ($this->__isset($k) && isset($keys[$k])) {
                $this->set($k, $v);
            }
        }

        return $this;
    }

    /**
     * @param bool $bool
     */
    public function squeezeAutoPositionning($bool = false)
    {
        $this->squeezeAutoPositionning = $bool;
    }

    /**
     * TODO: Refactor me! Get rid of the DB dependency from entities classes.
     * @param int $mode
     * @param null $previous
     * @param null $parent_before
     * @param null $parent_after
     * @param array $options
     */
    private function calculatePosition($mode = self::IMP_POS_END, $previous = null, $parent_before = null, $parent_after = null, $options = [])
    {
        $fallback = false;
        $initial_position = $this->get('position');

        $isParentable = !isset($this->parameters['isParentRelative']) || $this->parameters['isParentRelative'];

        if ($mode == self::IMP_POS_START) {
            $this->set('position', 1);//heading
        } else if ($mode == self::IMP_POS_AFTER && !empty($previous)) {
            //Get the position of the previous element
            $prec = null;
            if (array_key_exists('uuid', $options)) {
                $prec = $this->getDbAdapter()->getRepository(get_class($this))->createQueryBuilder('t')
                    ->select()
                    ->where('t.uuid = :previousid')
                    ->setParameter(':previousid', $previous);
                    if(array_key_exists('anr', $options) && !is_null($options['anr'])){ //fo with uuid
                      $prec->andWhere('t.anr = :anrid')
                            ->setParameter(':anrid', $options['anr']);
                    }
            } else {
                $prec = $this->getDbAdapter()->getRepository(get_class($this))->createQueryBuilder('t')
                    ->select()
                    ->where('t.id = :previousid')
                    ->setParameter(':previousid', $previous);
            }
            if (!empty($this->parameters['implicitPosition']['subField'])) {
                foreach ($this->parameters['implicitPosition']['subField'] as $k) {
                    $sub = $this->get($k);
                    if (!empty($sub)) {
                        $sub = is_object($sub) ? $sub->get('id') : $sub;
                    } else {
                        $sub = empty($options[$k]) || is_null($options[$k]) ? null : (is_object($options[$k]) ? $options[$k]->get('id') : $options[$k]);
                    }
                    if (is_null($sub)) {
                        $prec->andWhere('t.' . $k . ' IS NULL');
                    } else {
                        $prec->andWhere('t.' . $k . ' = :' . $k)
                            ->setParameter(':' . $k, $sub);
                    }
                }
            }
            $prec = $prec->getQuery()->getSingleResult();
            if ($prec) {
                //we need to be sure that the prec object has the same parent as the $parent_after
                //don't forget that the root value is NULL
                $prec_parent_id = null;

                if ($isParentable) {
                    $identifiers = $prec->get($this->parameters['implicitPosition']['field']) === null ? [] : $this->getDbAdapter()->getClassMetadata(ClassUtils::getRealClass(get_class($prec->get($this->parameters['implicitPosition']['field']))))->getIdentifierFieldNames();
                    if (in_array('uuid', $identifiers)) {
                        $prec_parent_id = $prec->get($this->parameters['implicitPosition']['field']) === null ? null : $prec->get($this->parameters['implicitPosition']['field'])->getUuid();
                    } else {
                        $prec_parent_id = $prec->get($this->parameters['implicitPosition']['field']) === null ? null : $prec->get($this->parameters['implicitPosition']['field'])->get('id');
                    }
                }
                $parent_after_id = (is_array($parent_after)&&array_key_exists('uuid', $parent_after))?$parent_after['uuid']:$parent_after;
                if ($parent_after_id == $prec_parent_id || !$isParentable) {
                    $prec_position = $prec->get('position');
                    $this->set('position', ((!$this->id  && !$this->get('uuid'))|| $parent_before != $parent_after_id || $this->get('position') > $prec_position) ? $prec_position + 1 : $prec_position);
                } else $fallback = true;//end
            } else $fallback = true;//end
        } else $fallback = true;//end

        if ($fallback) {//$mode = end
            $max = 0;
            $qb = $this->getDbAdapter()->getRepository(get_class($this))->createQueryBuilder('t')
                ->select('MAX(t.position)');

            if ($isParentable) {
              if(is_array($parent_after)) //manage fo with uuid key = (anr,uuid)
                {
                  $qb->innerJoin('t.'.$this->parameters['implicitPosition']['field'] ,$this->parameters['implicitPosition']['field']);
                  $qb->where(!is_null($parent_after) ? $this->parameters['implicitPosition']['field'] . '.anr = :parentAnr' : 't.' . $this->parameters['implicitPosition']['field'] . ' IS NULL');
                  $qb->andWhere(!is_null($parent_after) ? $this->parameters['implicitPosition']['field'] . '.uuid = :parentUuid' : 't.' . $this->parameters['implicitPosition']['field'] . ' IS NULL');
                }else {
                $qb->where(!is_null($parent_after) ? 't.' . $this->parameters['implicitPosition']['field'] . ' = :parentid' : 't.' . $this->parameters['implicitPosition']['field'] . ' IS NULL');
                }
                if (!is_null($parent_after)) {
                  if(is_array($parent_after))
                  {
                    $qb->setParameter(':parentAnr', $parent_after['anr']);
                    $qb->setParameter(':parentUuid', $parent_after['uuid']);
                  }else
                    $qb->setParameter(':parentid', $parent_after);
                }

                if (!empty($this->parameters['implicitPosition']['subField'])) {
                    foreach ($this->parameters['implicitPosition']['subField'] as $k) {
                        $sub = $this->get($k);
                        if (!empty($sub)) {
                            $sub = is_object($sub) ? $sub->get('id') : $sub;
                        } else {
                            $sub = empty($options[$k]) || is_null($options[$k]) ? null : (is_object($options[$k]) ? $options[$k]->get('id') : $options[$k]);
                        }
                        if (is_null($sub)) {
                            $qb->andWhere('t.' . $k . ' IS NULL');
                        } else {
                            $qb->andWhere('t.' . $k . ' = :' . $k)
                                ->setParameter(':' . $k, $sub);
                        }
                    }
                }
            }

            $max = $qb->getQuery()->getSingleScalarResult();
            $parent_after_id = (is_array($parent_after)&&array_key_exists('uuid', $parent_after))?$parent_after['uuid']:$parent_after; //in case of uuid to compare just on the uuid

            if ((!$this->id && !$this->get('uuid'))  || $parent_before != $parent_after_id) {
                $this->set('position', $max + 1);
            } else {//internal movement
                $this->set('position', $max);//in this case we're not adding something, no +1
            }
        }
        //assign cache value for brothers & children (algorithm delegated to AbstractEntityTable ::save)
        $this->parameters['implicitPosition']['changes']['position'] = ['before' => $initial_position, 'after' => $this->get('position')];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->getJsonArray();
    }

    /**
     * @param bool $partial
     * @return InputFilter
     */
    public function getInputFilter($partial = false)
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $attributes = get_object_vars($this);
            foreach ($attributes as $k => $v) {
                switch ($k) {
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
                        if (!$this->squeezeAutoPositionning && isset($this->parameters['implicitPosition']['field'])) {
                            $inputFilter->add(array(//TIPs - previous is not a real attribute of the entity
                                'name' => 'previous',
                                'required' => false,
                                'allow_empty' => true,
                                'continue_if_empty' => true,
                                // 'filters' => [['name' => 'ToInt']],
                                'validators' => array()
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
                                            'haystack' => [null, 1, 2, 3], // null: 0 traitement / 1: start / 2: end / 3: after elem
                                        ),
                                        'default' => null,
                                    )
                                )
                            ));
                        }else{
                             $inputFilter->add(array(
                                'name' => 'position',
                                'required' => false,
                                'allow_empty' => true,
                                'continue_if_empty' => true,
                                'filters' => [['name' => 'ToInt']],
                                'validators' => array()
                            ));
                        }
                        break;
                    case 'updatedAt':
                    case 'updater':
                    case 'createdAt':
                    case 'creator':
                    case 'inputFilter':
                    case 'dbadapter':
                    case 'parameters':
                    case 'squeezeAutoPositionning':
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

    /**
     * @param InputFilterInterface $inputFilter
     * @return $this
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;
        return $this;
    }

    /**
     * The method is used in AbstractEntityTable::initTree
     */
    public function addParameterValue(string $key, int $index, $value)
    {
        $this->parameters[$key][$index] = $value;
    }

    /**
     * The method is used to get the initialized tree of parent -> children elements.
     */
    public function getParameterValues(string $name): array
    {
        return $this->parameters[$name] ?? [];
    }

    public function initParametersChanges()
    {
        unset($this->parameters['implicitPosition']['changes']);
    }
}
