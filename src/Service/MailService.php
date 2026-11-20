<?php declare(strict_types=1);
/**
 * @link      https://github.com/monarc-project for the canonical source repository
 * @copyright Copyright (c) 2016-2024 Luxembourg House of Cybersecurity LHC.lu - Licensed under GNU Affero GPL v3
 * @license   MONARC is licensed under GNU Affero General Public License version 3
 */

namespace Monarc\Core\Service;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

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
        $mimeMessage = (new Email())
            ->from(new Address($from['from'], $from['name']))
            ->to(new Address($email, $email))
            ->subject($subject)
            ->html($message);

        $this->createTransport()->send($mimeMessage);
    }

    private function createTransport(): TransportInterface
    {
        $connectionConfig = $this->smtpOptions['connection_config'] ?? [];
        $host = $this->smtpOptions['host'] ?? 'localhost';
        $port = (int) ($this->smtpOptions['port'] ?? 25);
        $ssl = strtolower((string) ($connectionConfig['ssl'] ?? ''));
        $scheme = $ssl === 'ssl' ? 'smtps' : 'smtp';

        $credentials = '';
        if (isset($connectionConfig['username'])) {
            $credentials = rawurlencode((string) $connectionConfig['username']);
            $credentials .= ':' . rawurlencode((string) ($connectionConfig['password'] ?? '')) . '@';
        }

        $query = [];
        if ($ssl === 'tls') {
            $query['require_tls'] = 'true';
        }

        $authMode = $this->smtpOptions['connection_class'] ?? null;
        if (in_array($authMode, ['plain', 'login', 'cram-md5'], true)) {
            $query['auth_mode'] = $authMode;
        }

        $dsn = sprintf('%s://%s%s:%d', $scheme, $credentials, $host, $port);
        if ($query !== []) {
            $dsn .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return Transport::fromDsn($dsn);
    }
}
