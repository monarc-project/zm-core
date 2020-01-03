<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\DbCli;
use Monarc\Core\Model\Entity\UserToken;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class UserTokenTable
 * @package Monarc\Core\Model\Table
 */
class UserTokenTable extends AbstractEntityTable
{
    public function __construct(DbCli $db, ConnectedUserService $connectedUserService)
    {
        parent::__construct($db, UserToken::class, $connectedUserService);
    }

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
