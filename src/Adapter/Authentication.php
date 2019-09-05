<?php
namespace Monarc\Core\Adapter;

use Monarc\Core\Model\Entity\User;
use Monarc\Core\Model\Table\UserTable;
use Zend\Authentication\Adapter\AbstractAdapter;
use Zend\Authentication\Result;

/**
 * Class Authentication is an implementation of AbstractAdapter that takes care of authenticating an user.
 * This is heavily inspired from Zend Auth.
 *
 * @package Monarc\Core\Adapter
 */
class Authentication extends AbstractAdapter
{
    /** @var UserTable */
    private $userTable;

    /** @var User */
    protected $user;

    public function __construct(UserTable $userTable)
    {
        $this->userTable = $userTable;
    }

    /**
     * Sets the current active (logged in) user
     *
     * @param User $user The user
     *
     * @return $this For chaining calls
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User The current logged-in user
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Authenticates the user from its identity and credential
     *
     * @return Result The authentication result
     */
    public function authenticate(): Result
    {
        $identity = $this->getIdentity();
        $credential = $this->getCredential();
        $users = $this->userTable->getRepository()->findByEmail($identity);
        switch (count($users)) {
            case 0:
                return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, $this->getIdentity());
            case 1:
                $user = current($users);
                // TODO: faire le test sur dateStart && dateEnd
                if ($user->get('status')) {
                    if ($this->securityService->verifyPwd($credential, $user->get('password'))) {
                        $this->setUser($user);

                        return new Result(Result::SUCCESS, $this->getIdentity());
                    }

                    return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->getIdentity());
                }

                return new Result(Result::FAILURE_UNCATEGORIZED, $this->getIdentity());
            default:
                return new Result(Result::FAILURE_IDENTITY_AMBIGUOUS, $this->getIdentity());
        }
    }
}
