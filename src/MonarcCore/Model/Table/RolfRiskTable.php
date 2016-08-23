<?php
namespace MonarcCore\Model\Table;

class RolfRiskTable extends AbstractEntityTable {

    /**
     * Get by tag
     *
     * @param $tagId
     * @return array|bool
     */
    public function getByTag($tagId) {
        return $this->getEntityByFields(['tags' => $tagId]);
    }
}
