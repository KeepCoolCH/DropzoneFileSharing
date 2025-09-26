<?php
// Clean up temp files and folder
function rrmdir($dir) {
	foreach (scandir($dir) as $item) {
		if ($item === '.' || $item === '..') continue;
		$path = $dir . DIRECTORY_SEPARATOR . $item;
		if (is_dir($path)) rrmdir($path);
		else unlink($path);
	}
	rmdir($dir);
}

// Remove expired files
foreach ($fileData as $token => $info) {
    $type = $info['type'] ?? 'once'; // Standardwert fallback
	$used = $info['used'] ?? false;
	$duration = $info['duration'] ?? 3600;
	$time = $info['time'] ?? 0;

	$expired = ($type === 'once' && $used) || ($type === 'time' && $duration > 0 && $now - $time > $duration);
		if ($expired && file_exists($uploadDir . '/' . $info['path'])) {
			unlink($uploadDir . '/' . $info['path']);
			unset($fileData[$token]);
		}
	}
	file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));

// Clean up old chunk uploads
$maxAge = 24 * 3600; // 24 Hours
foreach (glob($chunksDir . '/*.{part,meta,current}', GLOB_BRACE) as $file) {
	if (is_file($file) && (time() - filemtime($file) > $maxAge)) {
		unlink($file);
	}
}

// Clean up old staging uploads
$maxAge = 24 * 3600; // 24 Hours
foreach (glob("$stagingRoot/*") as $stagingFolder) {
	if (is_dir($stagingFolder) && time() - filemtime($stagingFolder) > $maxAge) {
		rrmdir($stagingFolder);
	}
}

// Generate .htaccess for Upload-Folder (deny direct access)
$htaccessPathUpload = $uploadDir . '/.htaccess';
if (!file_exists($htaccessPathUpload)) {
    $htaccessContent = <<<HTACCESS
# Prevent direct access to files
Order deny,allow
Deny from all
HTACCESS;

    file_put_contents($htaccessPathUpload, $htaccessContent);
}

// Generate .htaccess for inc-Folder (deny direct access)
$htaccessPathInc = $incDir . '/.htaccess';
if (!file_exists($htaccessPathInc)) {
    $htaccessContent = <<<HTACCESS
# Prevent direct access to files
Order deny,allow
Deny from all
HTACCESS;

    file_put_contents($htaccessPathInc, $htaccessContent);
}

// Generate .htaccess for .env-Folder (deny direct access)
$htaccessPathEnv = $envDir . '/.htaccess';
if (!file_exists($htaccessPathEnv)) {
    $htaccessContent = <<<HTACCESS
# Prevent direct access to files
Order deny,allow
Deny from all
HTACCESS;

    file_put_contents($htaccessPathEnv, $htaccessContent);
}

// Automatically create the .env file (if it doesn't exist)
$envPath = $envDir . '/.env';

function ensureEnvFileExists(string $envPath): void {
    if (!file_exists($envPath)) {
        $envContent = <<<ENV
# SMTP Configuration -> Change settings directly in inc/.env-file
# SMPT Connection must be SSL Port 465
SMTP_HOST=mail.example.com
SMTP_PORT=465 
SMTP_USER=noreply@example.com
SMTP_PASS=changeme123!
ENV;
        file_put_contents($envPath, $envContent);
    }
}
ensureEnvFileExists($envPath);

// Load .env file
function loadEnv(string $path): void {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        putenv("$key=$value");
    }
}
loadEnv($envPath);

// Encryption for token links
function encrypt(string $data, string $key): string {
    return urlencode(base64_encode(openssl_encrypt($data, 'AES-128-ECB', $key)));
}
function decrypt(string $data, string $key): string|false {
    return openssl_decrypt(base64_decode(urldecode($data)), 'AES-128-ECB', $key);
}

// SMTP delivery via stream_socket_client
function sendSMTPMail(
    string|array $to,
    string $subject,
    string $message,
    string $from,
    string $smtpHost,
    int $smtpPort,
    string $smtpUser,
    string $smtpPass
): bool {
    $recipients = is_array($to) ? $to : preg_split('/[\s,;]+/', $to);
    $recipients = array_filter(array_map('trim', $recipients), fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

    if (empty($recipients)) {
        error_log("❌ No valid recipients.");
        return false;
    }

    foreach ($recipients as $recipient) {
        $socket = stream_socket_client("ssl://$smtpHost:$smtpPort", $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP connect error: $errstr ($errno)");
            return false;
        }

        $read = function () use ($socket) {
            $response = '';
            while ($line = fgets($socket, 1024)) {
                $response .= $line;
                if (preg_match('/^\d{3} /', $line)) break;
            }
            return $response;
        };

        $send = function ($cmd) use ($socket) {
            fwrite($socket, $cmd . "\r\n");
        };

        $read();
        $send("EHLO localhost"); $read();

        $send("AUTH LOGIN"); $read();

        $send(base64_encode($smtpUser)); $read();
        $send(base64_encode($smtpPass)); $read();

        $send("MAIL FROM:<$from>"); $read();
        $send("RCPT TO:<$recipient>"); $read();
        $send("DATA"); $read();

        $headers = "From: $from\r\nTo: $recipient\r\nSubject: $subject\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n";
        $send($headers . $message . "\r\n.");
        $read(); $send("QUIT"); fclose($socket);
    }

    return true;
}