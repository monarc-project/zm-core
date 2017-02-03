<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Entity;

use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

/**
 * Class AbstractEntity
 * @package MonarcCore\Model\Entity
 */
abstract class AbstractEntity implements InputFilterAwareInterface
{
    use \MonarcCore\Model\GetAndSet;

    protected $inputFilter;
    protected $user_language;
    protected $dbadapter;
    protected $parameters = array();
    protected $squeezeAutoPositionning = false;
    /*
    paramaters: auto position
    'implicitPosition' => array(
        'field' => null, // pivots: string
        'root' => null, // root field (if exist else null or not defined)
        'subField' => [ <field1>, <field2> ] // optionnal
    ),
    */

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
                if (is_subclass_of($obj, '\MonarcCore\Model\Entity\AbstractEntity') && method_exists($obj, 'getJsonArray')) {
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
        if (empty($fields)) {
            $array = get_object_vars($this);
            unset($array['inputFilter']);
            unset($array['user_language']);
            unset($array['dbadapter']);
            unset($array['parameters']);
            unset($array['squeezeAutoPositionning']);
            return $array;
        } else {
            return array_intersect_key(get_object_vars($this), array_flip($fields));
        }
    }

    /**
     * @param $dbadapter
     * @return $this
     * @throws \Exception
     */
    public function setDbAdapter($dbadapter)
    {
        if ($dbadapter == null) {
            throw new \Exception("Trying to call setDbAdapter with a null adapter");
        }

        $this->dbadapter = $dbadapter;

        return $this;
    }

    /**
     * @return mixed
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
     * @throws \Exception
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
            throw new \Exception(implode(", ", $field_errors), '412');
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
                $parent_before = $parent_before->get('id');
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
            $prec = $this->getDbAdapter()->getRepository(get_class($this))->createQueryBuilder('t')
                ->select()
                ->where('t.id = :previousid')
                ->setParameter(':previousid', $previous);
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
                    $prec_parent_id = is_null($prec->get($this->parameters['implicitPosition']['field'])) ? null : $prec->get($this->parameters['implicitPosition']['field'])->get('id');
                }
                if ($parent_after == $prec_parent_id || !$isParentable) {
                    $prec_position = $prec->get('position');
                    $this->set('position', (!$this->id || $parent_before != $parent_after || $this->get('position') > $prec_position) ? $prec_position + 1 : $prec_position);
                } else $fallback = true;//end
            } else $fallback = true;//end
        } else $fallback = true;//end

        if ($fallback) {//$mode = end
            $max = 0;
            $qb = $this->getDbAdapter()->getRepository(get_class($this))->createQueryBuilder('t')
                ->select('MAX(t.position)');

            if ($isParentable) {
                $qb->where(!is_null($parent_after) ? 't.' . $this->parameters['implicitPosition']['field'] . ' = :parentid' : 't.' . $this->parameters['implicitPosition']['field'] . ' IS NULL');

                if (!is_null($parent_after)) {
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

            if (!$this->id || $parent_before != $parent_after) {
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
        //return get_object_vars($this);
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
                                'filters' => [['name' => 'ToInt']],
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
     * @param $k
     * @param $v
     */
    public function setParameter($k, $v)
    {
        $this->parameters[$k] = $v;
    }

    /**
     * @param $k
     * @return mixed|null
     */
    public function getParameter($k)
    {
        return isset($this->parameters[$k]) ? $this->parameters[$k] : null;
    }

    public function initParametersChanges()
    {
        unset($this->parameters['implicitPosition']['changes']);
    }
}