<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Model\Entity\User;
use Monarc\Core\Service\ConnectedUserService;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Doctrine\Common\Util\ClassUtils;

/**
 * Class AbstractEntityTable
 * @package Monarc\Core\Model\Table
 */
abstract class AbstractEntityTable
{
    /** @var Db */
    protected $db;

    /** @var string */
    protected $entityClass;

    /** @var string|null */
    protected $language;

    /** @var ConnectedUserService */
    protected $connectedUserService;

    public function __construct(Db $dbService, string $entityClass, ConnectedUserService $connectedUserService)
    {
        $this->db = $dbService;
        $this->entityClass = $entityClass;
        $this->connectedUserService = $connectedUserService;
    }

    /**
     * @return Db
     */
    public function getDb(): Db
    {
        return $this->db;
    }

    /**
     * @return EntityRepository
     */
    public function getRepository(): EntityRepository
    {
        return $this->getDb()->getRepository($this->getEntityClass());
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata(): ClassMetadata
    {
        return $this->getDb()->getClassMetadata($this->getEntityClass());
    }

    /**
     * @return string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getConnectedUser(): ?User
    {
        return $this->connectedUserService->getConnectedUser();
    }

    /**
     * @param array $fields
     *
     * @return array|bool
     */
    public function fetchAll($fields = [])
    {
        $c = $this->getEntityClass();
        if (class_exists($c)) {
            $all = $this->getDb()->fetchAll(new $c());
            $return = array();
            foreach ($all as $a) {
                $return[] = $a->getJsonArray($fields);
            }
            return $return;
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function fetchAllObject()
    {
        $c = $this->getEntityClass();
        if (class_exists($c)) {
            return $this->getDb()->fetchAll(new $c());
        }

        return false;
    }

    /**
     * @param array $fields
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param null $filterAnd
     * @param null $filterJoin
     * @param null $filterLeft
     *
     * @return array|bool
     */
    public function fetchAllFiltered($fields = [], $page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null)
    {
        $class = $this->getEntityClass();

        if (class_exists($class)) {
            $all = $this->getDb()->fetchAllFiltered(new $class(), $page, $limit, $order, $filter, $filterAnd, $filterJoin, $filterLeft);
            $return = array();
            foreach ($all as $a) {
                $return[] = $a->getJsonArray($fields);
            }
            return $return;
        }

        return false;
    }

    /**
     * @return bool|int
     */
    public function count()
    {
        return $this->getDb()->count($this->getEntityClass());
    }

    /**
     * @param null $filter
     * @param null $filterAnd
     * @param null $filterJoin
     * @param null $filterLeft
     *
     * @return bool|mixed
     */
    public function countFiltered($filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null)
    {
        $c = $this->getEntityClass();
        if (class_exists($c)) {
            return $this->getDb()->countFiltered(new $c(), $filter, $filterAnd, $filterJoin, $filterLeft);
        }

        return false;
    }

    /**
     * @param $id
     * @param array $fields
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function get($id, $fields = [])
    {
        $ent = $this->getEntity($id);
        if ($ent !== false) {
            return $ent->getJsonArray($fields);
        }

        return false;
    }

    /**
     * @param $id
     *
     * @return bool|null|object
     *
     * @throws Exception
     * @throws MappingException
     */
    public function getEntity($id)
    {
        $class = $this->getEntityClass();
        if (class_exists($class)) {
            $entity = new $class();
            $entity->setDbAdapter($this->getDb());
            if (is_array($id)) {
                foreach ($id as $key => $value) {
                    $entity->set($key, $value);
                }
            } else {
                $entity->set($this->getClassMetadata()->getSingleIdentifierFieldName(), $id);
            }
            $entity = $this->getDb()->fetch($entity);
            if (!$entity) {
                throw new Exception('Entity does not exist', 412);
            }
            $entity->initParametersChanges();

            return $entity;
        }

        return false;
    }

    /**
     * @param array $fields
     * @param array $orderBy
     *
     * @return array|bool
     */
    public function getEntityByFields($fields = array(), $orderBy = array())
    {
        $emtityClass = $this->getEntityClass();
        if (class_exists($emtityClass)) {
            return $this->getDb()->fetchByFields($emtityClass, $fields, $orderBy);
        }

        return false;
    }

    /**
     * TODO: refactor the massive method.
     *
     * @param AbstractEntity $entity
     * @param bool $last
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function save(AbstractEntity $entity, $last = true)
    {
        if (!empty($this->connectedUser) && isset($this->connectedUser['firstname']) && isset($this->connectedUser['lastname'])) {
            $id = $entity->get('id');
            if (empty($id)) {
                $id = $entity->get('uuid'); //manage the case where the id field doesn't exist
            }
            if (empty($id)) {
                if ($entity->__isset('creator')) {
                    $entity->set('creator', trim($this->connectedUser['firstname'] . " " . $this->connectedUser['lastname']));
                }
                if ($entity->__isset('createdAt')) {
                    $entity->set('createdAt', new \DateTime());
                }
            } else {
                if ($entity->__isset('updater')) {
                    $entity->set('updater', trim($this->connectedUser['firstname'] . " " . $this->connectedUser['lastname']));
                }
                if ($entity->__isset('updatedAt')) {
                    $entity->set('updatedAt', new \DateTime());
                }
            }
        }
        $ids = $this->getClassMetadata()->getIdentifierFieldNames(); // fetch for the composite key

        $params = $entity->get('parameters');
        $clean_params = false;
        if (in_array('uuid', $ids)) { //uuid
            if (isset($params['implicitPosition']['changes'])) {
                if (isset($entity->parameters['implicitPosition']['root']) && (!$entity->uuid || $params['implicitPosition']['changes']['parent']['before'] != $params['implicitPosition']['changes']['parent']['after'])) {
                    $this->updateRootTree($entity, !$entity->uuid, $params['implicitPosition']['changes']);
                    $clean_params = true;
                }

                if ((!$entity->uuid)
                    || (isset($params['implicitPosition']['changes']['parent']) && $params['implicitPosition']['changes']['parent']['before'] != $params['implicitPosition']['changes']['parent']['after'])
                    || (isset($params['implicitPosition']['changes']['position']) && $params['implicitPosition']['changes']['position']['before'] != $params['implicitPosition']['changes']['position']['after'])
                ) {
                    $this->autopose($entity, !$entity->uuid, $params['implicitPosition']['changes']);
                    $clean_params = true;
                }
            }
        } else { //id
            if (isset($params['implicitPosition']['changes'])) {
                if (isset($entity->parameters['implicitPosition']['root']) && (!$entity->id || $params['implicitPosition']['changes']['parent']['before'] != $params['implicitPosition']['changes']['parent']['after'])) {
                    $this->updateRootTree($entity, !$entity->id, $params['implicitPosition']['changes']);
                    $clean_params = true;
                }

                if ((!$entity->id)
                    || (isset($params['implicitPosition']['changes']['parent']) && $params['implicitPosition']['changes']['parent']['before'] != $params['implicitPosition']['changes']['parent']['after'])
                    || (isset($params['implicitPosition']['changes']['position']) && $params['implicitPosition']['changes']['position']['before'] != $params['implicitPosition']['changes']['position']['after'])
                ) {
                    $this->autopose($entity, !$entity->id, $params['implicitPosition']['changes']);
                    $clean_params = true;
                }
            }
        }

        if ($clean_params) {
            unset($params['implicitPosition']['changes']);
        }

        if ($this->getClassMetadata()->getIdentifierFieldNames()) {
            foreach ($ids as $key => $value) {
                if ($value === 'uuid' && !$entity->get('uuid')) //uuid have to be generated and setted
                    $entity->set('uuid', Uuid::uuid4());
            }
        }

        $id = $this->getDb()->save($entity, $last); // standard stuff for normal AI id
        if ($entity->get('id'))
            $entity->set('id', $id);
        $entity->initParametersChanges();
        if ($entity->get('uuid')) {
            is_string($entity->get('uuid')) ? $uuid = $entity->get('uuid') : $uuid = $entity->get('uuid')->toString();
            return $uuid;
        }

        return $id;
    }

    /**
     * @param $entity
     * @param $was_new
     * @param array $changes
     */
    protected function updateRootTree($entity, $was_new, $changes = [])
    {
        $this->initTree($entity, 'position');//need to be called first to allow tree repositionning
        $rootField = $entity->parameters['implicitPosition']['root'];
        if (!is_null($entity->get($entity->parameters['implicitPosition']['field']))) {
            $father = $this->getEntity($entity->get($entity->parameters['implicitPosition']['field'])->get('id'));
            $entity->set($rootField, ($father->get($rootField) === null) ? $father : $father->get($rootField));
        } else {
            $entity->set($rootField, null);
        }

        if (!$was_new && $changes['parent']['before'] != $changes['parent']['after']) {
            $temp = isset($entity->parameters['children']) ? $entity->parameters['children'] : [];
            while (!empty($temp)) {
                $sub = array_shift($temp);
                $sub->set($rootField, ((is_null($entity->get($rootField))) ? $entity : $entity->get($rootField)));
                $this->save($sub, false);
                if (!empty($sub->parameters['children'])) {
                    foreach ($sub->parameters['children'] as $subsub) {
                        array_unshift($temp, $subsub);
                    }
                }
            }
        }
    }

    /**
     * TODO: refactor the massive method.
     *
     * @param $entity
     * @param $was_new
     * @param array $changes
     * @param bool $force_new
     */
    protected function autopose($entity, $was_new, $changes = [], $force_new = false)
    {
        //objects could be sorted even if they haven't a parent field
        $isParentable = !isset($entity->parameters['isParentRelative']) || $entity->parameters['isParentRelative'];
        //fetch the ids of the associations (needed for where clause) because the association can be (uuid / anr)
        $implicitPositionFieldIds = [];
        $classIdentifier = []; // the ids of the class
        $idName = 'id'; // the value of the name of the id (id or uuid) use for sql request

        if ($this != null && isset($entity->parameters['implicitPosition']['field']) && $entity->getDbAdapter() != null)
            $implicitPositionFieldIds = $entity->getDbAdapter()->getClassMetadata($this->getClassMetadata()->getAssociationTargetClass($entity->parameters['implicitPosition']['field']))->getIdentifierFieldNames();
        $implicitPositionFieldMainId = (in_array('uuid', $implicitPositionFieldIds)) ? 'uuid' : 'id';// the value of the name of the id (id or uuid) use for sql request for the implicit position

        if ($entity != null)
            $classIdentifier = $this->getDb()->getClassMetadata(ClassUtils::getRealClass(get_class($entity)))->getIdentifierFieldNames();
        if (in_array('uuid', $classIdentifier))
            $idName = 'uuid';

        /*
        * MEMO :
        * Be sure that the corresponding service has its parent dependency declared
        * and the create or update method calls $this->setDependencies($entity, $this->dependencies).
        * This required the injection of the parentTable in the factory of your Service
        */

        if (count($implicitPositionFieldIds) > 1) { //not possible to join on update directly so make subquery
            $subquery = $this->getRepository()->createQueryBuilder('s')
                ->select('distinct(s.' . $idName . ')') //fetch distinct id
                ->Join('s.' . $entity->parameters['implicitPosition']['field'], $entity->parameters['implicitPosition']['field']); //join the field
        }

        if ($was_new || $force_new) {
            $idParam = $idName === 'uuid' ? (string)$entity->get($idName) : $entity->get($idName) ?? 0;
            $params = [
                ':position' => (int) $entity->get('position'),
                ':id' => $idParam //specific to the TIPs below
            ];
            $bros = $this->getRepository()->createQueryBuilder('bro')
                ->update()->set("bro.position", "bro.position + 1")->where("1 = 1");
            if ($isParentable) {
                if ($entity->parameters['implicitPosition']['field'])
                    if (is_null($entity->get($entity->parameters['implicitPosition']['field']))) {
                        if (count($implicitPositionFieldIds) > 1) {
                            $subquery->andWhere($entity->parameters['implicitPosition']['field'] . '.anr IS NULL')
                                ->andWhere($entity->parameters['implicitPosition']['field'] . '.uuid IS NULL');
                        } else
                            $bros->andWhere('bro.' . $entity->parameters['implicitPosition']['field'] . ' IS NULL');
                    } else {
                        if (count($implicitPositionFieldIds) > 1) {
                            $subquery->andWhere($entity->parameters['implicitPosition']['field'] . '.anr = :implicitPositionFieldAnr')
                                ->andWhere($entity->parameters['implicitPosition']['field'] . '.uuid = :implicitPositionFieldUuid')
                                ->setParameter(':implicitPositionFieldUuid', is_string($entity->get($entity->parameters['implicitPosition']['field'])->get('uuid')) ? $entity->get($entity->parameters['implicitPosition']['field'])->get('uuid') : $entity->get($entity->parameters['implicitPosition']['field'])->get('uuid')->toString())
                                ->setParameter(':implicitPositionFieldAnr', $entity->get('anr')->get('id'));
                        } else {
                            $bros->where('bro.' . $entity->parameters['implicitPosition']['field'] . ' = :parentid');
                            $params[':parentid'] = $entity->get($entity->parameters['implicitPosition']['field'])->get($implicitPositionFieldMainId);
                        }
                    }

                if (!empty($entity->parameters['implicitPosition']['subField'])) {
                    foreach ($entity->parameters['implicitPosition']['subField'] as $k) {
                        $sub = is_null($entity->get($k)) ? null : (is_object($entity->get($k)) ? $entity->get($k)->get('id') : $entity->get($k));
                        if (is_null($sub)) {
                            $bros->andWhere('bro.' . $k . ' IS NULL');
                        } else {
                            $bros->andWhere('bro.' . $k . ' = :' . $k);
                            $params[':' . $k] = $sub;
                        }
                    }
                }
            }
            $bros = $bros->andWhere('bro.position >= :position');
            if (count($implicitPositionFieldIds) > 1) {
                $ids = $subquery->getQuery()->getResult();
                $bros->andWhere('bro.' . $idName . ' IN (:ids)');
                $params[':ids'] = $ids;
            }
            $bros->andWhere('bro.' . $idName . ' != :id')
                ->setParameters($params);

            $bros->getQuery()->getResult();

        } else if (!empty($changes['parent']) && $changes['parent']['before'] != $changes['parent']['after']) {//this is somewhat like we was new but we need to redistribute brothers
            $idParam = $idName === 'uuid' ? (string)$entity->get($idName) : $entity->get($idName);
            $params = [
                ':position' => !empty($changes['position']['before']) ? $changes['position']['before'] : $entity->get('position'),
                ':id' => $idParam
            ];
            $bros = $this->getRepository()->createQueryBuilder('bro')
                ->update()->set("bro.position", "bro.position - 1")->where("1 = 1");
            if ($isParentable) {//by security - inverse never should happen in this case
                if (is_null($changes['parent']['before'])) {
                    if (count($implicitPositionFieldIds) > 1) {
                        $subquery->where($entity->parameters['implicitPosition']['field'] . '.anr IS NULL')
                            ->andWhere($entity->parameters['implicitPosition']['field'] . '.uuid IS NULL');
                    } else
                        $bros->where('bro.' . $entity->parameters['implicitPosition']['field'] . ' IS NULL');
                } else {
                    if (count($implicitPositionFieldIds) > 1) {
                        $subquery->where($entity->parameters['implicitPosition']['field'] . '.anr = :implicitPositionFieldAnr')
                            ->andWhere($entity->parameters['implicitPosition']['field'] . '.uuid = :implicitPositionFieldUuid')
                            ->setParameter(':implicitPositionFieldUuid', $changes['parent']['before'])
                            ->setParameter(':implicitPositionFieldAnr', $entity->get('anr')->get('id'));
                    } else {
                        $bros->where('bro.' . $entity->parameters['implicitPosition']['field'] . ' = :parentid');
                        $params[':parentid'] = $changes['parent']['before'];
                    }
                }
                if (!empty($entity->parameters['implicitPosition']['subField'])) {
                    foreach ($entity->parameters['implicitPosition']['subField'] as $k) {
                        $sub = is_null($entity->get($k)) ? null : (is_object($entity->get($k)) ? $entity->get($k)->get('id') : $entity->get($k));
                        if (is_null($sub)) {
                            $bros->andWhere('bro.' . $k . ' IS NULL');
                        } else {
                            $bros->andWhere('bro.' . $k . ' = :' . $k);
                            $params[':' . $k] = $sub;
                        }
                    }
                }
            }

            $bros = $bros->andWhere('bro.position >= :position');
            if (count($implicitPositionFieldIds) > 1) {
                $ids = $subquery->getQuery()->getResult();
                $bros->andWhere('bro.' . $idName . ' IN (:ids)');
                $params[':ids'] = $ids;
            }
            $bros->andWhere('bro.' . $idName . ' != :id')
                ->setParameters($params);
            $bros->getQuery()->getResult();

            $this->autopose($entity, $was_new, $changes, true);//TIPS : we simulate the new option to move new brothers up
        } else {//we're not new, the parent is the same, so we "just" have to change internal positions
            $avant = $changes['position']['before'];
            $apres = $changes['position']['after'];// == $entity->get('position')
            $idName = $idName === 'uuid' ? (string)$entity->get($idName) : $entity->get($idName);
            $params = [
                ':apres' => $apres,
                ':avant' => $avant,
                ':id' => $idName
            ];

            $bros = $this->getRepository()->createQueryBuilder('bro')
                ->update()->set("bro.position", "bro.position " . ($avant > $apres ? "+" : "-") . " 1")->where("1 = 1");
            if ($isParentable) {
                if (is_null($entity->get($entity->parameters['implicitPosition']['field']))) {
                    if (count($implicitPositionFieldIds) > 1) {
                        $subquery->where($entity->parameters['implicitPosition']['field'] . '.anr IS NULL')
                            ->andWhere($entity->parameters['implicitPosition']['field'] . '.uuid IS NULL');
                    } else
                        $bros->where('bro.' . $entity->parameters['implicitPosition']['field'] . ' IS NULL');
                } else {
                    if (count($implicitPositionFieldIds) > 1) {
                        $subquery->where($entity->parameters['implicitPosition']['field'] . '.anr = :implicitPositionFieldAnr')
                            ->andWhere($entity->parameters['implicitPosition']['field'] . '.uuid = :implicitPositionFieldUuid')
                            ->setParameter(':implicitPositionFieldUuid', $changes['parent']['before'])
                            ->setParameter(':implicitPositionFieldAnr', $entity->get('anr')->get('id'));
                    } else {
                        $bros->where('bro.' . $entity->parameters['implicitPosition']['field'] . ' = :parentid');
                        $params[':parentid'] = $entity->get($entity->parameters['implicitPosition']['field'])->get($implicitPositionFieldMainId);
                    }
                }

                if (!empty($entity->parameters['implicitPosition']['subField'])) {
                    foreach ($entity->parameters['implicitPosition']['subField'] as $k) {
                        $sub = is_null($entity->get($k)) ? null : (is_object($entity->get($k)) ? $entity->get($k)->get('id') : $entity->get($k));
                        if (is_null($sub)) {
                            $bros->andWhere('bro.' . $k . ' IS NULL');
                        } else {
                            $bros->andWhere('bro.' . $k . ' = :' . $k);
                            $params[':' . $k] = $sub;
                        }
                    }
                }
            }
            if (count($implicitPositionFieldIds) > 1) {
                $ids = $subquery->getQuery()->getResult();
                $bros->andWhere('bro.' . $idName . ' IN (:ids)');
                $params[':ids'] = $ids;
            }
            // TODO: remove it as the results are not used.
            $bros = $bros->andWhere('bro.position ' . (($avant > $apres) ? '>=' : '<=') . ' :apres')
                ->andWhere('bro.position ' . (($avant > $apres) ? '<' : '>') . ' :avant')
                ->andWhere('bro.' . $idName . ' != :id')
                ->setParameters($params)
                ->getQuery()->getResult();
        }
    }

    /**
     * @param $id
     * @param bool $last
     *
     * @return bool
     *
     * @throws Exception
     * @throws ForeignKeyConstraintViolationException
     * @throws MappingException
     */
    public function delete($id, $last = true): bool
    {
        $c = $this->getEntityClass();
        if (class_exists($c)) {
            if (!is_array($id)) {
                try {
                    $id = Uuid::fromString($id);
                } catch (InvalidUuidStringException $e) {
                    $id = (int)$id;
                }
            }
            $entity = new $c();
            if (is_array($id)) {
                foreach ($id as $key => $value) {
                    $entity->set($key, $value);
                }
            } else {
                $entity->set($this->getClassMetadata()->getSingleIdentifierFieldName(), $id);
            }
            $entity = $this->getDb()->fetch($entity);

            $params = $entity->get('parameters');
            if (!empty($params['implicitPosition'])) {
                $this->manageDeletePosition($entity, $params['implicitPosition']);
            }

            $this->getDb()->delete($entity, $last);

            return true;
        }

        return false;
    }

    /**
     * TODO: refactor the massive method.
     *
     * @param AbstractEntity $entity
     * @param array $params
     */
    protected function manageDeletePosition(AbstractEntity $entity, $params = [])
    {
        if (!empty($params['field']))
            $implicitPositionFieldIds = $this->getDb()->getEntityManager()->getClassMetadata($this->getClassMetadata()->getAssociationTargetClass($params['field']))->getIdentifierFieldNames();

        $classIdentifier = []; // the ids of the class
        $idName = 'id'; // the value of the name of the id (id or uuid) use for sql request
        $classIdentifier = $this->getClassMetadata()->getIdentifierFieldNames();
        if (in_array('uuid', $classIdentifier))
            $idName = 'uuid';


        $subquery = null; //initialize varaible for subquery if needed
        $return = $this->getRepository()->createQueryBuilder('t')
            ->set('t.position', 't.position - 1')->update();

        if (count($implicitPositionFieldIds) > 1) { //not possible to join on update directly so make subquery
            $subquery = $this->getRepository()->createQueryBuilder('s')
                ->select('distinct(s.' . $idName . ')') //fetch distinct id
                ->innerJoin('s.' . $params['field'], $params['field']); //join the field
        }

        $hasWhere = false;
        if (!empty($params['field'])) {
            $hasWhere = true;
            if (is_null($entity->get($params['field']))) {
                if (count($implicitPositionFieldIds) > 1) {
                    $subquery = $subquery->andWhere($params['field'] . '.anr IS NULL')
                        ->andWhere($params['field'] . '.uuid IS NULL');
                } else
                    $return = $return->andWhere('t.' . $params['field'] . ' IS NULL');
            } else {
                if (count($implicitPositionFieldIds) > 1) {
                    $subquery = $subquery->andWhere($params['field'] . '.anr = :fieldAnr')
                        ->andWhere($params['field'] . '.uuid = :fieldUuid')
                        ->setParameter(':fieldAnr', $entity->get($params['field'])->getAnr()->getId())
                        ->setParameter(':fieldUuid', $entity->get($params['field'])->getUuid()->toString());


                } else {
                    $return = $return->andWhere('t.' . $params['field'] . ' = :' . $params['field'])
                        ->setParameter(':' . $params['field'], $entity->get($params['field']));
                }
            }
            if (!empty($params['subField'])) {
                foreach ($params['subField'] as $k) {
                    $sub = is_null($entity->get($k)) ? null : (is_object($entity->get($k)) ? $entity->get($k)->get('id') : $entity->get($k));
                    if (is_null($sub)) {
                        $return->andWhere('t.' . $k . ' IS NULL');
                    } else {
                        $return->andWhere('t.' . $k . ' = :' . $k)
                            ->setParameter(':' . $k, $sub);
                    }
                }
            }
        }
        if ($hasWhere) {
            $return = $return->andWhere('t.position >= :pos');
        } else {
            $return = $return->andWhere('t.position >= :pos');
        }
        if (count($implicitPositionFieldIds) > 1) {
            $ids = $subquery->getQuery()->getResult();
            $return = $return->andWhere('t.' . $idName . ' IN (:ids)');
            $return = $return->setParameter(':ids', $ids);
        }
        $return = $return->setParameter(':pos', $entity->get('position'));

        $return->getQuery()->getResult();
    }

    /**
     * @param $data
     *
     * @return bool
     *
     * @throws ForeignKeyConstraintViolationException
     */
    public function deleteList($data)
    {
        $c = $this->getEntityClass();
        if (!class_exists($c) || !is_array($data)) {
            return false;
        }

        $entity = new $c();
        $entities = $this->getDb()->fetchByIds($entity, $data);
        if (empty($entities)) {
            return false;
        }

        $params = $entity->get('parameters');
        if (!empty($params['implicitPosition'])) {
            // C'est un peu bourrin
            foreach ($entities as $e) {
                $this->manageDeletePosition($e, $params['implicitPosition']);
            }
        }
        $this->getDb()->deleteAll($entities);

        return true;
    }

    /**
     * @param $id
     *
     * @return bool|Proxy|null|object
     */
    public function getReference($id)
    {
        return $this->getDb()->getReference($this->getEntityClass(), $id);
    }

    /**
     * @param $id
     *
     * @return array
     */
    public function getDescendants($id): array
    {
        $childList = [];
        $this->getRecursiveChild($childList, $id);

        return $childList;
    }

    /**
     * Get Recursive Child
     *
     * @param $childList
     * @param $id
     */
    protected function getRecursiveChild(&$childList, $id)
    {
        $children = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        if (count($children)) {
            foreach ($children as $child) {
                $childList[] = $child['id'];
                $this->getRecursiveChild($childList, $child['id']);
            }
        }
    }

    /**
     * @param $childList
     * @param $id
     */
    protected function getRecursiveChildObjects(&$childList, $id)
    {
        $children = $this->getRepository()->createQueryBuilder('t')
            ->select()
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        foreach ($children as $child) {
            $childList[] = $child;
            $this->getRecursiveChildObjects($childList, $child->id);
        }
    }

    /**
     * Optimized method to avoid recursive call with multiple SQL queries
     *
     * @param $entity
     * @param null $order_by
     */
    public function initTree($entity, $order_by = null)
    {
        $rootField = isset($entity->parameters['implicitPosition']['root']) ? $entity->parameters['implicitPosition']['root'] : 'root';
        $parentField = isset($entity->parameters['implicitPosition']['field']) ? $entity->parameters['implicitPosition']['field'] : 'parent';

        $ref = $entity->get($rootField) === null
            ? $entity->get('id')
            : $entity->get($rootField)->get('id');

        $qb = $this->getRepository()->createQueryBuilder('t');

        $qb->select()
            ->where('t.' . $rootField . ' = :ref')
            ->setParameter(':ref', $ref);

        if ($order_by !== null) {
            $qb->orderBy('t.' . $order_by, 'DESC');
        }

        $descendants = $qb->getQuery()->getResult();

        $family = array();
        foreach ($descendants as $c) {
            //root is null but [null] on an array is not pretty cool
            $family[$c->get($parentField) === null ? 0 : $c->get($parentField)->get('id')][] = $c;
        }

        if (!empty($family)) {
            $temp = array();
            $temp[] = $entity;
            while (!empty($temp)) {
                $current = array_shift($temp);
                if (!empty($family[$current->get('id')])) {
                    foreach ($family[$current->get('id')] as $fam) {
                        $params = [];
                        if (!isset($current->parameters['children'])) {
                            $current->setParameter('children', []);
                        } else {
                            $params = $current->parameters['children'];
                        }
                        $params[$fam->get('id')] = $fam;
                        $current->setParameter('children', $params);
                        array_unshift($temp, $fam);
                    }
                }
            }
        }
    }
}
