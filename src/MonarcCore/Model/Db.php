<?php
namespace MonarcCore\Model;

class Db {
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
    public function fetch($entity)
    {
        if (!$entity->get('id')) {
            throw new \Exception('Entity `id` not found.');
        }
        return $this->entityManager->find(get_class($entity), $entity->get('id'));
    }
    public function delete($entity)
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }
    public function save($entity, $last = true)
    {
        $this->entityManager->persist($entity);
        if ($last) {
            $this->entityManager->flush();
        }
    }
    public function flush()
    {
        $this->entityManager->flush();
    }
}
