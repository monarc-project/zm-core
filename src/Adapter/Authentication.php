<?php
namespace Monarc\Core\Adapter;

use Doctrine\ORM\EntityNotFoundException;
use Monarc\Core\Model\Entity\UserSuperClass;
use Monarc\Core\Model\Table\UserTable;
use Monarc\Core\Service\ConfigService;
use Laminas\Authentication\Adapter\AbstractAdapter;
use Laminas\Authentication\Result;
use RobThree\Auth\TwoFactorAuth;

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

    /** @var ConfigService */
    private $configService;

    const TWO_FA_AUTHENTICATION_REQUIRED = 2;
    const TWO_FA_AUTHENTICATION_TO_SET_UP = 3;

    public function __construct(UserTable $userTable, ConfigService $configService)
    {
        $this->userTable = $userTable;
        $this->configService = $configService;
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
     * @return UserSuperClass The current logged-in user
     */
    public function getUser(): UserSuperClass
    {
        return $this->user;
    }

    /**
     * Authenticates the user from its identity and credential.
     * @param string $token The OTP token submitted by the user or a recovery code.
     * @return Result The authentication result
     */
    public function authenticate($token = ''): Result
    {
        $identity = $this->getIdentity();
        $credential = $this->getCredential();
        try {
            $user = $this->userTable->findByEmail($identity);
        } catch (EntityNotFoundException $e) {
            return new Result(Result::FAILURE_IDENTITY_NOT_FOUND, $this->getIdentity());
        }

        // TODO: make the test on dateStart and dateEnd
        if ($user->isActive()) {
            if (password_verify($credential, $user->getPassword())) {
                $this->setUser($user);
                // if the user is using Two Factor Authentication
                file_put_contents('php://stderr', print_r('Correct password', TRUE).PHP_EOL);
                if ($user->isTwoFactorAuthEnabled()) {
                    // check if the 2FA token has been submitted
                    if (empty($token)) {
                        return new Result(self::TWO_FA_AUTHENTICATION_REQUIRED, $this->getIdentity());
                    } else {
                        // verify the submitted OTP token
                        $tfa = new TwoFactorAuth('MONARC TwoFactorAuth');
                        if ($tfa->verifyCode($user->getSecretKey(), $token)) {
                            return new Result(Result::SUCCESS, $this->getIdentity());
                        }

                        // verify the submitted recovery code
                        $recoveryCodes = $user->getRecoveryCodes();
                        foreach ($recoveryCodes as $key => $recoveryCode) {
                            if (password_verify($token, $recoveryCode)) {
                                unset($recoveryCodes[$key]);
                                $user->setRecoveryCodes($recoveryCodes);
                                $this->userTable->saveEntity($user);

                                return new Result(Result::SUCCESS, $this->getIdentity());
                            }
                        }
                    }
                } else if ($this->configService->isTwoFactorAuthEnforced()) {
                    file_put_contents('php://stderr', print_r('2FA enforced', TRUE).PHP_EOL);
                    if (empty($token)) {
                        // if two factor authentication is enforced and the user has not yet enabled it
                        return new Result(self::TWO_FA_AUTHENTICATION_TO_SET_UP, $this->getIdentity());
                    }
                }
                else {
                    return new Result(Result::SUCCESS, $this->getIdentity());
                }

                return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->getIdentity());
            }

            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->getIdentity());
        }
    }
}
