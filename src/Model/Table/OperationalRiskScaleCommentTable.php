<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\OperationalRiskScaleComment;
use Monarc\Core\Model\Entity\Anr;

class OperationalRiskScaleCommentTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, OperationalRiskScaleComment::class);
    }

    /**
     * @return OperationalRiskScaleComment[]
     */
    public function findAllByAnrAndIndexAndScaleType(Anr $anr, int $scaleIndex, int $type): array
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->innerJoin('t.operationalRiskScale', 'ors')
            ->where('t.anr = :anr')
            ->andWhere('t.scaleIndex = :scaleIndex')
            ->andWhere('ors.type = :type')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->setParameter('scaleIndex', $scaleIndex)
            ->getQuery()
            ->getResult();
    }

    /**
     * If we modify the value which the index correspond to the max of the scale, we have to find the comment with higher index to update them to avoid error
     * @return OperationalRiskScaleComment[]
     */
    public function findNextCommentsToUpdateByAnrAndIndexAndType(Anr $anr, int $scaleIndex, int $type): array
    {
        return $this->getRepository()->createQueryBuilder('t')
            ->innerJoin('t.operationalRiskScale', 'ors')
            ->where('t.anr = :anr')
            ->andWhere('t.scaleIndex > :scaleIndex')
            ->andWhere('ors.type = :type')
            ->andWhere('ors.max = :scaleIndex')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->setParameter('scaleIndex', $scaleIndex)
            ->getQuery()
            ->getResult();
    }
}
