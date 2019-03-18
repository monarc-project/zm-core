<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */
namespace MonarcCore\Model;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Ramsey\Uuid\Uuid;

/**
 * Class Db
 * @package MonarcCore\Model
 */
class Db {
    /** @var EntityManager */
    protected $entityManager;

    /**
     * Construct Db object and set the entity manager
     * @param EntityManager $entityManager The entity manager from Doctrine ORM
     */
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Return the entity manager and check if connection already opened
     * @return EntityManager $entityManager The entity manager from Doctrine ORM
     */
    public function getEntityManager(){
        if(!$this->entityManager->isOpen()){
            $this->entityManager = $this->entityManager->create(
                $this->entityManager->getConnection(),
                $this->entityManager->getConfiguration()
            );
        }
        return $this->entityManager;
    }

    /**
     * Finds all objects in the repository.
     * @param  \MonarcCore\Model\Entity\AbstractEntity $entity An entity from Monarc
     * @return \MonarcCore\Model\Entity\AbstractEntity[] All entities stored in database
     */
    public function fetchAll($entity)
    {
        $repository = $this->getEntityManager()->getRepository(get_class($entity));
        $entities = $repository->findAll();
        return $entities;
    }

    /**
     * Gets the repository for an entity class.
     *
     * @param string $class The name of the entity.
     *
     * @return \Doctrine\ORM\EntityRepository The repository class.
     */
    public function getRepository($class){
        return $this->getEntityManager()->getRepository($class);
    }

    /**
     * Starts a transaction on the underlying database connection.
     *
     * @return void
     */
    public function beginTransaction() {
        $this->getEntityManager()->getConnection()->beginTransaction();
    }

    /**
     * Commits a transaction on the underlying database connection.
     *
     * @return void
     */
    public function commit() {
        $this->getEntityManager()->getConnection()->commit();
    }

    /**
     * Performs a rollback on the underlying database connection.
     *
     * @return void
     */
    public function rollback() {
        $this->getEntityManager()->getConnection()->rollBack();
    }

    /**
     * Find entities in repository who matches with filters.
     * Apply order and limit for pagination.
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @param int $page
     * @param int $limit
     * @param array|null $order
     * @param array|null $filter
     * @param array|null $filterAnd
     * @return array
     */
    public function fetchAllFiltered($entity, $page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null) {
        $repository = $this->getEntityManager()->getRepository(get_class($entity));

        $qb = $this->buildFilteredQuery($repository, $page, $limit, $order, $filter, $filterAnd, $filterJoin, $filterLeft);

        return $qb->getQuery()->getResult();
    }

    /**
     * Count number of elements in repository
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @return int Number of elements
     */
    public function count($entity) {
        $repository = $this->getEntityManager()->getRepository(get_class($entity));
        return $repository->createQueryBuilder('u')->select('count(u)')->getQuery()->getSingleScalarResult();
    }

    /**
     * Count number of elements in repository who matches with filters.
     * Apply order and limit for pagination.
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @param array|null $filter
     * @param array|null $filterAnd
     * @param array|null $filterJoin
     * @param array|null $filterLeft
     * @return int Number of elements
     */
    public function countFiltered($entity, $filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null) {
        $repository = $this->getEntityManager()->getRepository(get_class($entity));
        $qb = $this->buildFilteredQuery($repository, 1, 0, null, $filter, $filterAnd, $filterJoin, $filterLeft);
        $qb->select('count(t)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Finds an Entity by its identifier.
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @return \MonarcCore\Model\Entity\AbstractEntity The entity instance.
     *
     * @throws OptimisticLockException
     * @throws ORMInvalidArgumentException
     * @throws TransactionRequiredException
     * @throws ORMException
     * @throws \MonarcCore\Exception\Exception
     */
    public function fetch($entity)
    {
        if (!$entity->get('id')) {
          if ($entity->get('anr')){ // cas du FO
          return $this->getEntityManager()->find(get_class($entity), ['anr' => $entity->get('anr'),'uuid'=>$entity->get('uuid')]);
          }  //throw new \MonarcCore\Exception\Exception('Entity `id` not found.');
          else{ // BO
            return $this->getEntityManager()->find(get_class($entity), ['uuid'=>$entity->get('uuid')]);
          }
        }else {
          return $this->getEntityManager()->find(get_class($entity), $entity->get('id'));
        }

    }

    /**
     * Finds Entities matches with params fields and ordered.
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @param array $fields List of conditions ([key=>value,...] or [key=>[op=>operator,value=>value]...])
     * @param string[] $orderBy Order by [field=>DESC/ASC,...]
     * @return \MonarcCore\Model\Entity\AbstractEntity[] List of entities.
     */
    public function fetchByFields($entity, $fields, $orderBy)
    {
        $repository = $this->getEntityManager()->getRepository(get_class($entity));
        $qb = $repository->createQueryBuilder('u');

        $metadata = $this->getClassMetadata(get_class($entity));

        foreach ($fields as $key => $value) {
            $db = 'u.'.$key;
            if($metadata->hasAssociation($key) && !$metadata->isSingleValuedAssociation($key)){
                $qb->innerJoin($db,$key);
                $db = $key.'.id';
            }
            if ($value !== 'null' && !is_null($value)) {
                if(is_array($value)){
                    if(!empty($value['op']) && array_key_exists('value', $value)){
                        if(is_array($value['value'])){ // IN || NOT IN
                            $qb->andWhere("$db ".$value['op']." (:$key)");
                            $qb->setParameter($key, $value['value']);
                        }elseif(is_int($value['value'])){
                            $qb->andWhere("$db ".$value['op']." :$key");
                            $qb->setParameter($key, $value['value']);
                        }elseif(is_null($value['value'])){ // IS || IS NOT
                            $qb->andWhere("$db ".$value['op']." NULL");
                        }else{ // LIKE || NOT LIKE
                            $qb->andWhere("$db ".$value['op']." :$key");
                            $qb->setParameter($key, '%'.$value['value'].'%');
                        }
                    }else if(!empty($value['uuid']) && !empty($value['anr'])) { //request on uuid in fo to improve to be more generic
                      $qb->innerJoin($db,$key);
                      $qb->andWhere("$key".'.uuid = '." :uuid")
                          ->andWhere("$key".'.anr = '." :anr")
                          ->setParameters(array(
                                'uuid' => $value['uuid'],
                                'anr' => $value['anr']
                                )
                              );
                    }else{
                        $qb->andWhere("$db IN (:$key)");
                        $qb->setParameter($key, $value);
                    }
                }else{
                    $qb->andWhere("$db = :$key");
                    $qb->setParameter($key, $value);
                }
            } else {
                $qb->andWhere($qb->expr()->isNull("$db"));
            }
        }

        $fistOrder = true;
        foreach ($orderBy as $field => $way) {
            if($fistOrder){
                $qb->orderBy("u.$field", $way);
                $fistOrder = false;
            }else{
                $qb->addOrderBy("u.$field", $way);
            }
        }
        return $qb->getQuery()->getResult();
    }

    public function fetchByIds($entity,$ids = array()){

      if (is_array($ids[0])) {
        $entities = [];
        foreach ($ids as $id) {
          $entities[] = $this->getEntityManager()->getRepository(get_class($entity))->find($id); // cas du FO
        }
        return $entities;
      }else if (Uuid::isValid($ids[0]))
      {
        foreach ($ids as $id) {
          $entities[] = $this->getEntityManager()->getRepository(get_class($entity))->find(['uuid' => $id]); // BO case with uuid
        }
        return $entities;
      }
      else {
        return $this->getEntityManager()->getRepository(get_class($entity))->findById($ids);
      }
    }

    /**
     * Delete Entities.
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity[] $entities
     *
     * @throws \Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException
     */
    public function deleteAll($entities = array()){
         try {
            foreach($entities as $entity){
                $this->getEntityManager()->remove($entity);
            }
            $this->getEntityManager()->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new \MonarcCore\Exception\Exception('Foreign key violation', '400');
        }
    }

    /**
     * Delete Entity.
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @param boolean $last If true, flush entity in the repository
     *
     * @throws \Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException
     */
    public function delete($entity, $last = true)
    {
        try {
            $this->getEntityManager()->remove($entity);
            if ($last) {
                $this->getEntityManager()->flush();
            }
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new \MonarcCore\Exception\Exception('Foreign key violation', '400');
        }
    }

    /**
     * Save Entity.
     *
     * @param \MonarcCore\Model\Entity\AbstractEntity $entity
     * @param boolean $last If true, flush entity in the repository
     * @return int identifier of the entity
     */
    public function save($entity, $last = true)
    {
        $this->getEntityManager()->persist($entity);

        if ($last) {
            $this->flush();
        }

        return $entity->id;
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     *
     * If an entity is explicitly passed to this method only this entity and
     * the cascade-persist semantics + scheduled inserts/removals are synchronized.
     * @return void
     *
     * @throws \Doctrine\ORM\OptimisticLockException If a version check on an entity that
     *         makes use of optimistic locking fails.
     */
    public function flush()
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers,
     * because the underlying database may not even support the notion of AUTO_INCREMENT/IDENTITY
     * columns or sequences.
     *
     * @return string A string representation of the last inserted ID.
     */
    public function lastInsertId(){
        return $this->getEntityManager()->getConnection()->lastInsertId();
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed       $input The parameter to be quoted.
     * @param string|null $type  The type of the parameter.
     *
     * @return string The quoted parameter.
     */
    public function quote($input, $type) {
        return $this->getEntityManager()->getConnection()->quote($input, $type);
    }

    /**
     * @param $repository
     * @param int $page
     * @param int $limit
     * @param null $order : to order on join filter : ['name_of_the_alias'.'name_of_the_field']['ASC']
     * @param null $filter
     * @param null $filterAnd
     * @param null $filterJoin
     * @param null $filterLeft
     * @return mixed
     * @throws \MonarcCore\Exception\Exception
     */
    private function buildFilteredQuery($repository, $page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null, $filterLeft = null)
    {
        $qb = $repository->createQueryBuilder('t');

        if (!is_null($filterJoin) && is_array($filterJoin)) {
            foreach ($filterJoin as $join) {
                if ($join['as'] == 't') {
                    throw new \MonarcCore\Exception\Exception('Cannot use "t" as a table alias');
                }

                $qb->innerJoin('t.' . $join['rel'], $join['as']);
                $qb->addSelect($join['as']);
            }
        }

        if (!is_null($filterLeft) && is_array($filterLeft)) {
            foreach ($filterLeft as $left) {
                if ($left['as'] == 't') {
                    throw new \MonarcCore\Exception\Exception('Cannot use "t" as a table alias');
                }

                $qb->leftJoin('t.' . $left['rel'], $left['as']);
                $qb->addSelect($left['as']);
            }
        }

        $isFirst = true;
        $searchIndex = 1;

        // Add filter in WHERE xx LIKE %y% OR zz LIKE %y% ...
        if (!empty($filter) && is_array($filter))  {
            foreach ($filter as $colName => $value) {

                $fullColName = $colName;
                if (strpos($fullColName, '.') === false) {
                    $fullColName = 't.' . $fullColName;
                }

                if (!empty($value)) {

                    $where = (is_int($value)) ? "$fullColName = ?$searchIndex" : "$fullColName LIKE ?$searchIndex";
                    $parameterValue = (is_int($value)) ? $value : '%' . $value . '%';

                    if ($isFirst) {
                        $qb->where($where);
                        $qb->setParameter($searchIndex, $parameterValue);
                        $isFirst = false;
                    } else {
                        $qb->orWhere($where);
                        $qb->setParameter($searchIndex, $parameterValue);
                    }

                    ++$searchIndex;
                }

            }
        }

        // Add filter in WHERE xx LIKE %y% AND zz LIKE %y% ...
        if (!empty($filterAnd)) {
            foreach ($filterAnd as $colName => $value) {

                $fullColName = $colName;
                if (strpos($fullColName, '.') === false) {
                    $fullColName = 't.' . $fullColName;
                }

                if ($value !== '') {

                    if (is_array($value)) {
                        if(!empty($value['op']) &&array_key_exists('value', $value)){
                            if(is_array($value['value'])){
                                $where = "$fullColName ".$value['op']." (?$searchIndex)";
                                $parameterValue = $value['value'];
                            }elseif(is_int($value['value'])){
                                $where = "$fullColName ".$value['op']." ?$searchIndex";
                                $parameterValue = $value['value'];
                            }elseif(is_null($value['value'])){ // IS || IS NOT
                                $where = "$fullColName ".$value['op']." NULL";
                                $parameterValue = null;
                            }else{ // LIKE || NOT LIKE
                                $where = "$fullColName ".$value['op']." ?$searchIndex";
                                $parameterValue = '%'.$value['value'].'%';
                            }
                        }else{
                            $where = "$fullColName IN (?$searchIndex)";
                            $parameterValue = $value;
                        }
                    } else if (is_int($value)) {
                        $where = "$fullColName = ?$searchIndex";
                        $parameterValue = $value;
                    } else if (is_null($value)) {
                        $where = "$fullColName IS NULL";
                        $parameterValue = null;
                    } else {
                        $where = "$fullColName LIKE ?$searchIndex";
                        $parameterValue = '%' . $value . '%';
                    }

                    if ($isFirst) {
                        $qb->where($where);
                        $isFirst = false;
                    } else {
                        $qb->andWhere($where);
                    }
                    if (!is_null($parameterValue)) {
                        $qb->setParameter($searchIndex, $parameterValue);
                    }

                    ++$searchIndex;
                }

            }
        }

        // Add order
        if ($order != null) {
            if(count(explode('.',$order[0]))>1)
              $qb->orderBy($order[0], $order[1]); //link on join table
            else
              $qb->orderBy('t.' . $order[0], $order[1]);
        }

        // Add limit and offset
        if ($limit > 0) {
            $qb->setFirstResult((($page<1?1:$page) - 1) * $limit);
            $qb->setMaxResults($limit);
        }

        return $qb;
    }

    /**
     * Gets a reference to the entity identified by the given type and identifier
     * without actually loading it, if the entity is not yet loaded.
     *
     * @param string $entityName The name of the entity type.
     * @param mixed  $id         The entity identifier.
     *
     * @return object The entity reference.
     *
     * @throws ORMException
     */
    public function getReference($entityName, $id){
        return $this->getEntityManager()->getReference($entityName, $id);
    }

    /**
     * Returns the ORM metadata descriptor for a class.
     *
     * The class name must be the fully-qualified class name without a leading backslash
     * (as it is returned by get_class($obj)) or an aliased class name.
     *
     * Examples:
     * MyProject\Domain\User
     * sales:PriceRequest
     *
     * Internal note: Performance-sensitive method.
     *
     * @param string $className
     *
     * @return \Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getClassMetadata($entityName){
        return $this->getEntityManager()->getClassMetadata($entityName);
    }
}
