<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\Db;
use Monarc\Core\Model\Entity\MonarcObject;
use Monarc\Core\Model\Entity\ObjectObject;
use Monarc\Core\Model\Entity\ObjectObjectSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class ObjectObjectTable
 * @package Monarc\Core\Model\Table
 */
class ObjectObjectTable extends AbstractEntityTable
{
    public function __construct(Db $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, ObjectObject::class, $connectedUserService);
    }

    /**
     * TODO: due to complications of double fields relation on FO side (uuid + anr) we cant simply add self-reference.
     * When it's refactored, we can change to $object->getFather() (normally getParent) and $object->getChildren().
     *
     * @param string[] $uuids
     *
     * @return MonarcObject[]
     */
    public function findChildrenByFatherUuids(array $uuids): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('o');

        return $queryBuilder
            ->select('o.child')
            ->innerJoin('o.father', 'parent')
            ->where($queryBuilder->expr()->in('parent.uuid', ':fatherUuids'))
            ->setParameter('fatherUuids', $uuids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string[] $uuids
     *
     * @return MonarcObject[]
     */
    public function findParentsByChildrenUuids(array $uuids): array
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('o');

        return $queryBuilder
            ->select('o.father')
            ->innerJoin('o.child', 'child')
            ->where($queryBuilder->expr()->in('child.uuid', ':childrenUuids'))
            ->setParameter('childrenUuids', $uuids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get Direct Parents Infos
     *
     * @param $child_id
     * @return array
     */
    public function getDirectParentsInfos($child_id, $anrid)
    {
        $stmt = $this->getDb()->getEntityManager()->getConnection()->prepare(
            'SELECT  o.name1, o.name2, o.name3, o.name4, o.label1, o.label2, o.label3, o.label4
            FROM objects_objects oo
            INNER JOIN objects o ON oo.father_id = o.uuid and oo.anr_id = o.anr_id
            WHERE oo.anr_id = :anrid
            AND oo.child_id = :oid'
        );

        $result = $stmt->executeQuery([':anrid' => $anrid, ':oid' => $child_id]);

        return $result->fetchAllAssociative();
    }

    /**
     * Get Direct Parents In Anr
     *
     * @param $anrid
     * @param $id
     * @return array
     */
    public function getDirectParentsInAnr($anrid, $id)
    {
        $stmt = $this->getDb()->getEntityManager()->getConnection()->prepare(
            'SELECT o.uuid, oo.id AS linkid, o.label1, o.label2, o.label3, o.label4, o.name1, o.name2, o.name3, o.name4
            FROM objects_objects oo
            INNER JOIN objects o ON o.uuid = oo.father_id AND o.anr_id = oo.anr_id
            INNER JOIN anrs_objects ao ON ao.object_id = o.uuid AND o.anr_id = ao.anr_id
            WHERE oo.anr_id = :anrid
            AND oo.child_id = :oid'
        );

        $result = $stmt->executeQuery([':anrid' => $anrid, ':oid' => $id]);

        return $result->fetchAllAssociative();
    }
}
