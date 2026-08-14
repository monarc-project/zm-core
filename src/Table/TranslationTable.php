<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\Translation;
use Monarc\Core\Entity\TranslationSuperClass;

class TranslationTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = Translation::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return TranslationSuperClass[]
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

    /**
     * @return TranslationSuperClass[]
     */
    public function findByAnrAndKey(AnrSuperClass $anr, string $key): array
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->where('t.anr = :anr')
            ->andWhere('t.key = :key')
            ->setParameter('anr', $anr)
            ->setParameter('key', $key)
            ->getQuery()
            ->getResult();
    }

    public function findByAnrKeyAndLanguage(AnrSuperClass $anr, string $key, string $lang): TranslationSuperClass
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->where('t.anr = :anr')
            ->andWhere('t.key = :key')
            ->andWhere('t.lang = :lang')
            ->setParameter('anr', $anr)
            ->setParameter('key', $key)
            ->setParameter('lang', $lang)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return TranslationSuperClass[]
     */
    public function findByTypeAndLanguageIndexedByKeys(string $type, array $keys, string $lang): array
    {
        if ($keys === []) {
            return [];
        }

        $queryBuilder = $this->getRepository()->createQueryBuilder('t', 't.key');

        return $queryBuilder
            ->where('t.anr IS NULL')
            ->andWhere('t.type = :type')
            ->andWhere($queryBuilder->expr()->in('t.key', ':keys'))
            ->andWhere('t.lang = :lang')
            ->setParameter('type', $type)
            ->setParameter('keys', array_values(array_unique($keys)))
            ->setParameter('lang', $lang)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TranslationSuperClass[]
     */
    public function findByTypeAndKey(string $type, string $key): array
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->where('t.anr IS NULL')
            ->andWhere('t.type = :type')
            ->andWhere('t.key = :key')
            ->setParameter('type', $type)
            ->setParameter('key', $key)
            ->getQuery()
            ->getResult();
    }

    public function findByTypeKeyAndLanguage(string $type, string $key, string $lang): ?TranslationSuperClass
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->where('t.anr IS NULL')
            ->andWhere('t.type = :type')
            ->andWhere('t.key = :key')
            ->andWhere('t.lang = :lang')
            ->setParameter('type', $type)
            ->setParameter('key', $key)
            ->setParameter('lang', $lang)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteListByAnrAndKeys(AnrSuperClass $anr, array $keys): void
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('t');
        $queryBuilder
            ->delete()
            ->where('t.anr = :anr')
            ->andWhere($queryBuilder->expr()->in('t.key', $keys))
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    public function deleteListByKeys(array $keys): void
    {
        if ($keys === []) {
            return;
        }

        $queryBuilder = $this->getRepository()->createQueryBuilder('t');
        $queryBuilder
            ->delete()
            ->where('t.anr IS NULL')
            ->andWhere($queryBuilder->expr()->in('t.key', ':keys'))
            ->setParameter('keys', array_values(array_unique($keys)))
            ->getQuery()
            ->getResult();
    }
}
