<?php
namespace MonarcCore\Model\Table;

class UserRoleTable extends AbstractEntityTable {

    /**
     * Delete By User
     *
     * @param $userId
     */
    public function deleteByUser($userId) {

        $this->getRepository()->createQueryBuilder('ur')
            ->delete()
            ->where('ur.user = :user')
            ->setParameter(':user', $userId)
            ->getQuery()
            ->getResult();
    }
}