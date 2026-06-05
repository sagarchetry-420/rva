<?php
require_once __DIR__ . '/../includes/auth.php';
checkAuth();
require_once __DIR__ . '/../components/header.php';

$ttsLogPath = __DIR__ . '/../../rossie/tts_error.log';
$geminiLogPath = __DIR__ . '/../../rossie/gemini_error_log.txt';

$ttsLogs = file_exists($ttsLogPath) ? htmlspecialchars(file_get_contents($ttsLogPath)) : 'No TTS error logs found.';
$geminiLogs = file_exists($geminiLogPath) ? htmlspecialchars(file_get_contents($geminiLogPath)) : 'No Gemini error logs found.';

?>

<header class="topbar">
    <h1>Rossie AI Logs</h1>
</header>

<div class="logs-container" style="display: flex; flex-direction: column; gap: 20px;">
    
    <div class="log-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #800000; font-size: 1.2rem; margin-bottom: 15px;">ElevenLabs TTS Errors</h2>
        <pre style="background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; font-family: monospace; min-height: 100px;"><?= $ttsLogs ?></pre>
    </div>

    <div class="log-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #800000; font-size: 1.2rem; margin-bottom: 15px;">Gemini API Errors</h2>
        <pre style="background: #1e1e1e; color: #00ff00; padding: 15px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; font-family: monospace; min-height: 100px;"><?= $geminiLogs ?></pre>
    </div>

</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
