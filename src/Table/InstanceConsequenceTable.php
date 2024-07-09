<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\InstanceConsequence;
use Monarc\Core\Entity\InstanceConsequenceSuperClass;
use Monarc\Core\Entity\InstanceSuperClass;
use Monarc\Core\Entity\ScaleImpactTypeSuperClass;

class InstanceConsequenceTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = InstanceConsequence::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return InstanceConsequenceSuperClass[]
     */
    public function findByScaleImpactType(ScaleImpactTypeSuperClass $scaleImpactType): array
    {
        return $this->getRepository()->createQueryBuilder('ic')
            ->where('ic.scaleImpactType = :scaleImpactType')
            ->setParameter('scaleImpactType', $scaleImpactType)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstanceConsequenceSuperClass[]
     */
    public function findByAnrInstanceAndScaleImpactType(
        AnrSuperClass $anr,
        InstanceSuperClass $instance,
        ScaleImpactTypeSuperClass $scaleImpactType
    ): array {
        return $this->getRepository()->createQueryBuilder('ic')
            ->where('ic.anr = :anr')
            ->andWhere('ic.instance = :instance')
            ->andWhere('ic.scaleImpactType = :scaleImpactType')
            ->setParameter('anr', $anr)
            ->setParameter('instance', $instance)
            ->setParameter('scaleImpactType', $scaleImpactType)
            ->getQuery()
            ->getResult();
    }
}
