<?php
namespace MonarcCore\Model\Table;

class ObjectRiskTable extends AbstractEntityTable {

    /**
     * Get By Anr And Object
     *
     * @param $anrId
     * @param $objectId
     * @return array|bool
     */
    public function getByAnrAndObject($anrId, $objectId) {
        return $this->getEntityByFields(['anr' => $anrId, 'object' => $objectId]);
    }

}
