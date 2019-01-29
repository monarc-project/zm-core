<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Table;

/**
 * Class ObjectObjectTable
 * @package MonarcCore\Model\Table
 */
class ObjectObjectTable extends AbstractEntityTable
{

    /**
     * Get Childs
     *
     * @param $objectId
     * @return array
     */
    public function getChildren($objectId)
    {
        $child = $this->getRepository()->createQueryBuilder('o')
            ->select(array('IDENTITY(o.child) as childId', 'o.position'))
            ->where('o.father = :father')
            ->setParameter(':father', $objectId)
            ->getQuery()
            ->getResult();

        return $child;
    }

    /**
     * Get Direct Parents Infos
     *
     * @param $child_id
     * @return array
     */
    public function getDirectParentsInfos($child_id)
    {
        return $this->getRepository()->createQueryBuilder('oo')
            ->select(['o.name1', 'o.name2', 'o.name3', 'o.name4', 'o.label1', 'o.label2', 'o.label3', 'o.label4'])
            ->innerJoin('oo.father', 'o')
            ->where('oo.child = :child_id')
            ->setParameter(':child_id', $child_id)
            ->getQuery()
            ->getResult();
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
            'SELECT o.id, oo.id as linkid, o.label1, o.label2, o.label3, o.label4, o.name1, o.name2, o.name3, o.name4
            FROM objects_objects oo
            INNER JOIN objects o ON oo.father_id = o.id
            INNER JOIN anrs_objects ao ON ao.object_id = o.id
            WHERE ao.anr_id = :anrid
            AND oo.child_id = :oid'
        );

        $stmt->execute([':anrid' => $anrid, ':oid' => $id]);
        return $stmt->fetchAll();
    }
}
