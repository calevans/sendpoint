<?php

declare(strict_types=1);

namespace EICC\SendPoint\Controller;

use EICC\Utils\Container;
use EICC\SendPoint\Service\FormConfigService;
use EICC\SendPoint\Service\FormValidatorService;
use EICC\SendPoint\Service\EmailService;
use EICC\SendPoint\Exception\ValidationException;
use Twig\Environment;
use Throwable;

class FormController
{
    public function __construct(
        private Container $container
    ) {}

    public function handleRequest(): void
    {
        $logger = $this->container->get('logger');

        // 1. Validate IP
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

        // In a real environment, we might trust REMOTE_ADDR.
        // If behind a proxy, we might need to check headers, but for this internal tool, REMOTE_ADDR is likely correct.

        $allowedIps = explode(',', $_ENV['ALLOWED_IPS'] ?? '127.0.0.1');
        $allowedIps = array_map('trim', $allowedIps);

        if (!in_array($remoteIp, $allowedIps)) {
            http_response_code(403);
            echo "Forbidden: IP " . $remoteIp . " not allowed.";
            if ($logger) {
                $logger->log('WARNING', sprintf('Unauthorized access attempt from IP: %s', $remoteIp));
            }
            return;
        }

        // 2. Validate Method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        // 3. Load Config
        $formId = $_POST['FORMID'] ?? '';
        if (empty($formId)) {
            http_response_code(400);
            echo "Bad Request: Missing FORMID";
            return;
        }

        /** @var FormConfigService $configService */
        $configService = $this->container->get(FormConfigService::class);
        $config = $configService->loadConfig($formId);

        if ($config === null) {
            http_response_code(400);
            echo "Bad Request: Invalid FORMID";
            if ($logger) {
                $logger->log('WARNING', sprintf('Unknown FORMID attempt: %s from IP: %s', $formId, $remoteIp));
            }
            return;
        }

        // 4. Validate Data
        /** @var FormValidatorService $validatorService */
        $validatorService = $this->container->get(FormValidatorService::class);

        try {
            $cleanedData = $validatorService->validate($_POST, $config);
        } catch (ValidationException $e) {
            http_response_code(400);
            echo "Bad Request: " . $e->getMessage();
            if ($logger) {
                $logger->log('WARNING', sprintf('Validation failure for FORMID %s: %s from IP: %s', $formId, $e->getMessage(), $remoteIp));
            }
            return;
        }

        // 5. Render Template
        /** @var Environment $twig */
        $twig = $this->container->get('twig');

        try {
            $body = $twig->render($formId . '.twig', $cleanedData);
        } catch (Throwable $e) {
            http_response_code(500);
            echo "Internal Server Error";
            if ($logger) {
                $logger->log('ERROR', sprintf('Template rendering failed for FORMID %s: %s from IP: %s', $formId, $e->getMessage(), $remoteIp));
            }
            return;
        }

        // 6. Send Email
        /** @var EmailService $emailService */
        $emailService = $this->container->get(EmailService::class);

        $recipient = $config['recipient'] ?? '';
        $subject = $config['subject'] ?? 'New Submission';

        // Determine Reply-To
        $replyToField = $config['reply_to_field'] ?? null;
        $replyTo = null;
        if ($replyToField && isset($cleanedData[$replyToField])) {
            $replyTo = $cleanedData[$replyToField];
        }

        try {
            $emailService->send($recipient, $subject, $body, $replyTo);

            http_response_code(200);
            echo "OK";

            if ($logger) {
                $logger->log('INFO', sprintf('Email sent successfully for FORMID %s to %s from IP: %s', $formId, $recipient, $remoteIp));
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo "Internal Server Error";
            if ($logger) {
                $logger->log('ERROR', sprintf('Email sending failed for FORMID %s: %s from IP: %s', $formId, $e->getMessage(), $remoteIp));
            }
        }
    }
}