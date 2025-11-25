<?php

declare(strict_types=1);

use EICC\SendPoint\Controller\FormController;

/** @var \EICC\Utils\Container $container */
$container = require_once __DIR__ . '/../src/bootstrap.php';

// Handle Request
$controller = new FormController($container);
$controller->handleRequest();

