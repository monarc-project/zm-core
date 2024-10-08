<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\AnrSuperClass;
use Monarc\Core\Entity\Instance;
use Monarc\Core\Entity\InstanceSuperClass;
use Monarc\Core\Entity\ObjectSuperClass;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use Monarc\Core\Table\Traits\PositionIncrementTableTrait;

class InstanceTable extends AbstractTable implements PositionUpdatableTableInterface
{
    use PositionIncrementTableTrait;

    public function __construct(EntityManager $entityManager, string $entityName = Instance::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return Instance[]
     */
    public function findByAnrAndObject(AnrSuperClass $anr, ObjectSuperClass $object): array
    {
        return $this->getRepository()
            ->createQueryBuilder('i')
            ->innerJoin('i.object', 'o')
            ->where('i.anr = :anr')
            ->andWhere('o.uuid = :object_uuid')
            ->setParameter('anr', $anr)
            ->setParameter('object_uuid', $object->getUuid())
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstanceSuperClass[]
     */
    public function findRootsByAnr(AnrSuperClass $anr): array
    {
        return $this->getRepository()
            ->createQueryBuilder('i')
            ->where('i.anr = :anr')
            ->andWhere('i.parent is NULL')
            ->setParameter('anr', $anr)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstanceSuperClass[]
     */
    public function findRootInstancesByAnrAndOrderByPosition(AnrSuperClass $anr): array
    {
        return $this->getRepository()
            ->createQueryBuilder('i')
            ->where('i.anr = :anr')
            ->andWhere('i.parent IS NULL')
            ->setParameter('anr', $anr)
            ->addOrderBy('i.position')
            ->getQuery()
            ->getResult();
    }

    public function findOneByAnrAndObjectExcludeInstance(
        AnrSuperClass $anr,
        ObjectSuperClass $object,
        InstanceSuperClass $instanceToExclude
    ): ?InstanceSuperClass {
        return $this->getRepository()
            ->createQueryBuilder('i')
            ->innerJoin('i.object', 'o')
            ->where('i.anr = :anr')
            ->andWhere('o.uuid = :object_uuid')
            ->andWhere('i.id <> :instanceId')
            ->setParameter('anr', $anr)
            ->setParameter('object_uuid', $object->getUuid())
            ->setParameter('instanceId', $instanceToExclude->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByAnrParentAndPosition(
        AnrSuperClass $anr,
        ?InstanceSuperClass $parentInstance,
        int $position
    ): ?InstanceSuperClass {
        $queryBuilder = $this->getRepository()->createQueryBuilder('i')
            ->where('i.anr = :anr')
            ->setParameter('anr', $anr);
        if ($parentInstance === null) {
            $queryBuilder->andWhere('i.parent IS NULL');
        } else {
            $queryBuilder->andWhere('i.parent = :parent')->setParameter('parent', $parentInstance);
        }

        return $queryBuilder
            ->andWhere('i.position = :position')
            ->setParameter('position', $position)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
