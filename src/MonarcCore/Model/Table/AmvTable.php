<?php
namespace MonarcCore\Model\Table;

class AmvTable extends AbstractEntityTable {

    /**
     * Find By AMV
     *
     * @param $assetId
     * @param $threatId
     * @param $vulnerabilityId
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findByAMV($assetId, $threatId, $vulnerabilityId) {

        $parameters = [];
        if (!is_null($assetId)) {
            $parameters['asset'] = $assetId;
        }
        if (!is_null($threatId)) {
            $parameters['threat'] = $threatId;
        }
        if (!is_null($vulnerabilityId)) {
            $parameters['vulnerability'] = $vulnerabilityId;
        }

        $amvs = $this->getRepository()->createQueryBuilder('amv')
            ->select(array(
                'amv.id',
                'IDENTITY(amv.asset) as assetId',
                'IDENTITY(amv.threat) as threatId',
                'IDENTITY(amv.vulnerability) as vulnerabilityId'
            ));

        $first = true;
        foreach ($parameters as $parameter => $value) {
            if ($first) {
                $amvs->where('amv.' . $parameter . ' = :' . $parameter);
                $first = false;
            } else {
                $amvs->andWhere('amv.' . $parameter . ' = :' . $parameter);
            }
            $amvs->setParameter(':' . $parameter, $value);
        }

        return $amvs->getQuery()->getResult();
    }

}