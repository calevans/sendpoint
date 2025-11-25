<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EICC\Utils\Container;
use EICC\Utils\Log;
use Symfony\Component\Dotenv\Dotenv;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use EICC\SendPoint\Service\FormConfigService;
use EICC\SendPoint\Service\FormValidatorService;
use EICC\SendPoint\Service\FormSubmissionHandler;
use EICC\SendPoint\Service\EmailService;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

// Initialize Container
$container = new Container();

// Register Logger
$container->stuff('logger', function() {
    $logFile = $_ENV['LOG_FILE_PATH'] ?? __DIR__ . '/../var/log/app.log';
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    return new Log('SendPoint', $logFile, 'INFO');
});

// Register Twig
$container->stuff('twig', function() {
    $loader = new FilesystemLoader(__DIR__ . '/../templates');
    return new Environment($loader, [
        'cache' => __DIR__ . '/../var/cache/twig',
        'auto_reload' => true, // Useful for development
    ]);
});

// Register FormConfigService
$container->stuff(FormConfigService::class, function() {
    return new FormConfigService(__DIR__ . '/../templates');
});

// Register FormValidatorService
$container->stuff(FormValidatorService::class, function() {
    return new FormValidatorService();
});

// Register FormSubmissionHandler
$container->stuff(\EICC\SendPoint\Service\FormSubmissionHandler::class, function() use ($container) {
    return new \EICC\SendPoint\Service\FormSubmissionHandler($container);
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
