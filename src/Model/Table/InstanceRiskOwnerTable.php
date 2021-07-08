<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\InstanceRiskOwner;
use Monarc\Core\Model\Entity\InstanceRiskOwnerSuperClass;

class InstanceRiskOwnerTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = InstanceRiskOwner::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByAnrAndName(AnrSuperClass $anr, string $name): ?InstanceRiskOwnerSuperClass
    {
        return $this->getRepository()->createQueryBuilder('iro')
            ->where('iro.anr = :anr')
            ->andWhere('iro.name = :name')
            ->setParameter('anr', $anr)
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('iro')
            ->where('iro.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }
}
