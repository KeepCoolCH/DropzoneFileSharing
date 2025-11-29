<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

if (isset($_POST['lang'])) {
    $_GET['lang'] = preg_replace('/[^a-z]/i', '', $_POST['lang']);
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

    // Normalize path (keep structure, prevent traversal)
    $relativePath = str_replace('\\', '/', $rawName);
    $relativePath = preg_replace('#^(\.\.[/\\\\])+?#', '', $relativePath);
    $relativePath = preg_replace('#^/+?#', '', $relativePath);

    if ($relativePath === '') {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "OK 0";
        exit;
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
    $meta_write = function(string $path, string $n) {
        file_put_contents($path, preg_replace('/\D+/', '', $n) ?? '0', LOCK_EX);
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
        
        // --- Save key into fileData for cleanup ---
        $fileData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
        if (!isset($fileData[$uploadId])) {
            $fileData[$uploadId] = [
                'uploader_email'  => '',
                'recipient_email' => [],
                'keys'            => []
                ];
            }
            if (!isset($fileData[$uploadId]['keys']) || !is_array($fileData[$uploadId]['keys'])) {
                $fileData[$uploadId]['keys'] = [];
            }
            if (!in_array($key, $fileData[$uploadId]['keys'])) {
                $fileData[$uploadId]['keys'][] = $key;
                file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
            }

        if (!isset($_FILES['chunk'])) { echo "ERR no chunk field"; exit; }
        if ((int)$_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            echo "ERR upload error=".(int)$_FILES['chunk']['error']; exit;
        }

        $tmpUpload    = $_FILES['chunk']['tmp_name'];
        $chunkSizeInt = isset($_FILES['chunk']['size']) ? (int)$_FILES['chunk']['size'] : 0;

        // Check diskspace
        $free = disk_free_space($chunksDir);
        if ($free !== false && $free < ($chunkSizeInt + 5*1024*1024)) {
            echo "ERR disk full ($free bytes free)"; exit;
        }

        // Copy into own tmp
        $chunkCopy = rtrim($chunksDir, '/')."/$key.current";
        @unlink($chunkCopy);
        if (!move_uploaded_file($tmpUpload, $chunkCopy)) {
            if (!copy($tmpUpload, $chunkCopy)) { echo "ERR cannot move/copy upload to tmp"; exit; }
        }

        if ($chunkSizeInt === 0) {
            $fs = filesize($chunkCopy);
            if ($fs) $chunkSizeInt = (int)$fs;
            if ($chunkSizeInt === 0) { @unlink($chunkCopy); echo "ERR empty chunk"; exit; }
        }

        // Append to .part
        $out = fopen($partPath, file_exists($partPath) ? 'ab' : 'wb');
        if ($out === false) { @unlink($chunkCopy); echo "ERR cannot open part for write"; exit; }
        $in = fopen($chunkCopy, 'rb');
        if ($in === false) { fclose($out); @unlink($chunkCopy); echo "ERR cannot open chunkCopy"; exit; }

        if (stream_copy_to_stream($in, $out) === false) {
            fclose($in); fclose($out); @unlink($chunkCopy);
            echo "ERR write failed"; exit;
        }
        fflush($out);
        fclose($in);
        fclose($out);
        @unlink($chunkCopy);

        // Progress
        $received = $meta_read($metaPath);
        $received = $bi_add($received, (string)$chunkSizeInt);
        $meta_write($metaPath, $received);

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
        $canFinalize = ($totalSize !== '0' && $bi_cmp($received, $totalSize) >= 0);

        if (!$canFinalize && !file_exists($partPath)) {
            header('Content-Type: text/plain; charset=UTF-8');
            echo "STATUS $received";
            exit;
        }

        // Move to .staging
        $destFullPath = $stagingDir . '/' . $relativePath;
        $destDir = dirname($destFullPath);
        if (!is_dir($destDir)) mkdir($destDir, 0777, true);

        if (file_exists($destFullPath)) @unlink($destFullPath);
        if (!rename($partPath, $destFullPath)) {
            if (!copy($partPath, $destFullPath)) {
                header('Content-Type: text/plain; charset=UTF-8');
                echo "ERR finalize move failed"; exit;
            }
            @unlink($partPath);
        }
        @unlink($metaPath);

        // Mark file as complete
        $manifest = $stagingDir . '/.complete.json';
        $complete = file_exists($manifest) ? json_decode(file_get_contents($manifest), true) : [];
        if (!is_array($complete)) $complete = [];
        $complete[$relativePath] = true;
        file_put_contents($manifest, json_encode($complete));

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
        mkdir($tempDir, 0777, true);

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
                                rename($path, $dst);
                        }
                }

        @unlink($tempDir . '/.complete.json');

        $zipName = "$token.zip";
        $zipPath = "$uploadDir/$zipName";
        if (Config::$default['pwzip']):
        $pwzip = "$pw";
        else:
        $pwzip = "";
        endif;

        header('X-Accel-Buffering: no');
        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', '0');
        if (function_exists('apache_setenv')) {
                apache_setenv('no-gzip', '1');
        }
        while (ob_get_level() > 0) { ob_end_flush(); }
        ob_implicit_flush(true);

        $cmd = "cd " . escapeshellarg($tempDir) . " && zip -v -r -0 -ll " .
               ($pwzip !== '' ? "-P " . escapeshellarg($pwzip) . " " : "") .
               escapeshellarg($zipPath) . " . 2>&1";

        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];

        $proc = proc_open($cmd, $descriptorspec, $pipes);
        if (!is_resource($proc)) {
            echo "<!-- ERR cannot start zip -->\n"; flush();
            exit;
        }
        fclose($pipes[0]);

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $lastPing = time();
        echo "<!-- ZIP start: " . date('H:i:s') . " -->\n"; flush();

        while (true) {
            $out = fread($pipes[1], 8192);
            $err = fread($pipes[2], 8192);

            if ($out !== false && $out !== '') {
                echo "<!-- zip: " . htmlspecialchars(rtrim($out)) . " -->\n";
            }
            if ($err !== false && $err !== '') {
                echo "<!-- zip ERR: " . htmlspecialchars(rtrim($err)) . " -->\n";
            }

            if (time() - $lastPing >= 10) {
                echo "<!-- PING " . time() . " -->\n";
                $lastPing = time();
            }

            flush();

            $status = proc_get_status($proc);
            if (!$status['running']) {
                break;
            }
            usleep(150000); // 150 ms
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($proc);

        if ($exitCode !== 0) {
            echo "<!-- zip exit code $exitCode -->\n"; flush();
            exit;
        }

        echo "<!-- ZIP done: " . date('H:i:s') . " -->\n"; flush();

        $fileData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
        if (!is_array($fileData)) $fileData = [];
        $fileDataRaw = $fileData;

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
            'password' => $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null
        ];

        $basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        if ($basePath === '/' || $basePath === '\\') $basePath = '';
        $scheme = $_SERVER['REQUEST_SCHEME'] ?? '';
        if ($scheme !== 'http' && $scheme !== 'https') {
            $scheme = 'http';
        }
        $link = $scheme . '://' . $_SERVER['HTTP_HOST'] . $basePath . "/?lang=$lang&t=$token";
        
        $fileData[$token]['link'] = $link;

        $uploader  = $fileDataRaw[$uploadId]['uploader_email']  ?? '';
        $recipient = $fileDataRaw[$uploadId]['recipient_email'] ?? '';

        $fileData[$token]['uploader_email']  = $uploader;
        $fileData[$token]['recipient_email'] = $recipient;
        $fileData[$token]['verified']        = false;

        unset($fileData[$uploadId]);
        file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));

        if (Config::$default['send_email'] && $mailChoice === 'yes') {
            $encEmail  = encrypt($uploader, $secretKey);
            $encToken  = encrypt($token, $secretKey);
            $scheme = $_SERVER['REQUEST_SCHEME'] ?? '';
            if ($scheme !== 'http' && $scheme !== 'https') {
                $scheme = 'http';
            }
            $verifyUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . "$basePath/verify.php?lang=$lang&email=$encEmail&token=$encToken";

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

        exit;
    }

    // Errors for unknown actions
    header('Content-Type: text/plain; charset=UTF-8');
    echo "ERR unknown action";
    exit;
}
