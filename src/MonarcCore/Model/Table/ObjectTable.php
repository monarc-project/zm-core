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
     * Get By Assets
     *
     * @param $assetsIds
     * @return array
     */
    public function getByAssets($assetsIds) {
        if(empty($assetsIds)){
            $assetsIds[] = 0;
        }

        $qb = $this->getRepository()->createQueryBuilder('o');

        return $qb
            ->select()
            ->where($qb->expr()->in('o.asset', $assetsIds))
            ->getQuery()
            ->getResult();
    }
}
