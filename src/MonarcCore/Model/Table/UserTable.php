<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Model\Table;

/**
 * Class UserTable
 * @package MonarcCore\Model\Table
 */
class UserTable extends AbstractEntityTable
{
    protected $userRoleTable;
    protected $userTokenTable;
    protected $passwordTokenTable;

    /**
     * @return mixed
     */
    public function getUserRoleTable()
    {
        return $this->userRoleTable;
    }

    /**
     * @param mixed $userRoleTable
     * @return UserTable
     */
    public function setUserRoleTable($userRoleTable)
    {
        $this->userRoleTable = $userRoleTable;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserTokenTable()
    {
        return $this->userTokenTable;
    }

    /**
     * @param mixed $userTokenTable
     * @return UserTable
     */
    public function setUserTokenTable($userTokenTable)
    {
        $this->userTokenTable = $userTokenTable;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPasswordTokenTable()
    {
        return $this->passwordTokenTable;
    }

    /**
     * @param mixed $passwordTokenTable
     * @return UserTable
     */
    public function setPasswordTokenTable($passwordTokenTable)
    {
        $this->passwordTokenTable = $passwordTokenTable;
        return $this;
    }

    /**
     * Get By Email
     *
     * @param $email
     * @return mixed
     * @throws \Exception
     */
    public function getByEmail($email)
    {
        $users = $this->getRepository()->createQueryBuilder('u')
            ->select(array('u.id', 'u.firstname', 'u.lastname', 'u.email', 'u.phone', 'u.status'))
            ->where('u.email = :email')
            ->setParameter(':email', $email)
            ->getQuery()
            ->getResult();

        if (!count($users)) {
            throw new \Exception('Entity does not exist', 422);
        } else {
            return $users[0];
        }
    }

    /**
     * Delete
     *
     * @param $id
     * @param bool $last
     * @throws \Exception
     */
    public function delete($id, $last = true)
    {

        if ($this->getConnectedUser()['id'] == $id) {
            throw new \Exception("You can't delete yourself", 412);
        }

        $this->getDb()->beginTransaction();

        try {

            $this->getUserRoleTable()->deleteByUser($id);
            $this->getUserTokenTable()->deleteByUser($id);
            $this->getPasswordTokenTable()->deleteByUser($id);
            parent::delete($id, $last);

            $this->getDb()->commit();
        } catch (Exception $e) {
            $this->getDb()->rollBack();
            throw $e;
        }
    }
}