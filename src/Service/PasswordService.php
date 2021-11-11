<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2021 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use DateTime;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\ORMException;
use Monarc\Core\Exception\Exception;
use Monarc\Core\Model\Entity\PasswordToken;
use Monarc\Core\Model\Entity\User;
use Monarc\Core\Table\PasswordTokenTable;
use Monarc\Core\Table\UserTable;
use Monarc\Core\Validator\PasswordStrength;

/**
 * Password Service
 *
 * Class PasswordService
 * @package Monarc\Core\Service
 */
class PasswordService
{
    /** @var PasswordTokenTable */
    private $passwordTokenTable;

    /** @var UserTable */
    private $userTable;

    /** @var MailService */
    private $mailService;

    /** @var  ConfigService */
    protected $configService;

    public function __construct(
        PasswordTokenTable $passwordTokenTable,
        UserTable $userTable,
        MailService $mailService,
        ConfigService $configService
    ) {
        $this->passwordTokenTable = $passwordTokenTable;
        $this->userTable = $userTable;
        $this->mailService = $mailService;
        $this->configService = $configService;
    }

    /**
     * @throws Exception
     * @throws ORMException
     */
    public function passwordForgotten(string $email)
    {
        $user = $this->userTable->findByEmail($email);
        $token = uniqid(bin2hex(random_bytes(random_int(20, 40))), true);

        $passwordToken = new PasswordToken($token, $user, new DateTime('+1 day'));
        $this->passwordTokenTable->save($passwordToken);

        $subject = 'Restore password';
        $link = $this->configService->getHost() . '/#/passwordforgotten/' . htmlentities($token);
        $message = <<<EMAIL_MESSAGE
<p>Hello,</p>
<p>This is an automatically generated e-mail, please do not reply.</p>
<p>Thank you for requesting a new password, please confirm your request by clicking on the link below :<br />
    <a href="$link"><strong>$link</strong></a>
</p>
<p>In case you have not made request for a new password, we kindly ask you to ignore this e-mail</p>
<p>Best regards,<br />Monarc Team</p>
EMAIL_MESSAGE;

        $this->mailService->send($email, $subject, $message, $this->configService->getEmail());
    }

    /**
     * @throws Exception
     * @throws ORMException
     */
    public function newPasswordByToken(string $token, string $password): void
    {
        $passwordToken = $this->passwordTokenTable->getByToken($token, new DateTime());

        if ($passwordToken) {
            $this->validatePassword($password);

            $this->userTable->save($passwordToken->getUser()->setPassword($password));

            $this->passwordTokenTable->deleteToken($token);
        }

        $this->passwordTokenTable->deleteOld();
    }

    /**
     * Verifies if the passed token is a valid password reset token.
     *
     * @param string $token The password reset token
     *
     * @return bool True if the token is valid, false otherwise
     */
    public function verifyToken(string $token): bool
    {
        return (bool)$this->passwordTokenTable->getByToken($token, new DateTime());
    }

    /**
     * Changes the password for the specified user ID based on its old password.
     *
     * @throws Exception If the origin password is incorrect
     * @throws ORMException
     * @throws EntityNotFoundException
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): void
    {
        /** @var User $user */
        $user = $this->userTable->findById($userId);

        if (!password_verify($oldPassword, $user->getPassword())) {
            throw new Exception('Original password incorrect', 412);
        }

        $this->validatePassword($newPassword);

        $this->userTable->save($user->setPassword($newPassword));
    }

    /**
     * Changes the password for the specified user ID.
     *
     * @throws ORMException
     * @throws EntityNotFoundException
     */
    public function changePasswordWithoutOldPassword(int $userId, string $newPassword): void
    {
        $user = $this->userTable->findById($userId);

        $this->validatePassword($newPassword);

        $this->userTable->save($user->setPassword($newPassword));
    }

    /**
     * Reset the password for the specified user ID.
     *
     * @throws ORMException
     * @throws EntityNotFoundException
     */
    public function resetPassword(int $userId): void
    {
        $user = $this->userTable->findById($userId);

        $this->userTable->save($user->resetPassword());
    }

    /**
     * TODO: Move to the Controller action validation.
     *
     * Validates that the password matches the required strength policy (special chars, lower/uppercase, number)
     *
     * @param string $password
     *
     * @throws Exception If password is invalid
     */
    protected function validatePassword(string $password): void
    {
        $passwordValidator = new PasswordStrength();
        if (!$passwordValidator->isValid($password)) {
            $errors = [];
            foreach ($passwordValidator->getMessages() as $message) {
                $errors[] = $message;
            }

            throw new Exception('Password validation errors: [ ' . implode(', ', $errors) . ' ].', 412);
        }
    }
}
