<?php
namespace MonarcCore\Model\Table;

class ModelTable extends AbstractEntityTable {

    public function resetCurrentDefault() {
        $defaults = $this->getEntityByFields(['isDefault' => true]);

        // There should only ever be one default model
        if (count($defaults) == 1) {
            /** @var \MonarcCore\Model\Entity\Model $def */
            $def = $defaults[0];

            $def->set('isDefault', 0);
            $this->save($def);
        }
    }

    /**
     * Get By Anrs
     *
     * @param $anrsId
     * @return array
     */
    public function getByAnrs($anrsId) {
        if(empty($anrsId)){
            $anrsId[] = 0;
        }

        $qb = $this->getRepository()->createQueryBuilder('m');

        return $qb
            ->select()
            ->where($qb->expr()->in('m.anr', $anrsId))
            ->getQuery()
            ->getResult();
    }
}
