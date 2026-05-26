<?php
/**
 * ============================================================
 * Router — Simple Query-String Based Request Dispatcher
 * ============================================================
 * Routes requests based on ?module=xxx&action=yyy to the
 * appropriate Controller method with middleware support.
 * 
 * URL format: index.php?module=auth&action=login
 */

class Router
{
    private array $routes = [];
    private array $globalMiddleware = [];

    /**
     * Register a route
     * 
     * @param string $method   HTTP method (GET, POST, or ANY)
     * @param string $path     Route path e.g. "auth/login"
     * @param string $handler  Controller class name
     * @param string $action   Method name on the controller
     * @param array  $options  ['role' => 'admin', 'middleware' => [...]]
     */
    public function add(string $method, string $path, string $handler, string $action, array $options = []): self
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'path'       => $path,
            'handler'    => $handler,
            'action'     => $action,
            'role'       => $options['role'] ?? null,
            'middleware'  => $options['middleware'] ?? [],
        ];
        return $this;
    }

    /**
     * Register a GET route
     */
    public function get(string $path, string $handler, string $action, array $options = []): self
    {
        return $this->add('GET', $path, $handler, $action, $options);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, string $handler, string $action, array $options = []): self
    {
        return $this->add('POST', $path, $handler, $action, $options);
    }

    /**
     * Register a route for any HTTP method
     */
    public function any(string $path, string $handler, string $action, array $options = []): self
    {
        return $this->add('ANY', $path, $handler, $action, $options);
    }

    /**
     * Add global middleware (applied to all routes)
     */
    public function addGlobalMiddleware(string $middlewareClass): self
    {
        $this->globalMiddleware[] = $middlewareClass;
        return $this;
    }

    /**
     * Dispatch the current request to the matching route
     */
    public function dispatch(): void
    {
        $module = $_GET['module'] ?? '';
        $action = $_GET['action'] ?? 'index';
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $path = $module . '/' . $action;

        // Find matching route
        $route = $this->findRoute($path, $requestMethod);

        if (!$route) {
            // If no module specified, redirect to login or dashboard
            if (empty($module)) {
                $this->handleDefaultRoute();
                return;
            }
            $this->handleNotFound($path);
            return;
        }

        // Run global middleware
        foreach ($this->globalMiddleware as $mw) {
            $this->runMiddleware($mw, $route);
        }

        // Run route-specific middleware
        foreach ($route['middleware'] as $mw) {
            $this->runMiddleware($mw, $route);
        }

        // Check role if required
        if ($route['role']) {
            $this->checkRole($route['role']);
        }

        // Instantiate controller and call action
        $controllerClass = $route['handler'];

        if (!class_exists($controllerClass)) {
            $this->handleNotFound("Controller not found: {$controllerClass}");
            return;
        }

        $controller = new $controllerClass();
        $actionMethod = $route['action'];

        if (!method_exists($controller, $actionMethod)) {
            $this->handleNotFound("Action '{$actionMethod}' not found on " . get_class($controller));
            return;
        }

        $controller->$actionMethod();
    }

    /**
     * Find a matching route for the given path and method
     */
    private function findRoute(string $path, string $method): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['path'] !== $path) continue;
            if ($route['method'] === 'ANY' || $route['method'] === $method) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Handle default route (no module specified)
     */
    private function handleDefaultRoute(): void
    {
        if (!isLoggedIn()) {
            redirect(moduleUrl('auth', 'login'));
            return;
        }

        $role = getUserType();
        switch ($role) {
            case 'admin':
                redirect(moduleUrl('admin', 'dashboard'));
                break;
            case 'teacher':
                redirect(moduleUrl('teacher', 'dashboard'));
                break;
            case 'student':
                redirect(moduleUrl('student', 'dashboard'));
                break;
            default:
                redirect(moduleUrl('auth', 'login'));
        }
    }

    /**
     * Run a middleware class
     */
    private function runMiddleware(string $middlewareClass, array $route): void
    {
        if (!class_exists($middlewareClass)) return;
        $middleware = new $middlewareClass();
        if (method_exists($middleware, 'handle')) {
            $middleware->handle($route);
        }
    }

    /**
     * Check if current user has the required role
     */
    private function checkRole(string $requiredRole): void
    {
        if (!isLoggedIn()) {
            setFlashMessage('error', 'Please log in to continue.');
            redirect(moduleUrl('auth', 'login'));
            exit;
        }

        $userRole = getUserType();
        if ($userRole !== $requiredRole) {
            setFlashMessage('error', 'You do not have permission to access this page.');
            redirect(moduleUrl('auth', 'login'));
            exit;
        }
    }

    /**
     * Handle 404 - Route not found
     */
    private function handleNotFound(string $details = ''): void
    {
        http_response_code(404);
        if (App::env('APP_ENV') === 'development') {
            die("404 Not Found: {$details}");
        }
        setFlashMessage('error', 'Page not found.');
        redirect(moduleUrl('auth', 'login'));
    }
}
