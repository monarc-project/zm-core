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

    const TWO_FA_REQUIRED = 2;
    const TWO_FA_TO_SET_UP = 3;

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

        if ($user->isActive()) {
            if (password_verify($credential, $user->getPassword())) {
                $this->setUser($user);

                /* Validate if the user has 2FA enabled. */
                if (!$user->isTwoFactorAuthEnabled()) {
                    /* Validate if 2FA is enforced on the platform. */
                    if ($this->configService->isTwoFactorAuthEnforced() && empty($token)) {
                        /* 2FA enforced and missing tokens in order to activate 2FA */
                        return new Result(self::TWO_FA_TO_SET_UP, $this->getIdentity());
                    } elseif ($this->configService->isTwoFactorAuthEnforced()) {
                        /* 2FA enforced and received tokens in order to activate 2FA */
                        /* tokens are the verification code and the otp secret */
                        $tokens = explode(":", $token);
                        // verify the submitted OTP token
                        $tfa = new TwoFactorAuth('MONARC TwoFactorAuth');
                        if ($tfa->verifyCode($tokens[0], $tokens[1])) {
                            $user->setSecretKey($tokens[0]);
                            $user->setTwoFactorAuthEnabled(true);
                            $this->userTable->saveEntity($user);
                            return new Result(Result::SUCCESS, $this->getIdentity());
                        }
                        return new Result(self::TWO_FA_TO_SET_UP, $this->getIdentity());
                    }
                    return new Result(Result::SUCCESS, $this->getIdentity());
                }

                /* Validate if the 2FA token has been submitted. */
                if (empty($token)) {
                    return new Result(static::TWO_FA_REQUIRED, $this->getIdentity());
                }

                /* Verify the submitted OTP token. */
                $tfa = new TwoFactorAuth('MONARC TwoFactorAuth');
                if ($tfa->verifyCode($user->getSecretKey(), $token)) {
                    return new Result(Result::SUCCESS, $this->getIdentity());
                }

                /* Verify the submitted recovery code. */
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

            return new Result(Result::FAILURE_CREDENTIAL_INVALID, $this->getIdentity());
        }
    }
}
