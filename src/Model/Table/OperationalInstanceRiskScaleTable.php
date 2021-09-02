<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\InstanceRiskOpSuperClass;
use Monarc\Core\Model\Entity\OperationalInstanceRiskScale;
use Monarc\Core\Model\Entity\OperationalInstanceRiskScaleSuperClass;

class OperationalInstanceRiskScaleTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = OperationalInstanceRiskScale::class)
    {
        parent::__construct($entityManager, $entityName);
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
