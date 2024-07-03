<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mail\Message;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part;

class MailService
{
    private array $smtpOptions = [];

    public function __construct(ConfigService $configService)
    {
        $smtpOptions = $configService->getConfigOption('smtpOptions');
        if (!empty($smtpOptions)) {
            $this->smtpOptions = $smtpOptions;
        }
    }

    public function send(string $email, string $subject, string $message, array $from): void
    {
        $transport = new SmtpTransport();
        if (!empty($this->smtpOptions)) {
            $transport->setOptions(new SmtpOptions($this->smtpOptions));
        }

        $html = new Part($message);
        $html->type = 'text/html';

        $body = new MimeMessage();
        $body->setParts([$html]);

        $mimeMessage = (new Message())
            ->setBody($body)
            ->setFrom($from['from'], $from['name'])
            ->addTo($email, $email)
            ->setSubject($subject);

        $transport->send($mimeMessage);
    }
}
