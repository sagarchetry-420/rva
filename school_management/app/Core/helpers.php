<?php
/**
 * ============================================================
 * Global Helper Functions
 * ============================================================
 * Available everywhere after App::boot()
 */

// ─── Authentication Helpers ───

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function getUserType(): ?string {
    return $_SESSION['user_type'] ?? null;
}

function getUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function getUsername(): string {
    return $_SESSION['username'] ?? 'Guest';
}

function getUserEmail(): ?string {
    return $_SESSION['user_email'] ?? null;
}

// ─── Flash Messages ───

function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── URL & Path Helpers ───

function baseUrl(string $path = ''): string {
    return BASE_URL . ($path ? '/' . ltrim($path, '/') : '');
}

function asset(string $path): string {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

function redirect(string $url): void {
    // If URL starts with '?', it's a legacy query string, prepend BASE_URL/index.php
    if (strpos($url, '?') === 0) {
        $url = BASE_URL . '/index.php' . $url;
    } elseif (strpos($url, 'http') !== 0 && strpos($url, BASE_URL) !== 0) {
        $url = BASE_URL . '/' . ltrim($url, '/');
    }
    header('Location: ' . $url);
    exit;
}

function moduleUrl(string $module, string $action = 'index', array $params = []): string {
    $url = BASE_URL . "/{$module}/{$action}";
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

// ─── CSRF Protection ───

function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

function csrf_validate(string $token): bool {
    return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
}

// ─── Sanitization ───

function sanitizeInput(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sanitizeArray(array $data): array {
    return array_map(function($v) {
        return is_string($v) ? sanitizeInput($v) : $v;
    }, $data);
}

// ─── Grade Calculation ───

function calculateGrade(float $marks, float $maxMarks): string {
    if ($maxMarks <= 0) return 'N/A';
    $pct = ($marks / $maxMarks) * 100;
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B+';
    if ($pct >= 60) return 'B';
    if ($pct >= 50) return 'C';
    if ($pct >= 40) return 'D';
    return 'F';
}

// ─── Sidebar / Page Helpers ───

function getCurrentPage(): string {
    return $_GET['action'] ?? 'index';
}

function getCurrentModule(): string {
    return $_GET['module'] ?? '';
}

function isActivePage(string $module, string $action = ''): bool {
    $currentModule = getCurrentModule();
    $currentAction = getCurrentPage();
    if ($action) {
        return $currentModule === $module && $currentAction === $action;
    }
    return $currentModule === $module;
}

// ─── Date & Format Helpers ───

function formatDate(?string $date, string $format = 'M d, Y'): string {
    if (!$date) return '—';
    return date($format, strtotime($date));
}

function formatMoney(float $amount): string {
    return '₹' . number_format($amount, 2);
}

// ─── Old Input (for form repopulation after errors) ───

function old(string $key, $default = '') {
    return $_SESSION['_old_input'][$key] ?? $default;
}

function setOldInput(array $data): void {
    $_SESSION['_old_input'] = $data;
}

function clearOldInput(): void {
    unset($_SESSION['_old_input']);
}

// ─── Pagination Helper ───

function renderPagination(array $pagination, string $baseUrl = ''): string {
    if ($pagination['pages'] <= 1) {
        return '';
    }

    $currentUrl = $baseUrl ?: $_SERVER['REQUEST_URI'];
    // Parse URL to keep existing query params
    $parsedUrl = parse_url($currentUrl);
    $queryParams = [];
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $queryParams);
    }

    $html = '<div class="pagination" style="display:flex; justify-content:center; gap:5px; margin-top:20px;">';
    
    // Helper to generate page URL
    $getPageUrl = function($page) use ($parsedUrl, $queryParams) {
        $queryParams['page'] = $page;
        $query = http_build_query($queryParams);
        return ($parsedUrl['path'] ?? '') . '?' . $query;
    };

    $currentPage = $pagination['current_page'];
    $totalPages = $pagination['pages'];

    // Prev Button
    if ($currentPage > 1) {
        $html .= '<a href="' . htmlspecialchars($getPageUrl($currentPage - 1)) . '" class="btn btn-secondary btn-sm">&laquo; Prev</a>';
    } else {
        $html .= '<span class="btn btn-secondary btn-sm" style="opacity:0.5; pointer-events:none;">&laquo; Prev</span>';
    }

    // Page Numbers (simple logic: show all for now, or just some)
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<a href="' . htmlspecialchars($getPageUrl(1)) . '" class="btn btn-secondary btn-sm">1</a>';
        if ($start > 2) {
            $html .= '<span style="padding: 5px;">...</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="btn btn-primary btn-sm">' . $i . '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($getPageUrl($i)) . '" class="btn btn-secondary btn-sm">' . $i . '</a>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span style="padding: 5px;">...</span>';
        }
        $html .= '<a href="' . htmlspecialchars($getPageUrl($totalPages)) . '" class="btn btn-secondary btn-sm">' . $totalPages . '</a>';
    }

    // Next Button
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . htmlspecialchars($getPageUrl($currentPage + 1)) . '" class="btn btn-secondary btn-sm">Next &raquo;</a>';
    } else {
        $html .= '<span class="btn btn-secondary btn-sm" style="opacity:0.5; pointer-events:none;">Next &raquo;</span>';
    }

    $html .= '</div>';
    return $html;
}
