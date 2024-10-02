<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\ScaleImpactType;
use Monarc\Core\Entity\ScaleImpactTypeSuperClass;

class ScaleImpactTypeTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = ScaleImpactType::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return ScaleImpactTypeSuperClass[]
     */
    public function findByAnrIndexedByType(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('sit', 'sit.type')
            ->where('sit.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    public function findMaxTypeValueByAnr(AnrSuperClass $anr): int
    {
        return (int)$this->getRepository()->createQueryBuilder('sit')
            ->select('sit.type as type')
            ->where('sit.anr = :anr')
            ->setParameter('anr', $anr)
            ->orderBy('type', Criteria::DESC)
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
