<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\Anr;
use Monarc\Core\Model\Entity\OperationalRiskScale;

class OperationalRiskScaleTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, OperationalRiskScale::class);
    }

    /**
    * @return OperationalRiskScale[]
    */
   public function findWithCommentsByAnr(Anr $anr): array
   {
       return $this->getRepository()->createQueryBuilder('ors')
           ->innerJoin('ors.operationalRiskScaleComments', 'orsc')
           ->where('ors.anr = :anr')
           ->setParameter('anr', $anr)
           ->getQuery()
           ->getResult();
   }

   /**
     * @return OperationalRiskScale[]
     */
    public function findWithCommentsByAnrAndType(Anr $anr, int $type): array
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
}
