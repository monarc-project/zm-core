<?php
namespace MonarcCore\Model\Table;

class ObjectTable extends AbstractEntityTable {

    /**
     * Change positions by category
     *
     * @param $objectCategoryId
     * @param $position
     * @param string $direction
     * @param string $referential
     * @param bool $strict
     * @return array
     */
    public function changePositionsByCategory($objectCategoryId, $position, $direction = 'up', $referential = 'after', $strict = false)
    {
        $positionDirection = ($direction == 'up') ? '+1' : '-1';
        $sign = ($referential == 'after') ? '>' : '<';
        if (!$strict) {
            $sign .= '=';
        }

        return $this->getRepository()->createQueryBuilder('t')
            ->update()
            ->set('t.position', 't.position' . $positionDirection)
            ->where('t.category = :objectcategoryid')
            ->andWhere('t.position ' . $sign . ' :position')
            ->setParameter(':objectcategoryid', $objectCategoryId)
            ->setParameter(':position', $position)
            ->getQuery()
            ->getResult();
    }

    /**
     * Max position by category
     *
     * @param $objectCategoryId
     * @return mixed
     */
    public function maxPositionByCategory($objectCategoryId)
    {
        $maxPosition =  $this->getRepository()->createQueryBuilder('t')
            ->select(array('max(t.position)'))
            ->where('t.category = :objectcategoryid')
            ->setParameter(':objectcategoryid', $objectCategoryId)
            ->getQuery()
            ->getResult();

        return $maxPosition[0][1];
    }
}
