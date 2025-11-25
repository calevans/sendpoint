<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use EICC\Autopen\Application;
use EICC\Utils\Container;

// Initialize the application
$container = new Container();
$app = Application::init($container);

// Get the database connection
$connection = $container->get('db');

// Check if system_prompt exists in the config table
$sql = 'SELECT * FROM config WHERE config_key = ?';
$stmt = $connection->prepare($sql);
$result = $stmt->executeQuery(['system_prompt']);
$config = $result->fetchAssociative();

if ($config === false) {
    echo "Adding system_prompt to config table...\n";
    
    // Insert default system prompt
    $defaultPrompt = 'You are a helpful assistant that generates executive orders based on user input.';
    
    $sql = 'INSERT INTO config (config_key, config_value, data_type) VALUES (?, ?, ?)';
    $stmt = $connection->prepare($sql);
    $stmt->executeStatement([
        'system_prompt',
        $defaultPrompt,
        'string'
    ]);
    
    echo "Added system_prompt with default value.\n";
} else {
    echo "system_prompt already exists in config table.\n";
    echo "Current value: " . $config['config_value'] . "\n";
}

echo "Done.\n";
