<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
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