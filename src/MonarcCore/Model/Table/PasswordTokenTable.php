<?php
namespace MonarcCore\Model\Table;

class PasswordTokenTable extends AbstractEntityTable {

    /**
     * Get By Token
     *
     * @param $token
     * @return array
     */
    public function getByToken($token, $date) {

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
    public function deleteOld() {

        $date = new \DateTime("now");

        $this->getRepository()->createQueryBuilder('pt')
            ->delete()
            ->where('pt.dateEnd < :date')
            ->setParameter(':date', $date->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();
    }

}
