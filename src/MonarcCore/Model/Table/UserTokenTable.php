<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
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