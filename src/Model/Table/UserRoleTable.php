<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Monarc\Core\Model\DbCli;
use Monarc\Core\Model\Entity\UserRole;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class UserRoleTable
 * @package Monarc\Core\Model\Table
 */
class UserRoleTable extends AbstractEntityTable
{
    public function __construct(DbCli $db, ConnectedUserService $connectedUserService)
    {
        parent::__construct($db, UserRole::class, $connectedUserService);
    }

    /**
     * Delete By User ID
     *
     * @param $userId
     */
    public function deleteByUser($userId)
    {
        $this->getRepository()
            ->createQueryBuilder('ur')
            ->delete()
            ->where('ur.user = :user')
            ->setParameter(':user', $userId)
            ->getQuery()
            ->getResult();
    }
}
