<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\OperationalRiskScale;
use Monarc\Core\Entity\OperationalRiskScaleSuperClass;

class OperationalRiskScaleTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = OperationalRiskScale::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function findByAnrAndScaleId(AnrSuperClass $anr, int $scaleId): OperationalRiskScaleSuperClass
    {
        $scale = $this->getRepository()->createQueryBuilder('ors')
            ->where('ors.anr = :anr')
            ->andWhere('ors.id = :scaleId')
            ->setParameter('anr', $anr)
            ->setParameter('scaleId', $scaleId)
            ->getQuery()
            ->getOneOrNullResult();

        if ($scale === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$anr->getId(), $scaleId]);
        }

        return $scale;
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

    /**
     * @return OperationalRiskScaleSuperClass[]
     */
    public function findByAnrAndType(AnrSuperClass $anr, int $type): array
    {
        return $this->getRepository()->createQueryBuilder('ors')
            ->where('ors.anr = :anr')
            ->andWhere('ors.type = :type')
            ->setParameter('anr', $anr)
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }
}
