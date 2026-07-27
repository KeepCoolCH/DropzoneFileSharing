<?php
// Write config defaults if not existing
if (!defined('CONFIG_FILE')) {
    define('CONFIG_FILE', $incDir . '/config.php');
}

function saveConfigFile(string $path, array $data): void {
    $header = '';
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (preg_match('/^\s*class\s+Config\b/', $line)) break;
            $header .= $line . "\n";
        }
    } else {
        $header = "<?php\n\n// Dropzone File Sharing configuration (auto-created)\n\n";
    }

    $export = var_export($data, true);
    $export = preg_replace(['/^array\s*\(/', '/\)(\s*)$/'], ['[', ']$1'], $export);
    $exportIndented = preg_replace('/^/m', '  ', $export);

    $php = $header .
        "class Config {\n" .
        "    public static \$default = " . $exportIndented . ";\n" .
        "}\n";

    $tmp = $path . '.tmp';
    file_put_contents($tmp, $php);
    rename($tmp, $path);
}

if (class_exists('Config')) {
    $configData = Config::$default;

    $generalDefaults = [
        'lang_default' => 'de',
        'timezone'     => 'Europe/Zurich',
        'admin_email'  => 'you@example.com',
    ];

    $boolKeyDefaults = [
        'valid_once'    => true,
        'valid_1h'      => true,
        'valid_3h'      => true,
        'valid_6h'      => true,
        'valid_12h'     => true,
        'valid_1d'      => true,
        'valid_3d'      => true,
        'valid_7d'      => true,
        'valid_14d'     => true,
        'valid_30d'     => true,
        'valid_forever' => true,
        'only_upload'   => false,
        'user_upload'   => false,
        'send_email'    => false,
        'admin_notify'  => false,
        'show_dp'       => true,
        'pwzip'         => false,
    ];

    foreach ($generalDefaults as $key => $default) {
        if (!array_key_exists($key, $configData)) {
            $configData[$key] = $default;
        }
    }

    foreach ($boolKeyDefaults as $key => $default) {
        if (!array_key_exists($key, $configData)) {
            $configData[$key] = $default;
        }
    }

    $orderedKeys = array_merge(
        array_keys($generalDefaults),
        array_keys($boolKeyDefaults)
    );

    $orderedConfig = [];

    foreach ($orderedKeys as $key) {
        if (array_key_exists($key, $configData)) {
            $orderedConfig[$key] = $configData[$key];
            unset($configData[$key]);
        }
    }

    foreach ($configData as $key => $value) {
        $orderedConfig[$key] = $value;
    }

    if ($orderedConfig !== Config::$default) {
        Config::$default = $orderedConfig;
        saveConfigFile(CONFIG_FILE, $orderedConfig);
    }
}

// Clean up temp files and folder
function rrmdir($dir) {
    if (is_link($dir)) {
        unlink($dir);
        return;
    }
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    if ($items === false) return;
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_link($path)) unlink($path);
        elseif (is_dir($path)) rrmdir($path);
        else unlink($path);
    }
    rmdir($dir);
}

function dz_path_is_inside(string $path, string $base): bool {
    $base = rtrim(str_replace('\\', '/', $base), '/');
    $path = str_replace('\\', '/', $path);
    return $path === $base || str_starts_with($path, $base . '/');
}

function dz_uploads_file_path(string $relativePath): ?string {
    global $uploadDir;

    $relativePath = str_replace('\\', '/', ltrim($relativePath, "/\\"));
    if (
        $relativePath === ''
        || $relativePath !== basename($relativePath)
        || str_contains($relativePath, "\0")
    ) {
        return null;
    }

    $uploadsReal = realpath($uploadDir);
    $candidateReal = realpath(rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath);
    if ($uploadsReal === false || $candidateReal === false || !is_file($candidateReal)) {
        return null;
    }

    return dz_path_is_inside($candidateReal, $uploadsReal) ? $candidateReal : null;
}

function dz_csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function dz_csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(dz_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function dz_require_csrf(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        http_response_code(403);
        exit('Forbidden');
    }

    $token = (string)($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($token === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid CSRF token');
    }
}

function dz_header_filename(string $filename): string {
    return str_replace(['"', "\r", "\n"], ['\'', '', ''], basename($filename));
}

function dz_clean_line_value(string $value): string {
    return str_replace(["\r", "\n"], '', trim($value));
}

// Remove expired files
$cleanupLock = fopen($dataFile . '.lock', 'c');
if ($cleanupLock !== false && flock($cleanupLock, LOCK_EX)) {
    $currentData = json_decode((string)file_get_contents($dataFile), true);
    if (!is_array($currentData)) $currentData = [];

    foreach ($currentData as $token => $info) {
        $type = $info['type'] ?? 'once';
        $used = $info['used'] ?? false;
        $duration = $info['duration'] ?? 3600;
        $time = $info['time'] ?? 0;
        $storedPath = basename((string)($info['path'] ?? ''));
        $expired = ($type === 'once' && $used)
            || ($type === 'time' && $duration > 0 && $now - $time > $duration);

        if ($expired) {
            if ($storedPath !== '' && file_exists($uploadDir . '/' . $storedPath)) {
                unlink($uploadDir . '/' . $storedPath);
            }
            unset($currentData[$token]);
        }
    }

    file_put_contents(
        $dataFile,
        json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
    flock($cleanupLock, LOCK_UN);
    fclose($cleanupLock);
} elseif (is_resource($cleanupLock)) {
    fclose($cleanupLock);
}

// Clean up old chunk uploads
$maxAge = 24 * 3600; // 24 Hours
foreach (glob($chunksDir . '/*.{part,meta,current,lock}', GLOB_BRACE) as $file) {
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

// Read and Write json
function read_json(string $p, $def) {
    if (!file_exists($p)) return $def;
    $s = file_get_contents($p);
    $d = $s ? json_decode($s, true) : null;
    return is_array($d) ? $d : $def;
}

function write_json(string $p, $d): bool {
    $tmp = tempnam(dirname($p), basename($p) . '.tmp.');
    if ($tmp === false) return false;
    $json = json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($tmp, $json, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    if (!rename($tmp, $p)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function update_json_file(string $path, callable $callback): bool {
    $lockPath = $path . '.lock';
    $lock = fopen($lockPath, 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) fclose($lock);
        return false;
    }

    try {
        $data = read_json($path, []);
        $updated = $callback($data);
        if (is_array($updated)) {
            $data = $updated;
        }
        return write_json($path, $data);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
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

function ensureEnvKeyExists(string $envPath, string $key): void {
    $content = file_exists($envPath) ? (string)file_get_contents($envPath) : '';
    if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
        file_put_contents($envPath, rtrim($content, "\r\n") . "\n$key=" . bin2hex(random_bytes(32)) . "\n", LOCK_EX);
    }
}
ensureEnvKeyExists($envPath, 'DROPZONE_SECRET_KEY');

// Load .env file
function loadEnv(string $path): void {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = dz_clean_line_value($key);
        $value = dz_clean_line_value($value);
        putenv("$key=$value");
    }
}
loadEnv($envPath);

// Encryption for token links
function encrypt(string $data, string $key): string {
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($data, 'aes-256-gcm', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        return '';
    }
    return urlencode('gcm:' . base64_encode($iv . $tag . $ciphertext));
}
function decrypt(string $data, string $key): string|false {
    $decoded = urldecode($data);
    if (str_starts_with($decoded, 'gcm:')) {
        $raw = base64_decode(substr($decoded, 4), true);
        if ($raw === false || strlen($raw) < 29) {
            return false;
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        return openssl_decrypt($ciphertext, 'aes-256-gcm', hash('sha256', $key, true), OPENSSL_RAW_DATA, $iv, $tag);
    }
    return openssl_decrypt(base64_decode($decoded), 'AES-128-ECB', $key);
}

function dz_secret_key(): string {
    $key = getenv('DROPZONE_SECRET_KEY');
    return is_string($key) && $key !== '' ? $key : 'CHANGE-ME-DROPZONE-SECRET';
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

    $from = dz_clean_line_value($from);
    $smtpHost = dz_clean_line_value($smtpHost);
    $smtpUser = dz_clean_line_value($smtpUser);
    $smtpPass = dz_clean_line_value($smtpPass);
    if (!filter_var($from, FILTER_VALIDATE_EMAIL) || !preg_match('/^[A-Za-z0-9.-]+$/', $smtpHost)) {
        error_log("Invalid SMTP configuration.");
        return false;
    }

    // Hostname for EHLO and Message-ID
    $hostname = $_SERVER['HTTP_HOST']
        ?? $_SERVER['SERVER_NAME']
        ?? 'localhost';
    $hostname = dz_clean_line_value($hostname);
    if (!preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/', $hostname)) {
        $hostname = 'localhost';
    }

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
