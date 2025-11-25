<?php

declare(strict_types=1);

namespace EICC\SendPoint\Controller;

use EICC\Utils\Container;
use EICC\SendPoint\Exception\ValidationException;
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

        // 2. Load Config (Early for CORS)
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

        // 3. CORS Handling (Per-Form)
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

        // 4. Validate Method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method Not Allowed";
            return;
        }

        // 5. Handle Submission
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