<?php
include 'db_connect.php';
// check_url.php
// Usage: php check_url.php
// Configure:
$url = $base_url.'url_check.php';             // <- URL to monitor
$method = 'GET';                           // 'HEAD' or 'GET' (use GET if server blocks HEAD)
$timeout = 8;                              // seconds for connect+response (tune as needed)
$retries = 2;                              // number of quick retries before declaring down
$stateFile = __DIR__ . '/check_url.state';// state file to avoid duplicate alerts
$lockFile = __DIR__ . '/check_url.lock';  // lock file to prevent parallel runs

// Telegram config:
$token = $botToken; // -> replace with your bot token
$chat_id = $report_chat_id;                               // -> replace with your chat id (group or user)

// ------------ helpers -------------
function sendTelegram($token, $chat_id, $text) {
    $text = trim($text);
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['resp' => $resp, 'err' => $err, 'info' => $info];
}

function urlIsUp($url, $method = 'GET', $timeout = 8) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_NOBODY, $method === 'HEAD');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(2, (int)$timeout/2));
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    // Optional: skip verifying cert if you have self-signed certs (not recommended)
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $errno = curl_errno($ch);
    $http_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($errno) {
        return ['up' => false, 'reason' => "cURL error {$errno}"];
    }

    // consider 200-399 as "up" (treat redirects OK)
    $up = ($http_code >= 200 && $http_code < 400);
    return ['up' => $up, 'http_code' => $http_code];
}

// ------------ lock (prevents concurrent runs) -------------
$lockFp = fopen($lockFile, 'c');
if (!$lockFp) {
    // can't open lock, proceed but warn
    error_log("Warning: cannot open lock file {$lockFile}");
} else {
    if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
        // another process is running
        // exit silently (or uncomment next line if you want logging)
        // error_log("Another check is running. Exiting.");
        exit;
    }
}

// ------------ main logic -------------
$downCount = 0;
$finalResult = null;
for ($i = 0; $i <= $retries; $i++) {
    $res = urlIsUp($url, $method, $timeout);
    if ($res['up']) {
        $finalResult = $res;
        break;
    } else {
        $downCount++;
        // small sleep between retries (only if we will retry)
        if ($i < $retries) usleep(300000); // 300ms
        $finalResult = $res;
    }
}

// read previous state
$prevState = 'unknown';
if (file_exists($stateFile)) {
    $prevState = trim(@file_get_contents($stateFile));
}

// determine current state
$currentState = ($finalResult['up'] ?? false) ? 'up' : 'down';

// if state changed, send telegram
if ($currentState !== $prevState) {
    if ($currentState === 'down') {
        $msg = "<b>ALERT:</b> {$url} is DOWN.\n";
        if (isset($finalResult['http_code'])) {
            $msg .= "HTTP code: " . ($finalResult['http_code'] ?: 'N/A') . "\n";
        }
        if (isset($finalResult['reason'])) {
            $msg .= "Reason: " . $finalResult['reason'] . "\n";
        }
        $msg .= "Checked at: " . date('Y-m-d H:i:s');
        sendTelegram($token, $chat_id, $msg);
    } else {
        $msg = "<b>RECOVERY:</b> {$url} is UP again.\nChecked at: " . date('Y-m-d H:i:s');
        sendTelegram($token, $chat_id, $msg);
    }
    // write new state
    @file_put_contents($stateFile, $currentState, LOCK_EX);
}

// If you want, you can log a local message every run (optional):
$logLine = date('Y-m-d H:i:s') . " - {$url} - state={$currentState}";
if (isset($finalResult['http_code'])) $logLine .= " http={$finalResult['http_code']}";
if (isset($finalResult['reason'])) $logLine .= " reason={$finalResult['reason']}";
$logLine .= PHP_EOL;
@file_put_contents(__DIR__ . '/check_url.log', $logLine, FILE_APPEND | LOCK_EX);

// release lock
if ($lockFp) {
    flock($lockFp, LOCK_UN);
    fclose($lockFp);
}
