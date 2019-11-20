<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Model\Table;

use DateTime;
use Doctrine\ORM\ORMException;
use Monarc\Core\Model\DbCli;
use Monarc\Core\Model\Entity\PasswordToken;
use Monarc\Core\Model\Entity\PasswordTokenSuperClass;
use Monarc\Core\Service\ConnectedUserService;

/**
 * Class PasswordTokenTable
 * @package Monarc\Core\Model\Table
 */
class PasswordTokenTable extends AbstractEntityTable
{
    public function __construct(DbCli $dbService, ConnectedUserService $connectedUserService)
    {
        parent::__construct($dbService, PasswordToken::class, $connectedUserService);
    }

    public function getByToken(string $token, DateTime $date): ?PasswordTokenSuperClass
    {
        $result = $this->getRepository()->createQueryBuilder('pt')
            ->select('pt')
            ->where('pt.token = :token')
            ->andWhere('pt.dateEnd >= :date')
            ->setParameter(':token', $token)
            ->setParameter(':date', $date->format('Y-m-d H:i:s'))
            ->setMaxResults(1)
            ->getQuery()
            ->getResult();

        return !empty($result) ? $result[0] : null;
    }

    public function deleteOld(): void
    {
        $this->getRepository()->createQueryBuilder('pt')
            ->delete()
            ->where('pt.dateEnd < :date')
            ->setParameter(':date', (new DateTime())->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete token
     *
     * @param $token
     */
    public function deleteToken(string $token): void
    {
        $this->getRepository()->createQueryBuilder('t')
            ->delete()
            ->where('t.token = :token')
            ->setParameter(':token', $token)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete By User
     *
     * @param $userId
     */
    public function deleteByUser(int $userId): void
    {
        $this->getRepository()->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->setParameter(':user', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * TODO: move it to an abstract table class (also rename the method to save) when we remove AbstractEntityTable.
     * @throws ORMException
     */
    public function saveEntity(PasswordTokenSuperClass $passwordToken): void
    {
        // TODO: EntityManager has to be injected instead of the db class, actually we can remove db classes at all.
        $this->db->getEntityManager()->persist($passwordToken);
        $this->db->getEntityManager()->flush();
    }
}
