<?php
namespace MonarcCore\Model\Table;

class UserTable extends AbstractEntityTable {

    /**
     * Get By Email
     *
     * @param $email
     * @return mixed
     * @throws \Exception
     */
    public function getByEmail($email) {
        $users =  $this->getRepository()->createQueryBuilder('u')
            ->select(array('u.id', 'u.firstname', 'u.lastname', 'u.email', 'u.phone', 'u.status'))
            ->where('u.email = :email')
            ->setParameter(':email', $email)
            ->getQuery()
            ->getResult();

        if (! count($users)) {
            throw new \Exception('Entity not exist', 422);
        } else {
            return $users[0];
        }
    }

}