<?php
namespace MonarcCore\Adapter;

use MonarcCore\Model\Entity\User;
use MonarcCore\Model\Table\UserTable;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result;

/**
 * Class Authentication is an implementation of AbstractAdapter that takes care of authenticating an user.
 * This is heavily inspired from Zend Auth.
 * @package MonarcCore\Adapter
 */
class Authentication extends AbstractAdapter
{
    protected $userTable;
    protected $user;
    protected $security;

    /**
     * Sets the user table to use to check credentials
     * @param \MonarcCore\Model\Table\UserTable $userTable The user table to use
     * @return $this For chaining calls
     */
    public function setUserTable(\MonarcCore\Model\Table\UserTable $userTable)
    {
        $this->userTable = $userTable;
        return $this;
    }

    /**
     * @return UserTable The user table used to authenticate
     */
    public function getUserTable()
    {
        return $this->userTable;
    }

    /**
     * Sets the current active (logged in) user
     * @param User $user The user
     * @return $this For chaining calls
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return User The current logged-in user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the security service to use
     * @param object $security
     * @return $this For chaining calls
     */
    public function setSecurity($security)
    {
        $this->security = $security;
        return $this;
    }

    /**
     * @return object The security service used
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * Authenticates the user from its identity and credential
     * @return Result The authentication result
     */
    public function authenticate()
    {
        $identity = $this->getIdentity();
        $credential = $this->getCredential();
        $users = $this->getUserTable()->getRepository()->findByEmail($identity);
        switch (count($users)) {
            case 0:
                return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, $this->getIdentity());
                break;
            case 1:
                $user = current($users);
                //$now = mktime();
                // TODO: faire le test sur dateStart && dateEnd
                if ($user->get('status')) {
                    if ($this->getSecurity()->verifyPwd($credential, $user->get('password'))) {
                        $this->setUser($user);
                        return new Result(Result::SUCCESS, $this->getIdentity());
                    } else {
                        return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->getIdentity());
                    }
                } else {
                    return new Result(Result::FAILURE_UNCATEGORIZED, $this->getIdentity());
                }
                break;
            default:
                return new Result(Result::FAILURE_IDENTITY_AMBIGUOUS, $this->getIdentity());
                break;
        }
    }
}
