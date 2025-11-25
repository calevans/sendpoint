<?php
require __DIR__ . '/src/bootstrap.php';

use EICC\SendPoint\Service\RateLimitService;

$service = $container->get(RateLimitService::class);
$ref = new ReflectionClass($service);
$method = $ref->getMethod('cleanup');
$method->setAccessible(true);
$method->invoke($service);
echo "Cleanup called.\n";
