<?php
namespace MonarcCore\Model\Table;

class ObjectTable extends AbstractEntityTable {

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
}
