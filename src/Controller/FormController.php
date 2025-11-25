<?php

declare(strict_types=1);

namespace EICC\SendPoint\Controller;

use EICC\Utils\Container;
use EICC\SendPoint\Exception\ValidationException;
use EICC\SendPoint\Exception\RateLimitException;
use Throwable;

class FormController
{
    public function __construct(
        private Container $container
    ) {}

    public function handleRequest(): void
    {
        $logger = $this->container->get('logger');

        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

        // Rate Limiting
        try {
            /** @var \EICC\SendPoint\Service\RateLimitService $rateLimitService */
            $rateLimitService = $this->container->get(\EICC\SendPoint\Service\RateLimitService::class);
            $rateLimitService->checkLimit($remoteIp);
        } catch (RateLimitException $e) {
            http_response_code(429);
            echo "Too Many Requests";
            if ($logger) {
                $logger->log('WARNING', sprintf('Rate limit exceeded for IP: %s', $remoteIp));
            }
            return;
        }

        // 0. Handle Altcha Challenge Request
        if (($_GET['action'] ?? '') === 'challenge') {
            /** @var \EICC\SendPoint\Service\AltchaService $altchaService */
            $altchaService = $this->container->get(\EICC\SendPoint\Service\AltchaService::class);
            header('Content-Type: application/json');
            // Allow CORS for challenge (or rely on general CORS logic? No, general logic requires FORMID)
            // We should probably allow all origins for challenge or require FORMID to check allowed_origins.
            // For simplicity and security, let's require FORMID even for challenge if we want to restrict origins.
            // But the widget might not send FORMID easily in the URL unless configured.
            // Let's assume public challenge generation is OK (protected by Rate Limit).
            header('Access-Control-Allow-Origin: *');
            echo json_encode($altchaService->generateChallenge());
            return;
        }

        // 1. Load Config (Early for CORS)
        $formId = $_REQUEST['FORMID'] ?? '';
        if (empty($formId)) {
            http_response_code(400);
            echo "Bad Request: Missing FORMID";
            return;
        }

        /** @var \EICC\SendPoint\Service\FormConfigService $configService */
        $configService = $this->container->get(\EICC\SendPoint\Service\FormConfigService::class);
        $config = $configService->loadConfig($formId);

        if ($config === null) {
            http_response_code(400);
            echo "Bad Request: Invalid FORMID";
            if ($logger) {
                $logger->log('WARNING', sprintf('Unknown FORMID attempt: %s from IP: %s', $formId, $remoteIp));
            }
            return;
        }

        // 2. CORS Handling (Per-Form)
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = $config['allowed_origins'] ?? [];
        // Ensure array
        if (!is_array($allowedOrigins)) {
            $allowedOrigins = [$allowedOrigins];
        }

        // Require Origin header if allowed_origins is defined
        if (!empty($allowedOrigins) && empty($origin)) {
            http_response_code(400);
            echo "Bad Request: Missing Origin header";
            if ($logger) {
                $logger->log('WARNING', sprintf('CORS failure for FORMID %s: Missing Origin header', $formId));
            }
            return;
        }

        if ($origin) {
            if (in_array($origin, $allowedOrigins)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Methods: POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type');
            } else {
                // Origin present but not allowed
                http_response_code(403);
                echo "Forbidden: Origin not allowed";
                if ($logger) {
                    $logger->log('WARNING', sprintf('CORS failure for FORMID %s: Origin %s not allowed', $formId, $origin));
                }
                return;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // 3. Validate Method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        // 3.5 Altcha Validation
        if (($config['spam_protection'] ?? '') === 'altcha') {
            $payload = $_POST['altcha'] ?? '';
            /** @var \EICC\SendPoint\Service\AltchaService $altchaService */
            $altchaService = $this->container->get(\EICC\SendPoint\Service\AltchaService::class);

            if (empty($payload) || !$altchaService->verifySolution($payload)) {
                http_response_code(403); // Forbidden
                echo "Spam protection verification failed.";
                if ($logger) {
                    $logger->log('WARNING', sprintf('Altcha failure for FORMID %s from IP: %s', $formId, $remoteIp));
                }
                return;
            }
        }

        // 4. Handle Submission
        /** @var \EICC\SendPoint\Service\FormSubmissionHandler $handler */
        $handler = $this->container->get(\EICC\SendPoint\Service\FormSubmissionHandler::class);

        try {
            // Pass config to handler to avoid reloading
            $handler->handleWithConfig($formId, $config, $_POST, $remoteIp);
            echo "OK";
        } catch (ValidationException $e) {
            http_response_code(400);
            echo "Bad Request: " . $e->getMessage();
        } catch (Throwable $e) {
            http_response_code(500);
            echo "Internal Server Error";
        }
    }
}