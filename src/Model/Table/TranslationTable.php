<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Translation;

class TranslationTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, $entityName = Translation::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return Translation[][]
     */
    public function findByAnrAndTypesIndexedByKey(AnrSuperClass $anr, array $types): array
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
    public function findByAnrTypesAndLanguageIndexedByKey(AnrSuperClass $anr, array $types, string $lang): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t', 't.key');

        return $queryBuilder
            ->where('t.anr = :anr')
            ->andWhere($queryBuilder->expr()->in('t.type', $types))
            ->andWhere('t.lang = :lang')
            ->setParameter('anr', $anr)
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

    /**
     * @return Translation[]
     */
    public function findByAnrKeysAndLanguageIndexedByKey(AnrSuperClass $anr, array $keys, string $lang): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t', 't.key');

        return $queryBuilder
            ->where('t.anr = :anr')
            ->andWhere($queryBuilder->expr()->in('t.key', $keys))
            ->andWhere('t.lang = :lang')
            ->setParameter('anr', $anr)
            ->setParameter('lang', $lang)
            ->getQuery()
            ->getResult();
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
