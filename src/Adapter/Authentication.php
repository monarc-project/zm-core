<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Adapter;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Table\UserTable;
use Laminas\Authentication\Adapter\AbstractAdapter;
use Laminas\Authentication\Result;

/**
 * Class Authentication is an implementation of AbstractAdapter that takes care of authenticating an user.
 * This is heavily inspired from Laminas Auth.
 *
 * @package Monarc\Core\Adapter
 */
class Authentication extends AbstractAdapter
{
    /** @var UserTable */
    private $userTable;

    /** @var UserSuperClass */
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

    /**
     * @return UserSuperClass The current logged-in user.
     */
    public function getUser(): UserSuperClass
    {
        return $this->user;
    }

    /**
     * Authenticates the user from its identity and credential.
     *
     * @return Result The authentication result.
     */
    public function authenticate(): Result
    {
        $identity = $this->getIdentity();
        $credential = $this->getCredential();
        try {
            $user = $this->userTable->findByEmail($identity);
        } catch (EntityNotFoundException $e) {
            return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, $this->getIdentity());
        }

        // TODO: Can be implemented validation of dateStart && dateEnd.
        if ($user->isActive()) {
            if (password_verify($credential, $user->getPassword())) {
                $this->user = $user;

                return new Result(Result::SUCCESS, $this->getIdentity());
            }

            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->getIdentity());
        }
    }
}
