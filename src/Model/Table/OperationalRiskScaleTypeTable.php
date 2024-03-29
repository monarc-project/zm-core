<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\OperationalRiskScaleType;
use Monarc\Core\Model\Entity\OperationalRiskScaleTypeSuperClass;

class OperationalRiskScaleTypeTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = OperationalRiskScaleType::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return OperationalRiskScaleTypeSuperClass[]
     */
    public function findByAnrAndScaleType(AnrSuperClass $anr, int $scaleType): array
    {
        return $this->getRepository()->createQueryBuilder('orst')
            ->innerJoin('orst.operationalRiskScale', 'ors')
            ->where('orst.anr = :anr')
            ->andWhere('ors.type = :scaleType')
            ->setParameter('anr', $anr)
            ->setParameter('scaleType', $scaleType)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return OperationalRiskScaleTypeSuperClass[]
     */
    public function findByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()->createQueryBuilder('orst')
            ->where('orst.anr = :anr')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }
}
