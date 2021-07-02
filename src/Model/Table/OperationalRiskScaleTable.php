<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScale;
use Monarc\Core\Model\Entity\OperationalRiskScaleSuperClass;

class OperationalRiskScaleTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = OperationalRiskScale::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return OperationalRiskScaleSuperClass[]
     */
    public function findWithCommentsByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('ors')
            ->innerJoin('ors.operationalRiskScaleComments', 'orsc')
            ->where('ors.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return OperationalRiskScaleSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('ors')
            ->where('ors.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return OperationalRiskScaleSuperClass[]
     */
    public function findWithCommentsByAnrAndType(AnrSuperClass $anr, int $type): array
    {
        return $this->getRepository()->createQueryBuilder('ors')
            ->innerJoin('ors.operationalRiskScaleComments', 'orsc')
            ->where('ors.anr = :anr')
            ->andWhere('ors.type = :type')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }


    public function findByAnrAndType(AnrSuperClass $anr, int $type): OperationalRiskScaleSuperClass
    {
        $operationalRiskScale = $this->getRepository()->createQueryBuilder('ors')
            ->where('ors.anr = :anr')
            ->andWhere('ors.type = :type')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($operationalRiskScale === null) {
            throw new EntityNotFoundException(
                sprintf('Operational Risk Scale of type "%d" was not found in anr "%d".', $type, $anr->getId())
            );
        }

        return $operationalRiskScale;
    }
}
