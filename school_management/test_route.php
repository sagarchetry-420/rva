<?php
require_once __DIR__ . '/app/Core/Router.php';
$router = new Router();
require_once __DIR__ . '/config/routes.php';

$reflection = new ReflectionClass($router);
$property = $reflection->getProperty('routes');
$property->setAccessible(true);
$routes = $property->getValue($router);

foreach ($routes as $r) {
    echo $r['path'] . "\n";
}
