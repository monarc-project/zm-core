<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Table\AbstractTable;
use Monarc\Core\Model\Entity\Anr;
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
    public function findByAnrAndTypesIndexedByKey(Anr $anr, array $types): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t', 't.key');

        return $queryBuilder
            ->where('t.anr = :anr')
            ->andWhere($queryBuilder->expr()->in('t.type', $types))
            ->setParameter('anr', $anr)
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

    public function findByAnrKeyAndLanguage(Anr $anr, string $key, string $lang): Translation
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t', 't.key');

        return $queryBuilder
            ->where('t.anr = :anr')
            ->andWhere('t.key = :key')
            ->andWhere('t.lang = :lang')
            ->setParameter('key', $key)
            ->setParameter('lang', $lang)
            ->setParameter('anr', $anr)
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
