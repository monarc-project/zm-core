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
    }

    /**
     * Find by Anr
     *
     * @param $anrId
     * @return array
     */
    public function findByAnr($anrId) {
        $objects =  $this->getRepository()->createQueryBuilder('o')
            ->select(array(
                'o.id', 'o.position',
                'IDENTITY(o.category) as categoryId',
                'o.name1', 'o.name2', 'o.name3', 'o.name4',
                'o.label1', 'o.label2', 'o.label3', 'o.label4'
            ))
            ->where('o.type = \'anr\'')
            ->andWhere('o.anr = :anr')
            ->setParameter(':anr', $anrId)
            ->orderBy('o.position', 'ASC')
            ->getQuery()
            ->getResult();

        return $objects;
    }
}
