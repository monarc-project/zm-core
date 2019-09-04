<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

/**
 * Class MonarcObjectTable
 * @package Monarc\Core\Model\Table
 */
class MonarcObjectTable extends AbstractEntityTable
{
    protected $objectObjectTable;

    /**
     * @return mixed
     */
    public function getObjectObjectTable()
    {
        return $this->objectObjectTable;
    }

    /**
     * @param mixed $objectObjectTable
     * @return MonarcObjectTable
     */
    public function setObjectObjectTable($objectObjectTable)
    {
        $this->objectObjectTable = $objectObjectTable;
        return $this;
    }

    /**
     * Get generic by asset id
     *
     * @param $assetId
     * @return array
     */
    public function getGenericByAssetId($assetId)
    {
        $objects = $this->getRepository()->createQueryBuilder('o')
            ->select(array('o.id'))
            ->where('o.asset = :assetId')
            ->andWhere('o.mode = :mode')
            ->setParameter(':assetId', $assetId)
            ->setParameter(':mode', 0)
            ->getQuery()
            ->getResult();

        return $objects;
    }

    /**
     * Check In Anr
     *
     * @param $anrid
     * @param $id
     * @return bool
     */
    public function checkInAnr($anrid, $id)
    {
        $stmt = $this->getDb()->getEntityManager()->getConnection()->prepare(
            'SELECT id
             FROM   anrs_objects
             WHERE  anr_id = :anrid
             AND    object_id = :oid'
        );
        $stmt->execute([':anrid' => $anrid, ':oid' => $id]);

        return $stmt->rowCount() > 0;
    }
}
