<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2019  SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Monarc\FrontOffice\Model\Table\PasswordTokenTable;

/**
 * Password Service
 *
 * Class PasswordService
 * @package Monarc\Core\Service
 */
class PasswordService extends AbstractService
{
    protected $userTable;
    protected $userService;
    protected $mailService;
    protected $securityService;
    /** @var  ConfigService */
    protected $configService;

    /**
     * Handles password forgotten
     * @param string $email The email to which send the reset password email
     */
    public function passwordForgotten($email)
    {
        $user = $this->get('userTable')->getByEmail($email);

        if ($user) {

            $date = new \DateTime("now");
            $date->add(new \DateInterval("P1D"));

            //generate token
            $token = uniqid(bin2hex(openssl_random_pseudo_bytes(rand(20, 40))), true);
            $passwordTokenData = [
                'user' => $user['id'],
                'token' => $token,
                'dateEnd' => $date
            ];

            $passwordTokenEntity = $this->get('entity');
            $passwordTokenEntity->exchangeArray($passwordTokenData);

            $this->setDependencies($passwordTokenEntity, ['user']);


            /** @var PasswordTokenTable $passwordTokenTable */
            $passwordTokenTable = $this->get('table');
            $passwordTokenTable->save($passwordTokenEntity);

            $host = $this->configService->gethost();

            if (empty($host)) {
                // Determine HTTP/HTTPS proto, and HTTP_HOST
                if (isset($_SERVER['X_FORWARDED_PROTO'])) {
                    $proto = strtolower($_SERVER['X_FORWARDED_PROTO']);
                } else if (isset($_SERVER['X_URL_SCHEME'])) {
                    $proto = strtolower($_SERVER['X_URL_SCHEME']);
                } else if (isset($_SERVER['X_FORWARDED_SSL'])) {
                    $proto = (strtolower($_SERVER['X_FORWARDED_SSL']) == 'on') ? 'https' : 'http';
                } else if (isset($_SERVER['FRONT_END_HTTPS'])) { // Microsoft variant
                    $proto = (strtolower($_SERVER['FRONT_END_HTTPS']) == 'on') ? 'https' : 'http';
                } else if (isset($_SERVER['HTTPS'])) {
                    $proto = 'https';
                } else {
                    $proto = 'http';
                }

                if (isset($_SERVER['X_FORWARDED_HOST'])) {
                    $host = $proto. '://' . $_SERVER['X_FORWARDED_HOST'];
                } else {
                    $host = $proto. '://' . $_SERVER['HTTP_HOST'];
                }
            }

            //send mail
            $subject = 'Password forgotten';
            $link = $host . '/#/passwordforgotten/' . htmlentities($token);
            $message = "<p>Hello,</p>
                <p>This is an automatically generated e-mail, please do not reply.</p>
                <p>
                Thank you for requesting a new password, please confirm your request by clicking on the link below :<br />
                <a href='" . $link . "'><strong>" . $link . "</strong></a>
                </p>
                <p>In case you have not made request for a new password, we kindly ask you to ignore this e-mail</p>
                <p>Best regards,</p>";

            /** @var MailService $mailService */
            $mailService = $this->get('mailService');
            $from = $this->configService->getemail();
            $mailService->send($email, $subject, $message, $from);
        }
    }

    /**
     * Sets a new password based on the reset token passed
     * @param string $token The reset token
     * @param string $password The new password for the account associated with the token
     */
    public function newPasswordByToken($token, $password)
    {
        $date = new \DateTime("now");
        $passwordToken = $this->get('table')->getByToken($token, $date);

        if ($passwordToken) {
            $this->get('userService')->patch($passwordToken['userId'], ['password' => $password]);

            //delete current token
            $this->get('table')->deleteToken($token);
        }

        //delete old tokens
        $this->get('table')->deleteOld();
    }

    /**
     * Verifies if the passed token is a valid password reset token
     * @param string $token The password reset token
     * @return bool True if the token is valid, false otherwise
     */
    public function verifyToken($token)
    {
        $date = new \DateTime("now");
        $passwordToken = $this->get('table')->getByToken($token, $date);

        if ($passwordToken) {
            return true;
        }

        return false;
    }

    /**
     * Changes the password for the specified user ID based on its old password
     * @param int $userId The user ID
     * @param string $oldPassword The previous (current) user password
     * @param string $newPassword The new password to set
     * @throws \Exception If the origin password is incorrect, or user does not exist
     */
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        $user = $this->get('userService')->getEntity($userId);

        if ($user) {
            if ($this->securityService->verifyPwd($oldPassword, $user['password'])) {
                $this->get('userService')->patch($userId, ['password' => $newPassword]);
            } else {
                throw new \Monarc\Core\Exception\Exception('Original password incorrect', 412);
            }
        } else {
            throw new \Monarc\Core\Exception\Exception('User does not exist', 422);
        }
    }
}
