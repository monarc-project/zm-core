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
}
