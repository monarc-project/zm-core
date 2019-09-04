<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\Mapping\MappingException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\DbCli;
use Monarc\Core\Model\Entity\User;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class UserTable
 * @package Monarc\Core\Model\Table
 */
class UserTable extends AbstractEntityTable
{
    /** @var UserRoleTable */
    private $userRoleTable;

    /** @var UserTokenTable */
    private $userTokenTable;

    /** @var PasswordTokenTable */
    private $passwordTokenTable;

    public function __construct(
        DbCli $db,
        ConnectedUserService $connectedUserService,
        UserRoleTable $userRoleTable,
        UserTokenTable $userTokenTable,
        PasswordTokenTable $passwordTokenTable
    ) {
        parent::__construct($db, User::class, $connectedUserService->getConnectedUser());

        $this->userRoleTable = $userRoleTable;
        $this->userTokenTable = $userTokenTable;
        $this->passwordTokenTable = $passwordTokenTable;
    }

    /**
     * @param $email
     * @return mixed
     *
     * @throws Exception
     */
    public function getByEmail($email)
    {
        $users = $this->getRepository()->createQueryBuilder('u')
            ->select(array('u.id', 'u.firstname', 'u.lastname', 'u.email', 'u.status'))
            ->where('u.email = :email')
            ->setParameter(':email', $email)
            ->getQuery()
            ->getResult();

        if (!count($users)) {
            throw new Exception('Entity does not exist', 422);
        }

        return $users[0];
    }

    /**
     * @throws Exception
     * @throws ForeignKeyConstraintViolationException
     * @throws MappingException
     */
    public function delete($id, $last = true): bool
    {
        $this->getDb()->beginTransaction();

        try {
            $this->userRoleTable->deleteByUser($id);
            $this->userTokenTable->deleteByUser($id);
            $this->passwordTokenTable->deleteByUser($id);
            parent::delete($id, $last);

            $this->getDb()->commit();
        } catch (Exception $e) {
            $this->getDb()->rollBack();

            throw $e;
        }
    }
}
