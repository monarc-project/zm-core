<?php
namespace Monarc\Core\Adapter;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\UserSuperClass;
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

    /** @var UserSuperClass|null */
    protected $user;

    public function __construct(UserTable $userTable)
    {
        $this->userTable = $userTable;
    }

    /**
     * Sets the current active (logged in) user
     */
    public function setUser(UserSuperClass $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?UserSuperClass
    {
        return $this->user;
    }

    public function authenticate(): Result
    {
        $identity = $this->getIdentity();
        $credential = $this->getCredential();
        try {
            $user = $this->userTable->getByEmail($identity);
        } catch (EntityNotFoundException $e) {
            return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, $identity);
        }

        // TODO: check if user is active within the date range: dateStart && dateEnd.
        if ($user->isActive()) {
            if (password_verify($credential, $user->getPassword())) {
                $this->setUser($user);

                return new Result(Result::SUCCESS, $identity);
            }

            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $identity);
        }

        return new Result(Result::FAILURE_UNCATEGORIZED, $identity);
    }
}
