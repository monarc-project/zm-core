<?php
namespace MonarcCore\Model\Table;

class UserTokenTable extends AbstractEntityTable {

    /**
     * Delete By User
     *
     * @param $userId
     */
    public function deleteByUser($userId) {

        $this->getRepository()->createQueryBuilder('ut')
            ->delete()
            ->where('ut.user = :user')
            ->setParameter(':user', $userId)
            ->getQuery()
            ->getResult();
    }
}
