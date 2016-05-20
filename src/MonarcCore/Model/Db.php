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

    /**
     * @param Entity $entity
     * @param int $page
     * @param int $limit
     * @param array|null $order
     * @param array|null $filter
     * @return array
     */
    public function fetchAllFiltered($entity, $page = 1, $limit = 25, $order = null, $filter = null) {
        $repository = $this->entityManager->getRepository(get_class($entity));
        $qb = $this->buildFilteredQuery($repository, $page, $limit, $order, $filter);

        return $qb->getQuery()->getResult();
    }

    public function count($entity) {
        $repository = $this->entityManager->getRepository(get_class($entity));
        return $repository->createQueryBuilder('u')->select('count(u.id)')->getQuery()->getSingleScalarResult();
    }

    public function countFiltered($entity, $page = 1, $limit = 25, $order = null, $filter = null) {
        $repository = $this->entityManager->getRepository(get_class($entity));
        $qb = $this->buildFilteredQuery($repository, $page, $limit, $order, $filter);
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
     * @return QueryBuilder
     */
    private function buildFilteredQuery($repository, $page = 1, $limit = 25, $order = null, $filter = null)
    {
        $qb = $repository->createQueryBuilder('t');

        // Add filter in WHERE xx LIKE %y% OR zz LIKE %y% ...
        if ($filter != null && is_array($filter)) {
            $isFirst = true;

            $searchIndex = 1;

            foreach ($filter as $colName => $value) {
                if ($isFirst) {
                    $qb->where("t.$colName LIKE :filter_$searchIndex");
                    $qb->setParameter(":filter_$searchIndex",  '%' . $value . '%');
                    $isFirst = false;
                } else {
                    $qb->orWhere("t.$colName LIKE :filter_$searchIndex");
                    $qb->setParameter(":filter_$searchIndex",  '%' . $value . '%');
                }

                ++$searchIndex;
            }
        }

        // Add order
        if ($order != null) {
            $qb->orderBy('t.' . $order[0], $order[1]);
        }

        // Add limit and offset
        $qb->setFirstResult(($page - 1) * $limit);
        $qb->setMaxResults($limit);

        return $qb;
    }
}
