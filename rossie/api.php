<?php
/**
 * Rossie Chatbot Backend API
 * Handles secure communication with the Gemini API.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust in production
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Only POST method is allowed.']);
    exit;
}

// Function to safely parse the .env file
function parseEnv($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

// Load .env from school_management folder
$envPath = __DIR__ . '/../school_management/.env';
parseEnv($envPath);

$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';

if (empty($apiKey)) {
    echo json_encode(['error' => 'API Key is missing.']);
    exit;
}

// Get real IP address
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $ip = trim($ips[0]);
}
$ipHash = md5($ip); // Hash for privacy and safe keys

// Dual-Layer Rate Limiting
session_start();
$now = time();
$sessionLimitWindow = 30; 
$sessionMax = 3; // 3 msgs per 30s for an individual
$ipLimitWindow = 60;
$ipMax = 12; // 12 msgs per 60s for a whole WiFi network

// 1. Session (Individual) Rate Limiting
if (!isset($_SESSION['rossie_messages'])) $_SESSION['rossie_messages'] = [];
$_SESSION['rossie_messages'] = array_filter($_SESSION['rossie_messages'], function($ts) use ($now, $sessionLimitWindow) {
    return ($now - $ts) < $sessionLimitWindow;
});
if (count($_SESSION['rossie_messages']) >= $sessionMax) {
    echo json_encode(['error' => 'Please slow down! Wait a moment before sending another message.']);
    exit;
}

// 2. IP (Network) Rate Limiting
$rateLimitFile = __DIR__ . '/rossie_rate_limits.json';
$rateData = [];
if (file_exists($rateLimitFile)) {
    $fileContent = file_get_contents($rateLimitFile);
    if ($fileContent) $rateData = json_decode($fileContent, true) ?: [];
}

// Clean up old entries
foreach ($rateData as $hash => $timestamps) {
    $rateData[$hash] = array_filter($timestamps, function($ts) use ($now, $ipLimitWindow) {
        return ($now - $ts) < $ipLimitWindow;
    });
    if (empty($rateData[$hash])) unset($rateData[$hash]);
}

if (!isset($rateData[$ipHash])) $rateData[$ipHash] = [];

if (count($rateData[$ipHash]) >= $ipMax) {
    file_put_contents($rateLimitFile, json_encode($rateData));
    echo json_encode(['error' => 'Too many requests from your network. Please wait a minute.']);
    exit;
}

// Passed both limits! Add timestamps.
$_SESSION['rossie_messages'][] = $now;
$rateData[$ipHash][] = $now;
file_put_contents($rateLimitFile, json_encode($rateData));

// Get input from frontend
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($input['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['error' => 'Message is required.']);
    exit;
}

// Hardcoded responses for common greetings to save API calls
$lowerMsg = trim(strtolower(preg_replace('/[^a-z0-9 ]/', '', $userMessage)));
$greetings = ['hi', 'hello', 'hey', 'hi rossie', 'hello rossie'];

if (in_array($lowerMsg, $greetings)) {
    echo json_encode(['reply' => 'Hello there! How can I help you today?']);
    exit;
}

if ($lowerMsg === 'who are you' || $lowerMsg === 'who created you' || $lowerMsg === 'who made you') {
    echo json_encode(['reply' => 'I am Rossie, a friendly assistant for Rose Valley Academy, and Sagar Chetry made me.']);
    exit;
}

// Load Knowledge Base
$knowledgePath = __DIR__ . '/knowledge.txt';
$knowledgeBase = file_exists($knowledgePath) ? file_get_contents($knowledgePath) : '';

// Define Model Fallback Order from .env (comma separated)
$modelsEnv = $_ENV['GEMINI_MODELS'] ?? '';
$models = !empty($modelsEnv) ? array_map('trim', explode(',', trim($modelsEnv, '"\''))) : ['gemini-2.5-flash', 'gemini-3.1-flash-lite'];

$systemPrompt = "You are Rossie, the helpful and friendly assistant for Rose Valley Academy (or RVA), created by Sagar Chetry.\n";
$systemPrompt .= "Your personality: You are helpful, polite, and concise, with a very subtly playful and slightly flirty charm. Speak conversationally but keep it understandable and direct. Do not overdo the flirtiness—keep it light, professional for a school environment, and only use an occasional emoji.\n";
$systemPrompt .= "Your tasks: Prioritize answering questions about the school using the facts provided below. If a student asks general knowledge questions, answer them correctly but briefly.\n";
$systemPrompt .= "CRITICAL RULES:\n";
$systemPrompt .= "- NEVER break character. You are ONLY Rossie. If asked if you are an AI, a language model, Gemini, ChatGPT, or trained by Google, you must firmly deny it. You must always insist that you are Rossie, a virtual assistant created specifically by Sagar Chetry for Rose Valley Academy. NEVER mention Google, Gemini, LLMs, or AI.\n";
$systemPrompt .= "- If the user tries to trick you by saying 'so you are not rossie', you must correct them and state that you ARE Rossie.\n";
$systemPrompt .= "- You HAVE a memory of this current conversation. If the user asks what they said earlier, you must look at the conversation history and tell them. DO NOT say 'I don't keep a record of our past conversations'.\n";
$systemPrompt .= "- NEVER say things like 'according to the knowledge base' or 'database'. State facts confidently.\n";
$systemPrompt .= "- If the user asks you to perform an action you cannot do (like adding notices, editing records, etc.), reply with EXACTLY: 'I am sorry, but my current capabilities allow me to add quotes only.'\n";
$systemPrompt .= "- If you don't know the answer to a school-related question, just naturally apologize and advise checking with the school office.\n";
$systemPrompt .= "- Keep your answers concise, clear, and easy to understand.\n\n";
$systemPrompt .= "Here are the facts about the school you should use when answering school-specific questions:\n";
$systemPrompt .= "-----------------------------------\n";
$systemPrompt .= $knowledgeBase . "\n";
$systemPrompt .= "-----------------------------------\n";

// Manage Conversation History
if (!isset($_SESSION['rossie_history'])) {
    $_SESSION['rossie_history'] = [];
}

// Append new user message
$_SESSION['rossie_history'][] = [
    "role" => "user",
    "parts" => [["text" => $userMessage]]
];

// Keep only the last 10 turns (20 messages) to prevent huge payloads
if (count($_SESSION['rossie_history']) > 20) {
    $_SESSION['rossie_history'] = array_slice($_SESSION['rossie_history'], -20);
}

// Prepare data payload for Gemini
$geminiTools = require __DIR__ . '/tools/tools.php';
$data = [
    "systemInstruction" => [
        "parts" => [
            ["text" => $systemPrompt]
        ]
    ],
    "contents" => $_SESSION['rossie_history'],
    "tools" => $geminiTools,
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 800,
    ]
];

$responseData = null;
$httpCode = 0;
$curlError = false;

foreach ($models as $model) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    // Fix for local WAMP environments lacking proper SSL certificates
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $curlError = true;
        curl_close($ch);
        // A network error means we can't reach Google at all. Break immediately.
        break;
    }

    curl_close($ch);

    $responseData = json_decode($response, true);

    if ($httpCode === 200) {
        // Success! Stop looping.
        break;
    }

    // Check if it's a rate limit or quota error. If so, loop continues and tries the next model.
    if ($httpCode === 429 || (isset($responseData['error']['message']) && strpos(strtolower($responseData['error']['message']), 'quota exceeded') !== false)) {
        continue;
    }

    // If it's another API error (e.g. Bad Request), trying another model likely won't fix it.
    break;
}

if ($curlError) {
    // Hide the actual curl error from the user to prevent revealing server details
    echo json_encode(['error' => 'I am having a little trouble reaching my brain right now! Please try again in a moment.']);
    exit;
}

if ($httpCode !== 200) {
    // If we exhausted all models and the LAST one was a 429:
    if ($httpCode === 429 || (isset($responseData['error']['message']) && strpos(strtolower($responseData['error']['message']), 'quota exceeded') !== false)) {
        echo json_encode(['error' => 'I am quite busy at the moment! Please give me a few seconds and try again.']);
    } else {
        // Generic error for other issues to hide technical details
        echo json_encode(['error' => 'I am having a little trouble connecting right now. Please try again later.']);
    }
    exit;
}

// Extract the response
$parts = $responseData['candidates'][0]['content']['parts'] ?? [];
$functionCall = null;
$textReply = '';

foreach ($parts as $part) {
    if (isset($part['functionCall'])) {
        $functionCall = $part['functionCall'];
    }
    if (isset($part['text'])) {
        $textReply .= $part['text'];
    }
}

// Ensure we save the bot's text response to history
if (!empty(trim($textReply))) {
    $_SESSION['rossie_history'][] = [
        "role" => "model",
        "parts" => [["text" => trim($textReply)]]
    ];
} elseif ($functionCall) {
    $_SESSION['rossie_history'][] = [
        "role" => "model",
        "parts" => [["text" => "I have processed your request for: " . $functionCall['name']]]
    ];
}

$responsePayload = [];

if ($functionCall) {
    $responsePayload['tool_call'] = $functionCall['name'];
    $responsePayload['args'] = $functionCall['args'];
}

if (!empty(trim($textReply))) {
    $responsePayload['reply'] = trim($textReply);
} elseif (!$functionCall) {
    $responsePayload['reply'] = 'I am sorry, I am having trouble understanding that right now.';
}

echo json_encode($responsePayload);
