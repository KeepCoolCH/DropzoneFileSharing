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
    $type = $info['type'] ?? 'once'; // fallback
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
# Prevent direct access to internal files
# Works for Apache 2.2 and Apache 2.4+

<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
  Order deny,allow
  Deny from all
</IfModule>

# Optional: disable directory listing just in case
Options -Indexes
HTACCESS;

    file_put_contents($htaccessPathUpload, $htaccessContent);
}

// Generate .htaccess for inc-Folder (deny direct access)
$htaccessPathInc = $incDir . '/.htaccess';
if (!file_exists($htaccessPathInc)) {
    $htaccessContent = <<<HTACCESS
# Prevent direct access to internal files
# Works for Apache 2.2 and Apache 2.4+

<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
  Order deny,allow
  Deny from all
</IfModule>

# Optional: disable directory listing just in case
Options -Indexes
HTACCESS;

    file_put_contents($htaccessPathInc, $htaccessContent);
}

// Generate .htaccess for .env-Folder (deny direct access)
$htaccessPathEnv = $envDir . '/.htaccess';
if (!file_exists($htaccessPathEnv)) {
    $htaccessContent = <<<HTACCESS
# Prevent direct access to internal files
# Works for Apache 2.2 and Apache 2.4+

<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
  Order deny,allow
  Deny from all
</IfModule>

# Optional: disable directory listing just in case
Options -Indexes
HTACCESS;

    file_put_contents($htaccessPathEnv, $htaccessContent);
}

// Automatically create the .env file (if it doesn't exist)
$envPath = $envDir . '/.env';

function ensureEnvFileExists(string $envPath): void {
    if (!file_exists($envPath)) {
        $envContent = <<<ENV
# SMTP Configuration
SMTP_HOST=mail.example.com
SMTP_PORT=465 
SMTP_USER=noreply@example.com
SMTP_PASS=changeme123!
SMTP_FROM_ADDRESS=noreply@example.com
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
// Helper function: encode Subject according to RFC 2047 if needed
function encodeSubject(string $subject): string
{
    // If only ASCII (32–126), return as-is
    if (!preg_match('/[^\x20-\x7E]/', $subject)) {
        return $subject;
    }
    // UTF-8 Base64 encoded subject
    return '=?UTF-8?B?' . base64_encode($subject) . '?=';
}

// SMTP delivery via stream_socket_client (improved version)
function sendSMTPMail(
    string|array $to,
    string $subject,
    string $messageHtml,
    string $from,
    string $smtpHost,
    int    $smtpPort,
    string $smtpUser,
    string $smtpPass
): bool {
    $recipients = is_array($to) ? $to : preg_split('/[\s,;]+/', $to);
    $recipients = array_filter(
        array_map('trim', $recipients),
        fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)
    );

    if (empty($recipients)) {
        error_log("No valid recipients.");
        return false;
    }

    // Hostname for EHLO and Message-ID
    $hostname = $_SERVER['HTTP_HOST']
        ?? $_SERVER['SERVER_NAME']
        ?? 'localhost';

    // Date & Message-ID
    $dateHeader = date(DATE_RFC2822);
    $msgId      = sprintf('<%s@%s>', bin2hex(random_bytes(16)), $hostname);

    // Encode subject if needed
    $encodedSubject = encodeSubject($subject);

    // Multipart/alternative boundary
    $boundary = '=_DZFS_' . bin2hex(random_bytes(16));
    $eol = "\r\n";

    // Derive a plain-text version from HTML (simple but sufficient)
    $messageText = html_entity_decode(
        trim(strip_tags($messageHtml)),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    foreach ($recipients as $recipient) {
        // SSL: usually port 465. If you use 587/STARTTLS, this needs adjustment.
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

        $send = function (string $cmd) use ($socket, $eol) {
            fwrite($socket, $cmd . $eol);
        };

        // Greeting / handshake
        $read();
        $send("EHLO " . $hostname); $read();

        $send("AUTH LOGIN"); $read();
        $send(base64_encode($smtpUser)); $read();
        $send(base64_encode($smtpPass)); $read();

        $send("MAIL FROM:<$from>"); $read();
        $send("RCPT TO:<$recipient>"); $read();
        $send("DATA"); $read();

        // RFC-compliant headers
        $headers = [
            "From: $from",
            "To: $recipient",
            "Subject: $encodedSubject",
            "Date: $dateHeader",
            "Message-ID: $msgId",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"$boundary\""
        ];

        // Build body: text + HTML
        $body  = "--$boundary$eol";
        $body .= "Content-Type: text/plain; charset=UTF-8$eol";
        $body .= "Content-Transfer-Encoding: 8bit$eol$eol";
        $body .= $messageText . "$eol$eol";

        $body .= "--$boundary$eol";
        $body .= "Content-Type: text/html; charset=UTF-8$eol";
        $body .= "Content-Transfer-Encoding: 8bit$eol$eol";
        $body .= $messageHtml . "$eol$eol";

        $body .= "--$boundary--$eol";

        // Send headers + body to the SMTP server
        $data = implode($eol, $headers) . $eol . $eol . $body . $eol . '.';

        $send($data);
        $read();

        $send("QUIT");
        fclose($socket);
    }

    return true;
}
