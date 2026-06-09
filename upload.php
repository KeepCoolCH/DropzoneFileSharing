<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('DropzoneUserSession');
    session_start();
}

if (isset($_POST['lang'])) {
    $_GET['lang'] = preg_replace('/[^a-z]/i', '', $_POST['lang']);
}

function dz_get_base_url(): string
{
    $isHttps =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    $scheme = $isHttps ? 'https' : 'http';

    $host = $_SERVER['HTTP_X_FORWARDED_HOST']
        ?? $_SERVER['HTTP_HOST']
        ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');

    $host = preg_replace('/\s.*/', '', $host);
    if (!preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/', $host)) {
        $host = 'localhost';
    }

    $forwardedPrefix = $_SERVER['HTTP_X_FORWARDED_PREFIX'] ?? '';

    if ($forwardedPrefix !== '') {
        $basePath = rtrim($forwardedPrefix, '/');
    } else {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath   = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($basePath === '/') {
            $basePath = '';
        }
    }

    return $scheme . '://' . $host . $basePath;
}

$userChoice = $_POST['mailChoice'] ?? 'no';

loadEnv($envDir . '/.env');

$smtpHost = getenv('SMTP_HOST');
$smtpPort = getenv('SMTP_PORT');
$smtpUser = getenv('SMTP_USER');
$smtpPass = getenv('SMTP_PASS');

$smtpFromAddr = getenv('SMTP_FROM_ADDRESS') ?: $smtpUser;

$from = $smtpFromAddr;
$secretKey = 'YOUR_SECRET_KEY'; // Set your secret key for encryption here (Must be identical to verify.php)

// Resumable Chunk Upload Logic (status | append | finalize)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // Basepaths
    $action      = $_POST['action'];
    $uploadId    = $_POST['uploadId']    ?? '';
    $rawName     = $_POST['relativePath'] ?? ($_POST['name'] ?? '');
    $totalSizeIn = $_POST['totalSize']   ?? '0';
    $totalFiles  = isset($_POST['totalFiles']) ? (int)$_POST['totalFiles'] : 1;
    $pw          = trim($_POST['pw']  ?? '');
    $mode        = $_POST['mode']     ?? 'once';
    $mailChoice  = $_POST['mailChoice'] ?? 'no';

    if (!preg_match('/^[a-f0-9]{16}$/', $uploadId)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('ERR invalid uploadId');
    }
    if ($totalFiles < 1 || $totalFiles > 10000) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('ERR invalid file count');
    }
    if (!preg_match('/^\d+$/', (string)$totalSizeIn)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('ERR invalid file size');
    }
    if (!in_array($mode, ['once','1h','3h','6h','12h','1d','3d','7d','14d','30d','forever'], true)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('ERR invalid upload mode');
    }

    // Normalize path, preserve directory structure and reject traversal/control bytes.
    $relativePath = str_replace('\\', '/', $rawName);
    $relativePath = ltrim($relativePath, '/');
    $pathParts = explode('/', $relativePath);
    $validPath = $relativePath !== ''
        && strlen($relativePath) <= 4096
        && preg_match('//u', $relativePath)
        && !preg_match('/[\x00-\x1F\x7F]/', $relativePath);
    foreach ($pathParts as $part) {
        if ($part === '' || $part === '.' || $part === '..') {
            $validPath = false;
            break;
        }
    }

    if (!$validPath) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('ERR invalid relative path');
    }

    // Big-Int-Helpers
    $bi_norm = function(string $n): string {
        $n = trim($n);
        $n = preg_replace('/\D+/', '', $n) ?? '0';
        $n = ltrim($n, '0');
        return $n === '' ? '0' : $n;
    };
    $bi_add = function(string $a, string $b) use ($bi_norm): string {
        $a = $bi_norm($a); $b = $bi_norm($b);
        $i = strlen($a)-1; $j = strlen($b)-1; $carry = 0; $out = '';
        while ($i >= 0 || $j >= 0 || $carry) {
            $da = $i >= 0 ? ord($a[$i]) - 48 : 0;
            $db = $j >= 0 ? ord($b[$j]) - 48 : 0;
            $s = $da + $db + $carry;
            $out .= chr(($s % 10) + 48);
            $carry = intdiv($s, 10);
            $i--; $j--;
        }
        return strrev($out);
    };
    $bi_cmp = function(string $a, string $b) use ($bi_norm): int {
        $a = $bi_norm($a); $b = $bi_norm($b);
        $la = strlen($a); $lb = strlen($b);
        if ($la !== $lb) return $la < $lb ? -1 : 1;
        $c = strcmp($a, $b);
        return $c < 0 ? -1 : ($c > 0 ? 1 : 0);
    };

    // Keys & paths
    $key      = md5($uploadId . '|' . $relativePath);
    $partPath = rtrim($chunksDir, '/')."/$key.part";
    $metaPath = rtrim($chunksDir, '/')."/$key.meta";
    $lockPath = rtrim($chunksDir, '/')."/$key.lock";

    $stagingRoot = rtrim($uploadDir, '/').'/.staging';
    $stagingDir  = $stagingRoot . '/' . ($uploadId !== '' ? $uploadId : 'default');

    if (!is_dir($chunksDir))  mkdir($chunksDir, 0777, true);
    if (!is_dir($uploadDir))  mkdir($uploadDir, 0777, true);
    if (!is_dir($stagingDir)) mkdir($stagingDir, 0777, true);

    $meta_read = function(string $path) {
        if (!file_exists($path)) return '0';
        $v = file_get_contents($path);
        if ($v === false) return '0';
        $v = preg_replace('/\D+/', '', $v) ?? '0';
        $v = ltrim($v, '0');
        return $v === '' ? '0' : $v;
    };
    $meta_write = function(string $path, string $n): bool {
        return file_put_contents($path, preg_replace('/\D+/', '', $n) ?? '0', LOCK_EX) !== false;
    };

    // ---- STATUS ----
    if ($action === 'status') {
        header('Content-Type: text/plain; charset=UTF-8');
        $received = $meta_read($metaPath);
        echo "STATUS $received";
        exit;
    }

    // ---- APPEND ----
    if ($action === 'append') {
        header('Content-Type: text/plain; charset=UTF-8');
        
        $metadataSaved = update_json_file($dataFile, function (array $fileData) use ($uploadId, $key): array {
            if (!isset($fileData[$uploadId]) || !is_array($fileData[$uploadId])) {
                $fileData[$uploadId] = [
                    'uploader_email'  => '',
                    'recipient_email' => [],
                    'keys'            => []
                ];
            }
            if (!isset($fileData[$uploadId]['keys']) || !is_array($fileData[$uploadId]['keys'])) {
                $fileData[$uploadId]['keys'] = [];
            }
            if (!in_array($key, $fileData[$uploadId]['keys'], true)) {
                $fileData[$uploadId]['keys'][] = $key;
            }
            return $fileData;
        });
        if (!$metadataSaved) {
            http_response_code(500);
            exit('ERR cannot save upload metadata');
        }

        if (!isset($_FILES['chunk'])) { echo "ERR no chunk field"; exit; }
        if ((int)$_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            echo "ERR upload error=".(int)$_FILES['chunk']['error']; exit;
        }

        $tmpUpload    = $_FILES['chunk']['tmp_name'];
        $chunkSizeInt = isset($_FILES['chunk']['size']) ? (int)$_FILES['chunk']['size'] : 0;
        $expectedOffset = $bi_norm((string)($_POST['offset'] ?? '0'));

        // Check diskspace
        $free = disk_free_space($chunksDir);
        if ($free !== false && $free < ($chunkSizeInt + 5*1024*1024)) {
            echo "ERR disk full ($free bytes free)"; exit;
        }

        // Copy into own tmp
        $chunkCopy = rtrim($chunksDir, '/') . '/' . $key . '-' . bin2hex(random_bytes(6)) . '.current';
        if (!move_uploaded_file($tmpUpload, $chunkCopy)) {
            if (!copy($tmpUpload, $chunkCopy)) { echo "ERR cannot move/copy upload to tmp"; exit; }
        }

        if ($chunkSizeInt === 0) {
            $fs = filesize($chunkCopy);
            if ($fs) $chunkSizeInt = (int)$fs;
            if ($chunkSizeInt === 0) { @unlink($chunkCopy); echo "ERR empty chunk"; exit; }
        }

        $lock = fopen($lockPath, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            @unlink($chunkCopy);
            if (is_resource($lock)) fclose($lock);
            http_response_code(500);
            exit('ERR cannot lock upload');
        }

        $received = $meta_read($metaPath);
        if ($bi_cmp($received, $expectedOffset) !== 0) {
            flock($lock, LOCK_UN);
            fclose($lock);
            @unlink($chunkCopy);
            echo "OK $received";
            exit;
        }

        // Append to .part
        $out = fopen($partPath, file_exists($partPath) ? 'ab' : 'wb');
        if ($out === false) {
            flock($lock, LOCK_UN); fclose($lock); @unlink($chunkCopy);
            http_response_code(500); exit('ERR cannot open part for write');
        }
        $in = fopen($chunkCopy, 'rb');
        if ($in === false) {
            fclose($out); flock($lock, LOCK_UN); fclose($lock); @unlink($chunkCopy);
            http_response_code(500); exit('ERR cannot open chunkCopy');
        }

        if (stream_copy_to_stream($in, $out) === false) {
            fclose($in); fclose($out); @unlink($chunkCopy);
            flock($lock, LOCK_UN); fclose($lock);
            http_response_code(500); exit('ERR write failed');
        }
        fflush($out);
        fclose($in);
        fclose($out);
        @unlink($chunkCopy);

        // Progress
        $received = $bi_add($received, (string)$chunkSizeInt);
        if (!$meta_write($metaPath, $received)) {
            flock($lock, LOCK_UN);
            fclose($lock);
            http_response_code(500);
            exit('ERR cannot update upload progress');
        }
        flock($lock, LOCK_UN);
        fclose($lock);

        echo "OK $received";
        exit;
    }

    // ---- FINALIZE ----
    if ($action === 'finalize') {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        $onlyUpload = Config::$default['only_upload'] ?? false;
        if ($onlyUpload) {
            header('Content-Type: text/plain; charset=UTF-8');
        } else {
            header('Content-Type: text/html; charset=UTF-8');
        }
        
        $received  = $meta_read($metaPath);
        $totalSize = $bi_norm((string)$totalSizeIn);
        $canFinalize = $bi_cmp($received, $totalSize) === 0;

        if (!$canFinalize) {
            http_response_code(409);
            header('Content-Type: text/plain; charset=UTF-8');
            exit("ERR incomplete upload: received $received of $totalSize bytes");
        }

        // Move to .staging
        $destFullPath = $stagingDir . '/' . $relativePath;
        $destDir = dirname($destFullPath);
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);

        if (file_exists($destFullPath) && !unlink($destFullPath)) {
            http_response_code(500);
            exit('ERR cannot replace staged file');
        }
        if ($totalSize === '0') {
            if (file_put_contents($destFullPath, '') === false) {
                http_response_code(500);
                exit('ERR cannot create empty file');
            }
        } elseif (!rename($partPath, $destFullPath)) {
            if (!copy($partPath, $destFullPath)) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=UTF-8');
                echo "ERR finalize move failed"; exit;
            }
            @unlink($partPath);
        }
        @unlink($metaPath);
        @unlink($lockPath);

        // Mark file as complete
        $manifest = $stagingDir . '/.complete.json';
        $complete = file_exists($manifest) ? json_decode(file_get_contents($manifest), true) : [];
        if (!is_array($complete)) $complete = [];
        $complete[$relativePath] = true;
        if (file_put_contents($manifest, json_encode($complete, JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
            http_response_code(500);
            exit('ERR cannot update completion manifest');
        }

        // All complete
        $completedCount = count($complete);
        if ($completedCount < $totalFiles) {
            header('Content-Type: text/plain; charset=UTF-8');
            echo "STATUS $received";
            exit;
        }

        // ZIP/Link/E-Mail-Flow

        $token   = bin2hex(random_bytes(8));
        $tempDir = $uploadDir . '/' . $token;
        if (!mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            http_response_code(500);
            exit('ERR cannot create temporary directory');
        }
        $failFinalize = function (int $status, string $message) use ($tempDir, $uploadDir, $token): void {
            if (is_dir($tempDir)) rrmdir($tempDir);
            @unlink($uploadDir . '/' . $token . '.zip');
            http_response_code($status);
            header('Content-Type: text/plain; charset=UTF-8');
            exit('ERR ' . $message);
        };

        // Move staging → tempDir (without .complete.json)
        $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($stagingDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $path => $info) {
                $basename = basename($path);
                if ($basename === '.complete.json') {
                        @unlink($path);
                        continue;
                }
                $rel = substr($path, strlen($stagingDir) + 1);
                $dst = $tempDir . '/' . $rel;
                if ($info->isDir()) {
                        if (!is_dir($dst)) mkdir($dst, 0777, true);
                } else {
                        $dstDir = dirname($dst);
                        if (!is_dir($dstDir)) mkdir($dstDir, 0777, true);
                        if (!rename($path, $dst)) {
                            $failFinalize(500, 'cannot move staged file');
                        }
                }
        }

        @unlink($tempDir . '/.complete.json');

        $zipName = "$token.zip";
        $zipPath = "$uploadDir/$zipName";
        $zipInputSize = 0;
        $sizeIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($sizeIterator as $item) {
            if ($item->isFile()) {
                $zipInputSize += $item->getSize();
            }
        }
        $freeSpace = disk_free_space($uploadDir);
        if ($freeSpace !== false && $freeSpace < ($zipInputSize + 10 * 1024 * 1024)) {
            $failFinalize(507, 'insufficient disk space for zip');
        }
        if (Config::$default['pwzip']):
        $pwzip = "$pw";
        else:
        $pwzip = "";
        endif;

        $cmd = "cd " . escapeshellarg($tempDir) . " && zip -q -r -0 " .
               ($pwzip !== '' ? "-P " . escapeshellarg($pwzip) . " " : "") .
               escapeshellarg($zipPath) . " . 2>&1";

        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        if (!function_exists('proc_open')) {
            $failFinalize(500, 'zip process support is unavailable');
        }
        $proc = proc_open($cmd, $descriptorspec, $pipes);
        if (!is_resource($proc)) {
            $failFinalize(500, 'cannot start zip');
        }
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $processExitCode = null;
        while (true) {
            $out = fread($pipes[1], 8192);
            $err = fread($pipes[2], 8192);

            if ($out !== false && $out !== '') {
                error_log('zip: ' . rtrim($out));
            }
            if ($err !== false && $err !== '') {
                error_log('zip error: ' . rtrim($err));
            }

            $status = proc_get_status($proc);
            if (!$status['running']) {
                $processExitCode = $status['exitcode'];
                break;
            }
            usleep(150000); // 150 ms
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $closeExitCode = proc_close($proc);
        $exitCode = $processExitCode !== null && $processExitCode >= 0
            ? $processExitCode
            : $closeExitCode;

        if ($exitCode !== 0) {
            $failFinalize(500, "zip failed with exit code $exitCode");
        }

        clearstatcache(true, $zipPath);
        if (!is_file($zipPath) || !is_readable($zipPath)) {
            $failFinalize(500, 'zip file was not created or is not readable');
        }
        $zipSize = filesize($zipPath);
        if ($zipSize === false || $zipSize <= 0) {
            $failFinalize(500, 'zip file is empty');
        }

        $fileData = read_json($dataFile, []);
        $fileDataRaw = $fileData;

        $upload_user = null;
        if (!empty(Config::$default['user_upload']) && !empty($_SESSION['logged_in']) && !empty($_SESSION['user'])) {
            $upload_user = $_SESSION['user'];
        }

        $type = in_array($mode, ['1h','3h','6h','12h','1d','3d','7d','14d','30d','forever']) ? 'time' : 'once';
        $duration = match($mode) {
            '1h' => 3600,
            '3h' => 3 * 3600,
            '6h' => 6 * 3600,
            '12h' => 12 * 3600,
            '1d' => 1 * 86400,
            '3d' => 3 * 86400,
            '7d' => 7 * 86400,
            '14d' => 14 * 86400,
            '30d' => 30 * 86400,
            'forever' => 0,
            default => 86400,
        };

        $fileData[$token] = [
            'name' => $zipName,
            'path' => $zipName,
            'mode'  => $mode,
            'time' => time(),
            'type' => $type,
            'duration' => $duration,
            'used' => false,
            'password' => $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null,
        ];

        $baseUrl = dz_get_base_url();
        $link    = $baseUrl . '/?lang=' . rawurlencode($lang) . '&t=' . rawurlencode($token);
        
        $fileData[$token]['link'] = $link;

        $uploader  = $fileDataRaw[$uploadId]['uploader_email']  ?? '';
        $recipient = $fileDataRaw[$uploadId]['recipient_email'] ?? '';

        $fileData[$token]['uploader_email']  = $uploader;
        $fileData[$token]['recipient_email'] = $recipient;
        $fileData[$token]['verified']        = false;
        $fileData[$token]['upload_user']     = $upload_user;

        $metadataSaved = update_json_file($dataFile, function (array $current) use ($uploadId, $token, $fileData): array {
            $current[$token] = $fileData[$token];
            unset($current[$uploadId]);
            return $current;
        });
        if (!$metadataSaved) {
            http_response_code(500);
            exit('ERR cannot save completed upload metadata');
        }

        if (Config::$default['send_email'] && $mailChoice === 'yes') {
            $encEmail  = encrypt($uploader, $secretKey);
            $encToken  = encrypt($token, $secretKey);

            $verifyUrl = $baseUrl . "/verify.php?lang=$lang&email=$encEmail&token=$encToken";

            $subject = "{$t['title']} - {$t['sent_title_uploader']}";
            $message = "<html><body>
                {$t['sent_message_uploader']}
                {$t['title']}
                <p><a href='$verifyUrl'>$verifyUrl</a></p>
                </body></html>";

            sendSMTPMail($uploader, $subject, $message, $from, $smtpHost, (int)$smtpPort, $smtpUser, $smtpPass);
        }

        if (!empty(Config::$default['admin_notify'])) {
            $adminMail = Config::$default['admin_email'] ?? '';

            if ($adminMail !== '') {
                $subjectAdmin = "{$t['title']} - {$t['sent_title_admin']}";
                $messageAdmin = "<html><body>
                    <h3>{$t['sent_title_admin']}</h3>
                    <p><strong>{$t['token']}:</strong> $token</p>
                    <p><strong>{$t['file']}:</strong> $zipName</p>
                    <p><strong>{$t['uploader']}:</strong> " . htmlspecialchars($uploader) . "</p>
                    <p><strong>{$t['recipient']}:</strong> " . htmlspecialchars(is_array($recipient) ? implode(', ', $recipient) : $recipient) . "</p>
                    <p>{$t['sent_message_admin']}</p>
                </body></html>";

                sendSMTPMail($adminMail, $subjectAdmin, $messageAdmin, $from, $smtpHost, (int)$smtpPort, $smtpUser, $smtpPass);
            }
        }

        // Confirmation link
        if (!Config::$default['only_upload']) {
            if (!Config::$default['send_email']) {
                echo $t['your_link'] . " 
                <a id='link' href='$link' target='_blank'>$link</a><br><br>
                <button onclick='copyLink()'>{$t['copy']}</button>
                <span id='copied' style='display:none;'><br><br>{$t['copied']}</span><br><br><br>";
            } else {
                echo ($mailChoice === 'yes')
                    ? $t['email_sent'] . "<br><br><br>"
                    : $t['your_link'] . " 
                        <a id='link' href='$link' target='_blank'>$link</a><br><br>
                        <button onclick='copyLink()'>{$t['copy']}</button>
                        <span id='copied' style='display:none;'><br><br>{$t['copied']}</span><br><br><br>";
            }
        } else {
            echo "COMPLETE";
        }

        // Cleanup
        @rrmdir($stagingDir);
        @rrmdir($tempDir);
        @unlink($metaPath);
        @unlink($partPath);
        @unlink($lockPath);
        foreach (glob(rtrim($chunksDir, '/') . '/' . $key . '-*.current') ?: [] as $currentPath) {
            @unlink($currentPath);
        }

        exit;
    }

    // Errors for unknown actions
    header('Content-Type: text/plain; charset=UTF-8');
    echo "ERR unknown action";
    exit;
}
