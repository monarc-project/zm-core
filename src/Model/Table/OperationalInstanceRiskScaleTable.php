<?php declare(strict_types=1);

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\InstanceRiskOpSuperClass;
use Monarc\Core\Model\Entity\OperationalInstanceRiskScale;
use Monarc\Core\Model\Entity\OperationalInstanceRiskScaleSuperClass;

class OperationalInstanceRiskScaleTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager)
    {
        parent::__construct($entityManager, OperationalInstanceRiskScale::class);
    }

    /**
     * @return OperationalInstanceRiskScaleSuperClass[]
     */
    public function findByInstanceRiskOp(InstanceRiskOpSuperClass $instanceRiskOp): array
    {
        return $this->getRepository()->createQueryBuilder('oirs')
            ->where('oirs.instanceRiskOp = :instanceRiskOp')
            ->setParameter('instanceRiskOp', $instanceRiskOp)
            ->getQuery()
            ->getSQL();
    }
}
