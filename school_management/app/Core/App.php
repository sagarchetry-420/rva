<?php
/**
 * ============================================================
 * App Bootstrap — Core Application Initializer
 * ============================================================
 * Loads environment, registers autoloader, starts session,
 * and defines global constants.
 */

class App
{
    private static bool $initialized = false;

    /**
     * Bootstrap the application
     */
    public static function boot(): void
    {
        if (self::$initialized) return;
        self::$initialized = true;

        // Define root path
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', dirname(__DIR__, 2));
        }

        // Load environment variables
        self::loadEnv();

        // Define app constants from .env
        self::defineConstants();

        // Set timezone
        date_default_timezone_set('Asia/Kolkata');

        // Register PSR-4 style autoloader
        spl_autoload_register([self::class, 'autoload']);

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Load global helpers
        require_once APP_ROOT . '/app/Core/helpers.php';

        // Set error handling based on environment
        if (self::env('APP_ENV') === 'development') {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }
    }

    /**
     * Load .env file into environment
     */
    private static function loadEnv(): void
    {
        $envPath = APP_ROOT . '/.env';
        if (!file_exists($envPath)) return;

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) continue;

            $name = trim($parts[0]);
            $value = trim($parts[1]);

            // Remove surrounding quotes
            if (preg_match('/^"(.*)"$/', $value, $m)) $value = $m[1];
            if (preg_match("/^'(.*)'$/", $value, $m)) $value = $m[1];

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("{$name}={$value}");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Define application constants from environment
     */
    private static function defineConstants(): void
    {
        if (!defined('DB_HOST'))    define('DB_HOST',    self::env('DB_HOST', 'localhost'));
        if (!defined('DB_USER'))    define('DB_USER',    self::env('DB_USER', 'root'));
        if (!defined('DB_PASS'))    define('DB_PASS',    self::env('DB_PASS', ''));
        if (!defined('DB_NAME'))    define('DB_NAME',    self::env('DB_NAME', 'RVA'));
        if (!defined('DB_CHARSET')) define('DB_CHARSET', self::env('DB_CHARSET', 'utf8mb4'));
        if (!defined('APP_NAME'))   define('APP_NAME',   self::env('APP_NAME', 'Rose Valley Academy'));
        if (!defined('APP_VERSION'))define('APP_VERSION', self::env('APP_VERSION', '2.0.0'));
        if (!defined('BASE_URL'))   define('BASE_URL',   self::env('BASE_URL', '/rva/school_management'));
    }

    /**
     * PSR-4 style autoloader
     * Maps: App\Core\Database      → app/Core/Database.php
     *       App\Modules\Auth\...   → app/Modules/Auth/...
     */
    public static function autoload(string $class): void
    {
        // Only handle our App namespace
        $prefix = 'App\\';
        if (strpos($class, $prefix) !== 0) return;

        // Remove prefix and convert namespace separators to directory separators
        $relativeClass = substr($class, strlen($prefix));
        $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }

    /**
     * Get environment variable with fallback
     */
    public static function env(string $key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return $value;
    }
}
