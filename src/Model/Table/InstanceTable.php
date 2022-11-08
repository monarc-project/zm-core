<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\AbstractEntity;
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\AssetSuperClass;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Service\ConnectedUserService;

class InstanceTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, Instance::class, $connectedUserService);
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findById(int $id): InstanceSuperClass
    {
        /** @var InstanceSuperClass|null $instance */
        $instance = $this->getRepository()->find($id);
        if ($instance === null) {
            throw EntityNotFoundException::fromClassNameAndIdentifier(\get_class($this), [$id]);
        }

        return $instance;
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function saveEntity(InstanceSuperClass $instance, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->persist($instance);
        if ($flushAll) {
            $em->flush();
        }
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
    public function findByAnrId(int $anrId)
    {
        return $this->getRepository()
            ->createQueryBuilder('i')
            ->innerJoin('i.anr', 'anr')
            ->where('anr.id = :anrId')
            ->setParameter('anrId', $anrId)
            ->getQuery()
            ->getResult();
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

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEntity(InstanceSuperClass $instance, bool $flushAll = true): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($instance);
        if ($flushAll) {
            $em->flush();
        }
    }
}
