<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class UserTokenTable
 * @package MonarcCore\Model\Table
 */
class UserTokenTable extends AbstractEntityTable
{
    /**
     * Delete By User
     *
     * @param $userId
     */
    public function deleteByUser($userId)
    {
        $this->getRepository()->createQueryBuilder('ut')
            ->delete()
            ->where('ut.user = :user')
            ->setParameter(':user', $userId)
            ->getQuery()
            ->getResult();
    }
}