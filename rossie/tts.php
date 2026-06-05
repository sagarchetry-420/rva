<?php
/**
 * Rossie TTS API (ElevenLabs Integration)
 * Securely receives text from the frontend, queries ElevenLabs using the secret API key,
 * and streams the MP3 audio back to the user.
 */

header('Content-Type: audio/mpeg');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

// Safely parse the environment variables
function parseEnv($filePath) {
    if (!file_exists($filePath)) return false;
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, '"\'');
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

$envLoaded = parseEnv(__DIR__ . '/../school_management/.env');
if (!$envLoaded) {
    header('HTTP/1.1 500 Internal Server Error');
    file_put_contents(__DIR__ . '/tts_error.log', date('Y-m-d H:i:s') . " - Could not load .env file.\n", FILE_APPEND);
    exit;
}

$apiKey = $_ENV['ELEVENLABS_API_KEY'] ?? '';
$voiceId = $_ENV['ELEVENLABS_VOICE_ID'] ?? '21m00Tcm4TlvDq8ikWAM'; // Default to Rachel
$modelId = $_ENV['ELEVENLABS_MODEL_ID'] ?? 'eleven_turbo_v2_5';

if (empty($apiKey) || $apiKey === 'YOUR_ELEVENLABS_API_KEY') {
    header('HTTP/1.1 500 Internal Server Error');
    file_put_contents(__DIR__ . '/tts_error.log', date('Y-m-d H:i:s') . " - API Key not configured. Current key: $apiKey\n", FILE_APPEND);
    echo "API Key not configured.";
    exit;
}

// Get the text payload
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$text = $data['text'] ?? '';

if (empty($text)) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

// Clean the text before sending to ElevenLabs to save characters
$cleanText = preg_replace('/<[^>]*>?/m', '', $text);
$cleanText = preg_replace('/[\*\#\_]/', '', $cleanText);
// Strip common emojis safely
$cleanText = preg_replace('/[\x{1F300}-\x{1F5FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $cleanText);
$cleanText = trim($cleanText ?? '');

if (empty($cleanText)) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

// Check Cache
$cacheDir = __DIR__ . '/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$cacheKey = md5($voiceId . $cleanText);
$cacheFile = $cacheDir . '/' . $cacheKey . '.mp3';

if (file_exists($cacheFile)) {
    header('X-Cache: HIT');
    readfile($cacheFile);
    exit;
}
header('X-Cache: MISS');

// Stream the audio from ElevenLabs with latency optimization
$url = "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}/stream?optimize_streaming_latency=3";

$payload = json_encode([
    'text' => $cleanText,
    'model_id' => $modelId,
    'voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.75
    ]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
// Fix for local WAMP environments lacking proper SSL certificates
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: audio/mpeg',
    'Content-Type: application/json',
    'xi-api-key: ' . $apiKey
]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$audioData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch) || $httpCode !== 200) {
    header('HTTP/1.1 500 Internal Server Error');
    $errorMsg = "ElevenLabs Error (HTTP $httpCode): " . curl_error($ch) . "\nResponse: " . $audioData;
    file_put_contents(__DIR__ . '/tts_error.log', date('Y-m-d H:i:s') . ' - ' . $errorMsg . "\n", FILE_APPEND);
    curl_close($ch);
    exit;
}

curl_close($ch);

// Save to cache
file_put_contents($cacheFile, $audioData);

echo $audioData;
