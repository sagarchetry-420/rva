<?php
/**
 * ============================================================
 * Controller — Base Controller Class
 * ============================================================
 * All module controllers extend this class. Provides view
 * rendering, flash messages, redirects, and JSON responses.
 */

class Controller
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Render a view file with data
     * 
     * @param string      $view   View path relative to app/ e.g. "Modules/Auth/Views/login"
     * @param array       $data   Data to extract into view scope
     * @param string|null $layout Layout to wrap the view in, e.g. "admin", "auth"
     */
    protected function render(string $view, array $data = [], ?string $layout = null): void
    {
        // Extract data variables into local scope
        extract($data);

        // Resolve view file path
        $viewFile = APP_ROOT . '/app/' . $view . '.php';

        if (!file_exists($viewFile)) {
            die("View not found: {$viewFile}");
        }

        if ($layout) {
            // Buffer the view content, then inject into layout
            ob_start();
            require $viewFile;
            $__content = ob_get_clean();

            $layoutFile = APP_ROOT . '/app/Views/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                die("Layout not found: {$layoutFile}");
            }
            require $layoutFile;
        } else {
            require $viewFile;
        }
    }

    /**
     * Send a JSON response (for AJAX endpoints)
     */
    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to a URL
     */
    protected function redirect(string $url): void
    {
        redirect($url);
    }

    /**
     * Redirect back to the referring page
     */
    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL;
        redirect($referer);
    }

    /**
     * Get POST data with optional sanitization
     */
    protected function input(string $key, $default = null)
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        if (is_string($value)) {
            return trim($value);
        }
        return $value;
    }

    /**
     * Get all POST data
     */
    protected function allInput(): array
    {
        return array_map(function ($val) {
            return is_string($val) ? trim($val) : $val;
        }, $_POST);
    }

    /**
     * Check if request is POST
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool
    {
        $token = $_POST['_csrf_token'] ?? '';
        if (!csrf_validate($token)) {
            setFlashMessage('error', 'Invalid security token. Please try again.');
            $this->back();
            exit;
        }
        return true;
    }

    /**
     * Set flash message
     */
    protected function flash(string $type, string $message): void
    {
        setFlashMessage($type, $message);
    }
}
