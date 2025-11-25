<?php

declare(strict_types=1);

$appRoot = realpath(__DIR__ . '/..');

require_once $appRoot . '/vendor/autoload.php';

use EICC\Utils\Container;
use EICC\Utils\Log;
use Symfony\Component\Dotenv\Dotenv;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use EICC\SendPoint\Service\FormConfigService;
use EICC\SendPoint\Service\FormValidatorService;
use EICC\SendPoint\Service\FormSubmissionHandler;
use EICC\SendPoint\Service\EmailService;
use EICC\SendPoint\Service\RateLimitService;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load($appRoot . '/.env');

// Initialize Container
$container = new Container();

// Register Logger
$container->stuff('logger', function() use ($appRoot) {
    $envPath = $_ENV['LOG_FILE_PATH'] ?? 'logs/sendpoint.log';
    $logFile = ($envPath[0] === '/') ? $envPath : ($appRoot . '/' . $envPath);

    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    return new Log('SendPoint', $logFile, 'INFO');
});

// Register Twig
$container->stuff('twig', function() use ($appRoot) {
    $loader = new FilesystemLoader($appRoot . '/templates');
    return new Environment($loader, [
        'cache' => $appRoot . '/var/cache/twig',
        'auto_reload' => true, // Useful for development
    ]);
});

// Register FormConfigService
$container->stuff(FormConfigService::class, function() use ($appRoot) {
    return new FormConfigService($appRoot . '/templates');
});

// Register FormValidatorService
$container->stuff(FormValidatorService::class, function() {
    $defaultMaxLength = (int) ($_ENV['DEFAULT_MAX_FIELD_LENGTH'] ?? 2048);
    return new FormValidatorService($defaultMaxLength);
});

// Register FormSubmissionHandler
$container->stuff(\EICC\SendPoint\Service\FormSubmissionHandler::class, function() use ($container) {
    return new \EICC\SendPoint\Service\FormSubmissionHandler($container);
});

// Register RateLimitService
$container->stuff(\EICC\SendPoint\Service\RateLimitService::class, function() use ($appRoot) {
    $envDir = $_ENV['RATE_LIMIT_STORAGE_DIR'] ?? null;
    if ($envDir) {
        // If it's an absolute path, use it. If relative, prepend appRoot.
        $storageDir = ($envDir[0] === '/') ? $envDir : ($appRoot . '/' . $envDir);
    } else {
        $storageDir = $appRoot . '/var/cache/rate_limit';
    }

    $limitSeconds = (int) ($_ENV['RATE_LIMIT_SECONDS'] ?? 600);
    return new \EICC\SendPoint\Service\RateLimitService($storageDir, $limitSeconds);
});

// Register AltchaService
$container->stuff(\EICC\SendPoint\Service\AltchaService::class, function() {
    $key = $_ENV['ALTCHA_HMAC_KEY'] ?? '';
    if (empty($key)) {
        throw new \RuntimeException('ALTCHA_HMAC_KEY is not set in .env');
    }
    return new \EICC\SendPoint\Service\AltchaService($key);
});

// Register EmailService
$container->add(EmailService::class, function() {
    $dsn = sprintf(
        'smtp://%s:%s@%s:%s?verify_peer=0',
        urlencode($_ENV['SMTP_USER'] ?? ''),
        urlencode($_ENV['SMTP_PASS'] ?? ''),
        $_ENV['SMTP_HOST'] ?? 'localhost',
        $_ENV['SMTP_PORT'] ?? '587'
    );

    return new EmailService(
        $dsn,
        $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@example.com',
        $_ENV['SMTP_FROM_NAME'] ?? 'SendPoint'
    );
});

return $container;
