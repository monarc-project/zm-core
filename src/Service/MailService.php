<?php
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2020 SMILE GIE Securitymadein.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Sendmail;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part;

/**
 * Mail Service
 *
 * Class MailService
 * @package Monarc\Core\Service
 */
class MailService
{
    public function send(string $email, string $subject, string $message, array $from): void
    {
        $html = new Part($message);
        $html->type = 'text/html';

        $body = new MimeMessage();
        $body->setParts(array($html));

        $mimeMessage = (new Message())
            ->setBody($body)
            ->setFrom($from['from'], $from['name'])
            ->addTo($email, $email)
            ->setSubject($subject);

        (new Sendmail())->send($mimeMessage);
    }
}
