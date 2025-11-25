<?php

declare(strict_types=1);

namespace EICC\SendPoint\Service;

use EICC\Utils\Container;
use EICC\SendPoint\Exception\ValidationException;
use Twig\Environment;
use Throwable;

class FormSubmissionHandler
{
    public function __construct(
        private Container $container
    ) {}

    public function handle(string $formId, array $postData, string $remoteIp): void
    {
        $logger = $this->container->get('logger');

        // 1. Load Config
        /** @var FormConfigService $configService */
        $configService = $this->container->get(FormConfigService::class);
        $config = $configService->loadConfig($formId);

        if ($config === null) {
            if ($logger) {
                $logger->log('WARNING', sprintf('Unknown FORMID attempt: %s from IP: %s', $formId, $remoteIp));
            }
            throw new ValidationException("Invalid FORMID");
        }

        $this->handleWithConfig($formId, $config, $postData, $remoteIp);
    }

    public function handleWithConfig(string $formId, array $config, array $postData, string $remoteIp): void
    {
        $logger = $this->container->get('logger');

        // 2. Validate Data
        /** @var FormValidatorService $validatorService */
        $validatorService = $this->container->get(FormValidatorService::class);

        try {
            $cleanedData = $validatorService->validate($postData, $config);
        } catch (ValidationException $e) {
            if ($logger) {
                $logger->log('WARNING', sprintf('Validation failure for FORMID %s: %s from IP: %s', $formId, $e->getMessage(), $remoteIp));
            }
            throw $e;
        }

        // 3. Render Template
        /** @var Environment $twig */
        $twig = $this->container->get('twig');

        try {
            $body = $twig->render($formId . '.twig', $cleanedData);
        } catch (Throwable $e) {
            if ($logger) {
                $logger->log('ERROR', sprintf('Template rendering failed for FORMID %s: %s from IP: %s', $formId, $e->getMessage(), $remoteIp));
            }
            throw new \RuntimeException("Internal Server Error");
        }

        // 4. Send Email
        /** @var EmailService $emailService */
        $emailService = $this->container->get(EmailService::class);

        $recipient = $config['recipient'] ?? null;
        if (empty($recipient)) {
            if ($logger) {
                $logger->log('ERROR', sprintf('Configuration error: Missing recipient for FORMID %s', $formId));
            }
            throw new \RuntimeException("Configuration Error: Missing recipient");
        }

        $subject = $config['subject'] ?? 'New Submission';

        // Determine Reply-To
        $replyToField = $config['reply_to_field'] ?? null;
        $replyTo = null;
        if ($replyToField && isset($cleanedData[$replyToField])) {
            $replyTo = $cleanedData[$replyToField];
        }

        try {
            $emailService->send($recipient, $subject, $body, $replyTo);

            if ($logger) {
                $logger->log('INFO', sprintf('Email sent successfully for FORMID %s to %s from IP: %s', $formId, $recipient, $remoteIp));
            }
        } catch (Throwable $e) {
            if ($logger) {
                $logger->log('ERROR', sprintf('Email sending failed for FORMID %s: %s from IP: %s', $formId, $e->getMessage(), $remoteIp));
            }
            throw new \RuntimeException("Internal Server Error");
        }
    }
}
