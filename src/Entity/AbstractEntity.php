<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Entity;

use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\GetAndSet;
use Laminas\InputFilter\InputFilter;
use Laminas\InputFilter\InputFilterAwareInterface;
use Laminas\InputFilter\InputFilterInterface;
use Doctrine\Common\Util\ClassUtils;

/**
 * TODO: remove it when all the usages are cleaned up.
 */
abstract class AbstractEntity implements InputFilterAwareInterface
{
    use GetAndSet;

    protected $inputFilter;
    protected $user_language;
    protected $dbadapter;
    protected $parameters = [];
    protected $squeezeAutoPositionning = false;

    const IMP_POS_START = 1;
    const IMP_POS_END = 2;
    const IMP_POS_AFTER = 3;

    /**
     * @param mixed $obj (extends AbstractEntity OR array)
     */
    public function __construct($obj = null)
    {
        if (!empty($obj)) {
            if (\is_object($obj)) {
                if ($obj instanceof self && method_exists($obj, 'getJsonArray')) {
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
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    public function getJsonArray($fields = [])
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
     *
     * @return $this
     * @throws Exception
     */
    public function setDbAdapter($dbadapter)
    {
        if ($dbadapter === null) {
            throw new Exception('Trying to call setDbAdapter with a null adapter');
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
     *
     * @return $this
     * @throws Exception
     */
    public function exchangeArray(array $options, $partial = false)
    {
        $keys = array_keys($options);
        $keys = array_combine($keys, $keys);

        $filter = $this->getInputFilter($partial)->setData($options)->setValidationGroup(
            InputFilterInterface::VALIDATE_ALL
        );

        $isValid = $filter->isValid();

        if (!$isValid) {
            $field_errors = [];

            foreach ($filter->getInvalidInput() as $field => $error) {
                foreach ($error->getMessages() as $message) {
                    if ($message !== 'Value is required and can\'t be empty') {
                        $field_errors[] = $message;
                        break;
                    }
                }

                if (empty($field_errors)) {
                    if (!empty($field)) {
                        $field = strtr($field, ['1' => '', '2' => '', '3' => '', '4' => '']);
                        $field_errors[] = ucfirst($field) . ' is required';
                        break;
                    }
                }
            }
            throw new Exception(implode(', ', $field_errors), '412');
        }

        $options = $filter->getValues();

        //position should not be sent by HTTP requests
        if (isset($options['implicitPosition'])) {
            if (isset($options['position'])) {
                unset($options['position']);
            }
            if (isset(
                $this->parameters['implicitPosition']['root'],
                $options[$this->parameters['implicitPosition']['root']]
            )) {
                unset($options[$this->parameters['implicitPosition']['root']]);
            }
        }

        //Abstract handling on recursive trees
        $parent_after = null;
        $parent_before = null;

        if (!$this->squeezeAutoPositionning && isset($this->parameters['implicitPosition']['field'])) {
            $parent_before = $this->get($this->parameters['implicitPosition']['field']);
            if (\is_object($parent_before)) {
                $parent_before = !$parent_before instanceof AnrSuperClass && method_exists(
                    $parent_before,
                    'getUuid'
                ) && $parent_before->getUuid() !== null
                    ? $parent_before->getUuid()
                    : $parent_before->getId();
            }
            $parent_after = $options[$this->parameters['implicitPosition']['field']] ?? null;

            $this->parameters['implicitPosition']['changes'] = [
                'parent' => ['before' => $parent_before, 'after' => $parent_after],
            ];
        }

        // Abstract handling of positions
        if (!$this->squeezeAutoPositionning && isset($options['implicitPosition'])) {
            $this->calculatePosition(
                $options['implicitPosition'],
                $options['previous'] ?? null,
                $parent_before,
                $parent_after,
                $options
            );
            unset($options['implicitPosition'], $options['previous']);
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
     * @param array $options
     */
    private function calculatePosition(
        $mode = self::IMP_POS_END,
        $previous = null,
        $parent_before = null,
        $parent_after = null,
        $options = []
    ) {
        $fallback = false;
        $initial_position = $this->get('position');

        $isParentable = !isset($this->parameters['isParentRelative']) || $this->parameters['isParentRelative'];

        if ($mode === self::IMP_POS_START) {
            $this->setPosition(1);
        } elseif ($mode === self::IMP_POS_AFTER && !empty($previous)) {
            //Get the position of the previous element
            if (\array_key_exists('uuid', $options)) {
                $prec = $this->getDbAdapter()->getRepository(\get_class($this))->createQueryBuilder('t')->select()
                    ->where('t.uuid = :previousid')->setParameter(':previousid', $previous);
                if (\array_key_exists('anr', $options) && $options['anr'] !== null) {
                    //fo with uuid
                    $prec->andWhere('t.anr = :anrid')->setParameter(':anrid', $options['anr']);
                }
            } else {
                $prec = $this->getDbAdapter()->getRepository(\get_class($this))->createQueryBuilder('t')->select()
                    ->where('t.id = :previousid')->setParameter(':previousid', $previous);
            }
            if (!empty($this->parameters['implicitPosition']['subField'])) {
                foreach ($this->parameters['implicitPosition']['subField'] as $k) {
                    $sub = $this->get($k);
                    if (!empty($sub)) {
                        $sub = \is_object($sub) ? $sub->getId() : $sub;
                    } else {
                        $subValue = \is_object($options[$k]) ? $options[$k]->getId() : $options[$k];
                        $sub = empty($options[$k]) ? null : $subValue;
                    }
                    if ($sub === null) {
                        $prec->andWhere('t.' . $k . ' IS NULL');
                    } else {
                        $prec->andWhere('t.' . $k . ' = :' . $k)->setParameter(':' . $k, $sub);
                    }
                }
            }
            $prec = $prec->getQuery()->getSingleResult();
            if ($prec) {
                //we need to be sure that the prec object has the same parent as the $parent_after
                //don't forget that the root value is NULL
                $prec_parent_id = null;

                if ($isParentable) {
                    $identifiers = $prec->get($this->parameters['implicitPosition']['field']) === null
                        ? []
                        : $this->getDbAdapter()->getClassMetadata(ClassUtils::getRealClass(
                            \get_class($prec->get($this->parameters['implicitPosition']['field']))
                        ))->getIdentifierFieldNames();
                    if (\in_array('uuid', $identifiers, true)) {
                        $prec_parent_id = $prec->get($this->parameters['implicitPosition']['field']) === null
                            ? null
                            : $prec->get($this->parameters['implicitPosition']['field'])->getUuid();
                    } else {
                        $prec_parent_id = $prec->get($this->parameters['implicitPosition']['field']) === null
                            ? null
                            : $prec->get($this->parameters['implicitPosition']['field'])->getId();
                    }
                }
                $parent_after_id = (is_array($parent_after) && array_key_exists('uuid', $parent_after))
                    ? $parent_after['uuid'] : $parent_after;
                if ($parent_after_id == $prec_parent_id || !$isParentable) {
                    $prec_position = $prec->getPosition();
                    $position = (((!method_exists($this, 'getId') || !$this->getId())
                        && (!method_exists($this, 'getUuid') || !$this->getUuid()))
                        || $parent_before !== $parent_after_id
                        || $this->getPosition() > $prec_position)? $prec_position + 1 : $prec_position;
                    $this->setPosition($position);
                } else {
                    $fallback = true;
                }
            } else {
                $fallback = true;
            }
        } else {
            $fallback = true;
        }

        if ($fallback) {
            $qb = $this->getDbAdapter()
                ->getRepository(\get_class($this))
                ->createQueryBuilder('t')
                ->select('MAX(t.position)');

            if ($isParentable) {
                //manage fo with uuid key = (anr,uuid)
                if (\is_array($parent_after)) {
                    $qb->innerJoin(
                        't.' . $this->parameters['implicitPosition']['field'],
                        $this->parameters['implicitPosition']['field']
                    );
                    $qb->where(
                        !empty($parent_after)
                            ? $this->parameters['implicitPosition']['field'] . '.anr = :parentAnr'
                            : 't.' . $this->parameters['implicitPosition']['field'] . ' IS NULL'
                    );
                    $qb->andWhere(
                        !empty($parent_after)
                            ? $this->parameters['implicitPosition']['field'] . '.uuid = :parentUuid'
                            : 't.' . $this->parameters['implicitPosition']['field'] . ' IS NULL'
                    );
                } else {
                    $qb->where(
                        !empty($parent_after)
                            ? 't.' . $this->parameters['implicitPosition']['field'] . ' = :parentid'
                            : 't.' . $this->parameters['implicitPosition']['field'] . ' IS NULL'
                    );
                }
                if ($parent_after !== null) {
                    if (\is_array($parent_after)) {
                        $qb->setParameter(':parentAnr', $parent_after['anr']);
                        $qb->setParameter(':parentUuid', $parent_after['uuid']);
                    } else {
                        $qb->setParameter(':parentid', $parent_after);
                    }
                }

                if (!empty($this->parameters['implicitPosition']['subField'])) {
                    foreach ($this->parameters['implicitPosition']['subField'] as $k) {
                        $sub = $this->get($k);
                        if (!empty($sub)) {
                            $sub = \is_object($sub) ? $sub->get('id') : $sub;
                        } else {
                            $subValue = \is_object($options[$k]) ? $options[$k]->getId() : $options[$k];
                            $sub = empty($options[$k]) ? null : $subValue;
                        }
                        if ($sub === null) {
                            $qb->andWhere('t.' . $k . ' IS NULL');
                        } else {
                            $qb->andWhere('t.' . $k . ' = :' . $k)->setParameter(':' . $k, $sub);
                        }
                    }
                }
            }

            $max = $qb->getQuery()->getSingleScalarResult();
            $parent_after_id = \is_array($parent_after) && \array_key_exists('uuid', $parent_after)
                ? $parent_after['uuid']
                : $parent_after; //in case of uuid to compare just on the uuid

            if (((!method_exists($this, 'getId') || !$this->getId())
                && (!method_exists($this, 'getUuid') || !$this->getUuid()))
                || $parent_before !== $parent_after_id
            ) {
                $this->setPosition($max + 1);
            } else {//internal movement
                $this->setPosition($max); //in this case we're not adding something, no +1
            }
        }
        //assign cache value for brothers & children (algorithm delegated to AbstractEntityTable ::save)
        $this->parameters['implicitPosition']['changes']['position'] = [
            'before' => $initial_position,
            'after' => $this->getPosition(),
        ];
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
     *
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
                        $inputFilter->add([
                            'name' => 'id',
                            'required' => false,
                            'filters' => [
                                ['name' => 'ToInt',],
                            ],
                            'validators' => [],
                        ]);
                        break;
                    case 'position':
                        if (!$this->squeezeAutoPositionning && isset($this->parameters['implicitPosition']['field'])) {
                            //TIPs - previous is not a real attribute of the entity
                            $inputFilter->add([
                                'name' => 'previous',
                                'required' => false,
                                'allow_empty' => true,
                                'continue_if_empty' => true,
                                'validators' => [],
                            ]);
                            $inputFilter->add([
                                'name' => 'implicitPosition',
                                'required' => false,
                                'allow_empty' => true,
                                'continue_if_empty' => true,
                                'filters' => [],
                                'validators' => [
                                    [
                                        'name' => 'InArray',
                                        'options' => [
                                            'haystack' => [null, 1, 2, 3],
                                            // null: 0 traitement / 1: start / 2: end / 3: after elem
                                        ],
                                        'default' => null,
                                    ],
                                ],
                            ]);
                        } else {
                            $inputFilter->add([
                                'name' => 'position',
                                'required' => false,
                                'allow_empty' => true,
                                'continue_if_empty' => true,
                                'filters' => [['name' => 'ToInt']],
                                'validators' => [],
                            ]);
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
                        $inputFilter->add([
                            'name' => $k,
                            'required' => false,
                            'allow_empty' => true,
                            'continue_if_empty' => true,
                            'filters' => [],
                            'validators' => [],
                        ]);
                        break;
                }
            }
            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }

    /**
     * @param InputFilterInterface $inputFilter
     *
     * @return $this
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        $this->inputFilter = $inputFilter;

        return $this;
    }

    public function initParametersChanges()
    {
        unset($this->parameters['implicitPosition']['changes']);
    }
}
