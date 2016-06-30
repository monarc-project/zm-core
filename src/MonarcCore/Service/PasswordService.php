<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\PasswordToken;
use MonarcCore\Model\Entity\User;

class PasswordService extends AbstractService
{
    protected $passwordTokenEntity;
    protected $passwordTokenTable;
    protected $userTable;
    protected $userService;
    protected $mailService;

    /**
     * Password forgotten
     *
     * @param $email
     */
    public function passwordForgotten($email) {

        $user = $this->get('userService')->getByEmail($email);

        if ($user) {

            $date = new \DateTime("now");
            $date->add(new \DateInterval("P1D"));

            //generate token
            $token = uniqid('', true);
            $passwordTokenData = [
                'user' => $user['id'],
                'token' => $token,
                'dateEnd' => $date
            ];

            $passwordTokenEntity = $this->get('passwordTokenEntity');
            $passwordTokenEntity->exchangeArray($passwordTokenData);

            $this->setDependencies($passwordTokenEntity, ['user']);

            $this->get('passwordTokenTable')->save($passwordTokenEntity);

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

            $this->get('mailService')->send($email, $subject, $message);
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
        $passwordToken = $this->get('passwordTokenTable')->getByToken($token, $date);

        if ($passwordToken) {

            $this->get('userService')->patch($passwordToken['userId'], ['password' => $password]);
        }
    }

    /**
     * Verify Token
     *
     * @param $token
     * @return bool
     */
    public function verifyToken($token) {
        $date = new \DateTime("now");
        $passwordToken = $this->get('passwordTokenTable')->getByToken($token, $date);

        if ($passwordToken) {
            return true;
        }

        return false;
    }
}