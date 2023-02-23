<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2023 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use Doctrine\ORM\EntityManager;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Table\Interfaces\PositionUpdatableTableInterface;
use Monarc\Core\Table\Traits\PositionIncrementTableTrait;

class InstanceTable extends AbstractTable implements PositionUpdatableTableInterface
{
    use PositionIncrementTableTrait;

    public function __construct(EntityManager $entityManager, $entityName = Instance::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    /**
     * @return InstanceSuperClass[]
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
    public function findGlobalBrothersByAnrAndInstance(AnrSuperClass $anr, InstanceSuperClass $instance): array
    {
        return $this->getRepository()
            ->createQueryBuilder('i')
            ->innerJoin('i.object', 'o')
            ->where('i.anr = :anr')
            ->andWhere('o.uuid = :object_uuid')
            ->andWhere('o.anr = :anr')
            ->andWhere('i.id != :id')
            ->andWhere('o.scope = :scopeMode')
            ->setParameter('anr', $anr)
            ->setParameter('id', $instance->getId())
            ->setParameter('object_uuid', $instance->getObject()->getUuid())
            ->setParameter('scopeMode', ObjectSuperClass::SCOPE_GLOBAL)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return InstanceSuperClass[]
     */
    public function findByObject(ObjectSuperClass $object): array
    {
        return $this->getRepository()
            ->createQueryBuilder('i')
            ->where('i.object = :object')
            ->setParameter('object', $object)
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
    public function findByAnrAndOrderByParams(AnrSuperClass $anr, array $orderBy = []): array
    {
        $queryBuilder = $this->getRepository()
            ->createQueryBuilder('i')
            ->where('i.anr = :anr')
            ->setParameter('anr', $anr);

        foreach ($orderBy as $fieldName => $order) {
            $queryBuilder->addOrderBy($fieldName, $order);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return Instance[]
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
            ->andWhere('o.anr = :object_anr')
            ->andWhere('i.id <> :instanceId')
            ->setParameter('anr', $anr)
            ->setParameter('object_uuid', $object->getUuid())
            ->setParameter('object_anr', $anr)
            ->setParameter('instanceId', $instanceToExclude->getId())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Manage Delete Position
     *
     * @param AbstractEntity $entity
     * @param array $params
     */
    protected function manageDeletePosition(AbstractEntity $entity, $params = array())
    {
        $return = $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position - 1');
        $hasWhere = false;
        if (!empty($params['field'])) {
            $hasWhere = true;
            if (is_null($entity->get($params['field']))) {
                $return = $return->where('t.' . $params['field'] . ' IS NULL');
            } else {
                $return = $return->where('t.' . $params['field'] . ' = :' . $params['field'])
                    ->setParameter(':' . $params['field'], $entity->get($params['field']));
            }
        }

        $anr = $entity->get('anr');
        if ($anr) {
            $return = $return->andWhere('t.anr = :anr')
                ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
        } else {
            $return = $return->andWhere('t.anr IS NULL');
        }

        if ($hasWhere) {
            $return = $return->andWhere('t.position >= :pos');
        } else {
            $return = $return->where('t.position >= :pos');
        }
        $return = $return->setParameter(':pos', $entity->get('position'));
        $return->getQuery()->getResult();
    }

    /**
     * @return InstanceSuperClass[]
     */
    public function findByAsset(AssetSuperClass $asset): array
    {
        return $this->getRepository()
            ->createQueryBuilder('i')
            ->where('i.asset = :asset')
            ->setParameter('asset', $asset)
            ->getQuery()
            ->getResult();
    }

    public function findOneByAnrParentAndPosition(
        AnrSuperClass $anr,
        ?InstanceSuperClass $parentInstance,
        int $position
    ): ?InstanceSuperClass {
        $queryBuilder = $this->getRepository()->createQueryBuilder('a')
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
            ->getResult();
    }
}
