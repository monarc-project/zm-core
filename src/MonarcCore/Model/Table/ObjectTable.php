<?php
namespace MonarcCore\Model\Table;

class ObjectTable extends AbstractEntityTable {

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
     * @return ObjectTable
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
    public function getGenericByAssetId($assetId) {

        $objects =  $this->getRepository()->createQueryBuilder('o')
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
     *
     * Get anr by asset id
     *
     * @param $assetId
     * @return array
     */
    public function getAnrByAssetId($assetId) {

        $objects =  $this->getRepository()->createQueryBuilder('o')
            ->select(array('o.id'))
            ->where('o.asset = :assetId')
            ->andWhere('o.type = :type')
            ->setParameter(':assetId', $assetId)
            ->setParameter(':type', 'anr')
            ->getQuery()
            ->getResult();

        return $objects;
    }

    /**
     * Instantiate Object To Anr
     *
     * @param $anrId
     * @param $objectId
     * @param $parentId
     * @param $position
     * @throws Exception
     */
    public function instantiateObjectToAnr($anrId, $objectId, $parentId, $position) {

        $this->getDb()->beginTransaction();

        try {

            $this->getObjectObjectTable()->shiftPositionFromPosition($anrId, $parentId, $position); //change position other
            //create object
            //specifi position object

            $this->getDb()->commit();
        } catch (Exception $e) {
            $this->getDb()->rollBack();
            throw $e;
        }
    }

    /**
     * Find by type, source and anr
     *
     * @param $type
     * @param $sourceId
     * @param $anrId
     * @return array
     */
    public function findByTypeSourceAnr($type, $sourceId, $anrId) {

        $objects =  $this->getRepository()->createQueryBuilder('o')
            ->select(array('o.id'))
            ->where('o.type = :type')
            ->andWhere('o.anr = :anr')
            ->andWhere('o.source = :source')
            ->setParameter(':type', $type)
            ->setParameter(':anr', $anrId)
            ->setParameter(':source', $sourceId)
            ->getQuery()
            ->getResult();

        return $objects;

        var_dump($type);
        var_dump($objectId);
        var_dump($anrId);
        die;

    }
}
