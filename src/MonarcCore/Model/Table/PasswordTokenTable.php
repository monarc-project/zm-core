<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class PasswordTokenTable
 * @package MonarcCore\Model\Table
 */
class PasswordTokenTable extends AbstractEntityTable
{
    /**
     * Get By Token
     *
     * @param $token
     * @return array
     */
    public function getByToken($token, $date)
    {
        $passwordToken = $this->getRepository()->createQueryBuilder('pt')
            ->select(array('pt.id', 'IDENTITY(pt.user) as userId', 'pt.token', 'pt.dateEnd'))
            ->where('pt.token = :token')
            ->andWhere('pt.dateEnd >= :date')
            ->setParameter(':token', $token)
            ->setParameter(':date', $date->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();

        if (count($passwordToken)) {
            return $passwordToken[0];
        } else {
            return null;
        }
    }

    /**
     * Delete Old
     */
    public function deleteOld()
    {
        $date = new \DateTime("now");

        $this->getRepository()->createQueryBuilder('pt')
            ->delete()
            ->where('pt.dateEnd < :date')
            ->setParameter(':date', $date->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete token
     *
     * @param $token
     */
    public function deleteToken($token)
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
    public function deleteByUser($userId)
    {
        $this->getRepository()->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->setParameter(':user', $userId)
            ->getQuery()
            ->getResult();
    }
}
