<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\Scale;
use Monarc\Core\Entity\ScaleSuperClass;

class ScaleTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = Scale::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByAnrAndType(AnrSuperClass $anr, int $type): ScaleSuperClass
    {
        $scale = $this->getRepository()
            ->createQueryBuilder('s')
            ->where('s.anr = :anr')
            ->andWhere('s.type = :type')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($scale === null) {
            throw new EntityNotFoundException(
                sprintf('Scale of type "%d" does not exist with anr ID: "%d"', $type, $anr->getId())
            );
        }

        return $scale;
    }

    /**
     * @return ScaleSuperClass[]
     */
    public function findByAnrIndexedByType(AnrSuperClass $anr): array
    {
        return $this->getRepository()
            ->createQueryBuilder('s', 's.type')
            ->where('s.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }
}
