<?php

declare(strict_types=1);

namespace EICC\SendPoint\Service;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use RuntimeException;
use Throwable;

class EmailService
{
    public function __construct(
        private string $dsn,
        private string $fromEmail,
        private string $fromName
    ) {}

    public function send(
        string $to,
        string $subject,
        string $body,
        ?string $replyTo = null
    ): bool {
        try {
            $transport = Transport::fromDsn($this->dsn);
            $mailer = new Mailer($transport);

            $email = (new Email())
                ->from($this->fromName . ' <' . $this->fromEmail . '>')
                ->to($to)
                ->subject($subject)
                ->text($body);

            if ($replyTo) {
                $email->replyTo($replyTo);
            }

            $mailer->send($email);
            return true;
        } catch (Throwable $e) {
            // Log the error in the controller, but rethrow so we know it failed
            throw new RuntimeException('Failed to send email: ' . $e->getMessage(), 0, $e);
        }
    }
}
