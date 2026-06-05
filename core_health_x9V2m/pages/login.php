<?php
require_once __DIR__ . '/../includes/auth.php';

$localEnvPath = __DIR__ . '/../.env';

// Anti-bruteforce rate limiting
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitFile = __DIR__ . '/../login_attempts.json';
$attempts = file_exists($rateLimitFile) ? json_decode(file_get_contents($rateLimitFile), true) : [];

// Clean old attempts (older than 15 minutes)
$now = time();
foreach ($attempts as $ipKey => $times) {
    $attempts[$ipKey] = array_filter($times, function($t) use ($now) {
        return ($now - $t) < 900; // 15 mins
    });
    if (empty($attempts[$ipKey])) unset($attempts[$ipKey]);
}

$currentAttempts = count($attempts[$ip] ?? []);
$isBlocked = $currentAttempts >= 5;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($isBlocked) {
        $error = "Too many failed attempts. Please try again in 15 minutes.";
    } else {
        // SETUP MODE
        if (!isSuperAdminSetup() && isset($_POST['setup_user']) && isset($_POST['setup_pass'])) {
            $newUser = trim($_POST['setup_user']);
            $newPass = $_POST['setup_pass'];
            
            if (strlen($newPass) < 8) {
                $error = "Password must be at least 8 characters.";
            } else {
                $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $envContent = file_exists($localEnvPath) ? file_get_contents($localEnvPath) : '';
                $envContent .= "\n# Super Admin Credentials\nSUPER_ADMIN_USER=\"{$newUser}\"\nSUPER_ADMIN_PASS_HASH=\"{$hash}\"\n";
                file_put_contents($localEnvPath, trim($envContent) . "\n");
                $success = "Setup complete! Please login.";
                // Refresh env
                $_ENV['SUPER_ADMIN_USER'] = $newUser;
                $_ENV['SUPER_ADMIN_PASS_HASH'] = $hash;
            }
        } 
        // LOGIN MODE
        elseif (isset($_POST['username']) && isset($_POST['password'])) {
            $user = trim($_POST['username']);
            $pass = $_POST['password'];
            
            $envUser = $_ENV['SUPER_ADMIN_USER'] ?? '';
            $envHash = $_ENV['SUPER_ADMIN_PASS_HASH'] ?? '';
            
            if ($user === $envUser && password_verify($pass, $envHash)) {
                // Success
                session_regenerate_id(true); // Prevent session fixation
                $_SESSION['super_admin_logged_in'] = true;
                // Clear attempts
                unset($attempts[$ip]);
                file_put_contents($rateLimitFile, json_encode($attempts));
                header('Location: index.php');
                exit;
            } else {
                $error = "Invalid credentials.";
                $attempts[$ip][] = $now;
                file_put_contents($rateLimitFile, json_encode($attempts));
                $currentAttempts++;
                if ($currentAttempts >= 5) $isBlocked = true;
            }
        }
    }
}
file_put_contents($rateLimitFile, json_encode($attempts));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin | RVA</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: #1e293b; padding: 40px; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); width: 100%; max-width: 400px; text-align: center; }
        h2 { margin-top: 0; font-weight: 600; color: #38bdf8; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; font-size: 0.9rem; color: #94a3b8; }
        input { width: 100%; padding: 12px; box-sizing: border-box; background: #0f172a; border: 1px solid #334155; color: white; border-radius: 6px; outline: none; transition: border 0.3s; }
        input:focus { border-color: #38bdf8; }
        button { width: 100%; padding: 12px; background: #0ea5e9; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #0284c7; }
        .error { color: #ef4444; margin-bottom: 20px; font-size: 0.9rem; }
        .success { color: #10b981; margin-bottom: 20px; font-size: 0.9rem; }
        .logo { width: 60px; height: 60px; background: #0ea5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">⚡</div>
        <h2>System Health Monitor</h2>
        
        <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        
        <?php if (!isSuperAdminSetup()): ?>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-bottom: 20px;">Initial Setup: Create your Super Admin account.</p>
            <form method="POST">
                <div class="form-group">
                    <label>Master Username</label>
                    <input type="text" name="setup_user" required>
                </div>
                <div class="form-group">
                    <label>Master Password (min 8 chars)</label>
                    <input type="password" name="setup_pass" required>
                </div>
                <button type="submit">Complete Setup</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required <?= $isBlocked ? 'disabled' : '' ?>>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required <?= $isBlocked ? 'disabled' : '' ?>>
                </div>
                <button type="submit" <?= $isBlocked ? 'disabled' : '' ?>><?= $isBlocked ? 'Blocked temporarily' : 'Access System' ?></button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
