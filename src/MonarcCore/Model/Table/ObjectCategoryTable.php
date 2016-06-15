<?php
namespace MonarcCore\Model\Table;

class ObjectCategoryTable extends AbstractEntityTable {

    /**
     * Get Child
     *
     * @param $id
     * @return array
     */
    public function getChild($id) {
        $child = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        return $child;
    }

    /**
     * Get Descendants
     * @param $id
     * @return array
     */
    public function getDescendants($id) {

        $childList = [];

        $this->getRecursiveChild($childList, $id);

        return $childList;
    }

    /**
     * Get Recursive Child
     *
     * @param $childList
     * @param $id
     */
    protected function getRecursiveChild(&$childList, $id) {
        $childs = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        if (count($childs)) {
            foreach ($childs as $child) {
                $childList[] = $child['id'];
                $this->getRecursiveChild($childList, $child['id']);
            }
        }
    }

}
