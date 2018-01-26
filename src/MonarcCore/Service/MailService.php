<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2018 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace MonarcCore\Service;

use Zend\Mail\Message;
use Zend\Mail\Transport\Sendmail;
use Zend\Mime\Part;

/**
 * Mail Service
 *
 * Class MailService
 * @package MonarcCore\Service
 */
class MailService extends AbstractService
{
    protected $configService;

    /**
     * Send an email to the specified recipient, with the subject and message set
     * @param string $email Email address
     * @param string $subject Email subject
     * @param string $message Email message
     * @param string $from Email sender (optionnal)
     */
    public function send($email, $subject, $message, $from)
    {
        $html = new Part($message);
        $html->type = "text/html";

        $body = new \Zend\Mime\Message();
        $body->setParts(array($html));

        $message = new Message();
        $message->setBody($body);
        $message->setFrom($from['from'], $from['name']);
        $message->addTo($email, $email);
        $message->setSubject($subject);

        $transport = new Sendmail();
        $transport->send($message);
    }
}
