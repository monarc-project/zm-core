<?php
namespace MonarcCore\Model;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\QueryBuilder;

class Db {
    /** @var EntityManager */
    protected $entityManager;

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }
    public function fetchAll($entity)
    {
        $repository = $this->entityManager->getRepository(get_class($entity));
        $entities = $repository->findAll();
        return $entities;
    }

    public function getRepository($class){
        return $this->entityManager->getRepository($class);
    }

    public function beginTransaction() {
        $this->entityManager->getConnection()->beginTransaction();
    }

    public function commit() {
        $this->entityManager->getConnection()->commit();
    }

    public function rollback() {
        $this->entityManager->getConnection()->rollBack();
    }

    /**
     * @param Entity $entity
     * @param int $page
     * @param int $limit
     * @param array|null $order
     * @param array|null $filter
     * @param array|null $filterAnd
     * @return array
     */
    public function fetchAllFiltered($entity, $page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null) {
        $repository = $this->entityManager->getRepository(get_class($entity));

        $qb = $this->buildFilteredQuery($repository, $page, $limit, $order, $filter, $filterAnd, $filterJoin);

        return $qb->getQuery()->getResult();
    }

    public function count($entity) {
        $repository = $this->entityManager->getRepository(get_class($entity));
        return $repository->createQueryBuilder('u')->select('count(u.id)')->getQuery()->getSingleScalarResult();
    }

    public function countFiltered($entity, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null) {
        $repository = $this->entityManager->getRepository(get_class($entity));
        $qb = $this->buildFilteredQuery($repository, 1, $limit, $order, $filter, $filterAnd, $filterJoin);
        $qb->select('count(t.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function fetch($entity)
    {
        if (!$entity->get('id')) {
            throw new \Exception('Entity `id` not found.');
        }
        return $this->entityManager->find(get_class($entity), $entity->get('id'));
    }
    public function delete($entity)
    {
        try {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new \Exception('Foreign key violation', '400');
        }
    }
    public function save($entity, $last = true)
    {
        $this->entityManager->persist($entity);
        if ($last) {
            $this->entityManager->flush();
        }

        return $entity->id;
    }
    public function flush()
    {
        $this->entityManager->flush();
    }

    public function lastInsertId(){
        return $this->entityManager->getConnection()->lastInsertId();
    }

    /**
     * @param EntityRepository $repository
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param null $filterAnd
     * @param null $filterJoin
     * @return QueryBuilder
     */
    private function buildFilteredQuery($repository, $page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null, $filterJoin = null)
    {
        $qb = $repository->createQueryBuilder('t');

        if (!is_null($filterJoin) && is_array($filterJoin)) {
            foreach ($filterJoin as $join) {
                if ($join['as'] == 't') {
                    throw new \Exception('Cannot use "t" as a table alias');
                }

                $qb->join('t.' . $join['rel'], $join['as']);
                $qb->addSelect($join['as']);
            }
        }

        if ((($filter != null) || ($filterAnd != null)) && is_array($filter))  {
            $isFirst = true;

            $searchIndex = 1;

            // Add filter in WHERE xx LIKE %y% OR zz LIKE %y% ...
            foreach ($filter as $colName => $value) {

                $fullColName = $colName;
                if (strpos($fullColName, '.') === false) {
                    $fullColName = 't.' . $fullColName;
                }

                if ($value !== '') {

                    $where = (is_int($value)) ? "$fullColName = :filter_$searchIndex" : "$fullColName LIKE :filter_$searchIndex";
                    $parameterValue = (is_int($value)) ? $value : '%' . $value . '%';

                    if ($isFirst) {
                        $qb->where($where);
                        $qb->setParameter(":filter_$searchIndex", $parameterValue);
                        $isFirst = false;
                    } else {
                        $qb->orWhere($where);
                        $qb->setParameter(":filter_$searchIndex", $parameterValue);
                    }

                    ++$searchIndex;
                }

            }

            // Add filter in WHERE xx LIKE %y% AND zz LIKE %y% ...
            if (!is_null($filterAnd)) {
                foreach ($filterAnd as $colName => $value) {

                    $fullColName = $colName;
                    if (strpos($fullColName, '.') === false) {
                        $fullColName = 't.' . $fullColName;
                    }

                    if ($value !== '') {

                        if (is_array($value)) {
                            $where = "$fullColName IN (:filter_$searchIndex)";
                            $parameterValue = $value;
                        } else if (is_int($value)) {
                            $where = "$fullColName = :filter_$searchIndex";
                            $parameterValue = $value;
                        } else {
                            $where = "$fullColName LIKE :filter_$searchIndex";
                            $parameterValue = '%' . $value . '%';
                        }

                        if ($isFirst) {
                            $qb->where($where);
                            $qb->setParameter(":filter_$searchIndex", $parameterValue);
                            $isFirst = false;
                        } else {
                            $qb->andWhere($where);
                            $qb->setParameter(":filter_$searchIndex", $parameterValue);
                        }

                        ++$searchIndex;
                    }

                }
            }
        }

        // Add order
        if ($order != null) {
            $qb->orderBy('t.' . $order[0], $order[1]);
        }

        // Add limit and offset
        if ($limit > 0) {
            $qb->setFirstResult(($page - 1) * $limit);
            $qb->setMaxResults($limit);
        }

        return $qb;
    }
}
