<?php
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
use Monarc\Core\Model\Entity\AnrSuperClass;
use Monarc\Core\Model\Entity\Instance;
use Monarc\Core\Model\Entity\InstanceSuperClass;
use Monarc\Core\Model\Entity\ObjectSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class InstanceTable
 * @package Monarc\Core\Model\Table
 */
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
            ->andWhere('o.anr = :object_anr')
            ->setParameter('anr', $anr)
            ->setParameter('object_uuid', $object->getUuid())
            ->setParameter('object_anr', $anr)
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
     * Get Ascendance
     *
     * @param $instance
     * @return array
     */
    public function getAscendance($instance)
    {
        $root = $instance->get('root');
        $idRoot = null;
        $arbo[] = $instance->getJsonArray();
        if (!empty($root)) {
            $idRoot = $root->get('id');
        }
        if (!empty($idRoot)) {
            $idAnr = $instance->get('anr')->get('id');
            $result = $this->getRepository()->createQueryBuilder('i')
                ->where('i.anr = :anrId')
                ->andWhere('i.root = :rootid')
                ->setParameter(':anrId', empty($idAnr) ? null : $idAnr)
                ->setParameter(':rootid', $idRoot)
                ->getQuery()
                ->getResult();
            $family = array();
            foreach ($result as $r) {
                $p = $r->get('parent');
                if (!empty($p)) {//Cyril - Fix Trello [#9KkvKj5M] - not sure it's enough, what if we want the anr itself (null)
                    $family[$r->get('id')][$r->get('parent')->get('id')] = $r->get('parent')->getJsonArray();
                }
            }
            $temp = array();
            $temp[] = $instance->getJsonArray();
            while (count($temp)) {
                $cur = array_shift($temp);
                if (isset($family[$cur['id']])) {
                    foreach ($family[$cur['id']] as $id => $parent) {
                        $arbo[$id] = $parent;
                        $temp[] = $parent;
                    }
                }
            }
        }
        return array_reverse($arbo);
    }

    /**
     * Build Where For Position Create
     *
     * @param $params
     * @param $queryBuilder
     * @param \Monarc\Core\Model\Entity\AbstractEntity $entity
     * @param string $newOrOld
     * @return mixed
     */
    protected function buildWhereForPositionCreate($params, $queryBuilder, \Monarc\Core\Model\Entity\AbstractEntity $entity, $newOrOld = 'new')
    {
        $queryBuilder = parent::buildWhereForPositionCreate($params, $queryBuilder, $entity, $newOrOld);
        $anr = $entity->get('anr');
        if ($anr) {
            $queryBuilder = $queryBuilder->andWhere('t.anr = :anr')
                ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
        } else {
            $queryBuilder = $queryBuilder->andWhere('t.anr IS NULL');
        }
        return $queryBuilder;
    }

    /**
     * Manage Delete Position
     *
     * @param \Monarc\Core\Model\Entity\AbstractEntity $entity
     * @param array $params
     */
    protected function manageDeletePosition(\Monarc\Core\Model\Entity\AbstractEntity $entity, $params = array())
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
     * Count Position Max
     *
     * @param \Monarc\Core\Model\Entity\AbstractEntity $entity
     * @param array $params
     * @return mixed
     */
    protected function countPositionMax(\Monarc\Core\Model\Entity\AbstractEntity $entity, $params = array())
    {
        $return = $this->getRepository()->createQueryBuilder('t')
            ->select('COUNT(t.id)');
        if (!empty($params['field'])) {
            if (isset($params['newField'][$params['field']])) {
                if (is_null($params['newField'][$params['field']])) {
                    $return = $return->where('t.' . $params['field'] . ' IS NULL');
                } else {
                    $return = $return->where('t.' . $params['field'] . ' = :' . $params['field'])
                        ->setParameter(':' . $params['field'], $params['newField'][$params['field']]);
                }
            }
        }
        $anr = $entity->get('anr');
        if ($anr) {
            $return = $return->andWhere('t.anr = :anr')
                ->setParameter(':anr', is_object($anr) ? $anr->get('id') : $anr);
        } else {
            $return = $return->andWhere('t.anr IS NULL');
        }

        $id = $entity->get('id');
        return $return->getQuery()->getSingleScalarResult() + ($id ? 0 : 1);
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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deleteEntity(InstanceSuperClass $instance): void
    {
        $em = $this->getDb()->getEntityManager();
        $em->remove($instance);
        $em->flush();
    }
}
