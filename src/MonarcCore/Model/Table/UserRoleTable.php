<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class UserRoleTable
 * @package MonarcCore\Model\Table
 */
class UserRoleTable extends AbstractEntityTable
{
    /**
     * Delete By User
     *
     * @param $userId
     */
    public function deleteByUser($userId)
    {
        $this->getRepository()->createQueryBuilder('ur')
            ->delete()
            ->where('ur.user = :user')
            ->setParameter(':user', $userId)
            ->getQuery()
            ->getResult();
    }
}