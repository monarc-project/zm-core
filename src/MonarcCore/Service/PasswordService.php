<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\PasswordToken;
use MonarcCore\Model\Entity\User;
use MonarcFO\Model\Table\PasswordTokenTable;

class PasswordService extends AbstractService
{
    protected $userTable;
    protected $userService;
    protected $mailService;

    /**
     * Password forgotten
     *
     * @param $email
     */
    public function passwordForgotten($email) {

        $user = $this->get('userTable')->getByEmail($email);

        if ($user) {

            $date = new \DateTime("now");
            $date->add(new \DateInterval("P1D"));

            //generate token
            $token = uniqid(bin2hex(openssl_random_pseudo_bytes(15)), true);
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

            //send mail
            $subject = 'Password forgotten';
            $link = 'http://' . $_SERVER['HTTP_HOST'] . '/#/passwordforgotten/' . htmlentities($token);
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
            $mailService->send($email, $subject, $message);
        }
    }

    /**
     * New Password By Token
     *
     * @param $token
     * @param $password
     */
    public function newPasswordByToken($token, $password) {


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
     * Verify Token
     *
     * @param $token
     * @return bool
     */
    public function verifyToken($token) {
        $date = new \DateTime("now");
        $passwordToken = $this->get('table')->getByToken($token, $date);

        if ($passwordToken) {
            return true;
        }

        return false;
    }

    /**
     * Change Password
     *
     * @param $userId
     * @param $oldPassword
     * @param $newPassword
     * @throws \Exception
     */
    public function changePassword($userId, $oldPassword, $newPassword) {

        $user = $this->get('userService')->getEntity($userId);

        if ($user) {
            if (password_verify($oldPassword, $user['password'])) {
                $this->get('userService')->patch($userId, ['password' => $newPassword]);
            } else {
                throw new \Exception('Original password incorrect', 412);
            }
        } else {
            throw new \Exception('User does not exist', 422);
        }
    }
}