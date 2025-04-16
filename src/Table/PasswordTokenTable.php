<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Table;

use DateTime;
use Doctrine\ORM\EntityManager;
use Monarc\Core\Entity\PasswordToken;
use Monarc\Core\Entity\PasswordTokenSuperClass;

class PasswordTokenTable extends AbstractTable
{
    public function __construct(EntityManager $entityManager, string $entityName = PasswordToken::class)
    {
        parent::__construct($entityManager, $entityName);
    }

    public function getByToken(string $token, DateTime $date): ?PasswordTokenSuperClass
    {
        $result = $this->getRepository()->createQueryBuilder('pt')
            ->select('pt')
            ->where('pt.token = :token')
            ->andWhere('pt.dateEnd >= :date')
            ->setParameter('token', $token)
            ->setParameter('date', $date->format('Y-m-d H:i:s'))
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
            ->setParameter('date', (new DateTime())->format('Y-m-d H:i:s'))
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
            ->setParameter('token', $token)
            ->getQuery()
            ->getResult();
    }
}
