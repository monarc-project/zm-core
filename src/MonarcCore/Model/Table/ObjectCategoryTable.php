<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Model\Table;

/**
 * Object Category Table
 *
 * Class ObjectCategoryTable
 * @package MonarcCore\Model\Table
 */
class ObjectCategoryTable extends AbstractEntityTable
{
    /**
     * Get Child
     *
     * @param $id
     * @return array
     */
    public function getChild($id)
    {
        $child = $this->getRepository()->createQueryBuilder('t')
            ->select(array('t.id'))
            ->where('t.parent = :parent')
            ->setParameter(':parent', $id)
            ->getQuery()
            ->getResult();

        return $child;
    }
}