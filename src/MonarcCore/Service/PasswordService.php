<?php
namespace MonarcCore\Service;

use MonarcCore\Model\Entity\PasswordToken;
use MonarcCore\Model\Entity\User;

class PasswordService extends AbstractService
{
    protected $userService;

    protected $mailService;

    protected $passwordTokenTable;

    public function passwordForgotten($email) {

        $user = $this->userService->getByEmail($email);

        if (count($user) == 1) {

            $user = $user[0];
            $user['status'] = 1;

            $userEntity = new User();
            $userEntity->exchangeArray($user);

            var_dump($user); die;





            $date = new \DateTime("now");
            $date->add(new \DateInterval("P1D"));

            //generate token
            $token = uniqid('', true);
            $passwordTokenData = [
                'user' => $userEntity,
                'token' => $token,
                'dateEnd' => $date
            ];

            $passwordTokenEntity = new PasswordToken();
            $passwordTokenEntity->exchangeArray($passwordTokenData);

            $passwordTokenTable = $this->get('passwordTokenTable');
            $passwordTokenTable->save($passwordTokenEntity);

            //send mail
            $subject = 'Password forgotten';
            $link = 'http://localhost:8080/api/admin/password?token=' . htmlentities($token);
            $message = "<p>Hello,</p>
                <p>This is an automatically generated e-mail, please do not reply.</p>
                <p>
                Thank you for requesting a new password, please confirm your request by clicking on the link below :<br />
                <a href='" . $link . "'><strong>" . $link . "</strong></a>
                </p>
                <p>In case you have not made request for a new password, we kindly ask you to ignore this e-mail</p>
                <p>Best regards,</p>";

            $mailService = $this->get('mailService');
            $mailService->send($email, $subject, $message);
        }
    }

    public function newPassword($token, $password) {

        $passwordToken = $this->getByToken($token);

        if (count($passwordToken)) {
            $passwordToken = $passwordToken[0];

            $userId = $passwordToken['userId'];

            if (new \DateTime('now') < $passwordToken['dateEnd']) {
                $user = $this->userService->getEntity($userId);
                $user['password'] = password_hash($password, PASSWORD_BCRYPT);
                $this->userService->update($user);
            }
        }
    }

    public function getByToken($token) {

        $passwordTokenTable = $this->get('passwordTokenTable');

        return $passwordTokenTable->getRepository()->createQueryBuilder('pt')
            ->select(array('pt.id', 'IDENTITY(pt.user) as userId', 'pt.token', 'pt.dateEnd'))
            ->where('pt.token = :token')
            ->setParameter(':token', $token)
            ->getQuery()
            ->getResult();
    }
}