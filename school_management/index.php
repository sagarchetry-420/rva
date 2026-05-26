<?php
/**
 * ============================================================
 * Front Controller — Application Entry Point
 * ============================================================
 * All requests are routed through this file.
 * URL format: index.php?module=auth&action=login
 */

// Bootstrap the application
require_once __DIR__ . '/app/Core/App.php';
App::boot();

// Load core classes
require_once APP_ROOT . '/app/Core/Database.php';
require_once APP_ROOT . '/app/Core/Router.php';
require_once APP_ROOT . '/app/Core/Controller.php';
require_once APP_ROOT . '/app/Core/Validator.php';
require_once APP_ROOT . '/app/Core/Middleware/AuthMiddleware.php';
require_once APP_ROOT . '/app/Core/Middleware/RoleMiddleware.php';

// Create router and register middleware
$router = new Router();
$router->addGlobalMiddleware('AuthMiddleware');
$router->addGlobalMiddleware('RoleMiddleware');

// Load route definitions
require_once APP_ROOT . '/config/routes.php';

// Dispatch the request
$router->dispatch();
