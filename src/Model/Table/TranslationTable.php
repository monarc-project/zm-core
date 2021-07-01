<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\Translation;

class TranslationTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, Translation::class);
    }

    /**
     * @return Translation[]
     */
    public function findByTypesIndexedByKey(array $types): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t', 't.key');

        return $queryBuilder
            ->where($queryBuilder->expr()->in('t.type', $types))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Translation[]
     */
    public function findByTypesAndLanguageIndexedByKey(array $types, string $lang): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t', 't.key');

        return $queryBuilder
            ->where($queryBuilder->expr()->in('t.type', $types))
            ->andWhere('t.lang = :lang')
            ->setParameter('lang', $lang)
            ->getQuery()
            ->getResult();
    }

    public function findByKeyAndLanguage(string $key, string $lang): Translation
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->where('t.key = :key')
            ->andWhere('t.lang = :lang')
            ->setParameter('key', $key)
            ->setParameter('lang', $lang)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteListByKeys(array $keys): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t');
        $queryBuilder
            ->delete()
            ->where($queryBuilder->expr()->in('t.key', $keys))
            ->getQuery()
            ->getResult();
    }
}
