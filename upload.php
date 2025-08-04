<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

loadEnv($envDir . '/.env');

$smtpHost = getenv('SMTP_HOST');
$smtpPort = getenv('SMTP_PORT');
$smtpUser = getenv('SMTP_USER');
$smtpPass = getenv('SMTP_PASS');
$from = $smtpUser;
$secretKey = 'YOUR_SECRET_KEY'; // Set your secret key for encryption here (Must be identical to verify.php)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['chunk'])) {
    $uploadId = $_POST['uploadId'] ?? '';
    $chunkIndex = $_POST['chunkIndex'] ?? '';
    $filename = $_POST['name'] ?? '';
    $isLastFile = $_POST['isLastFile'] ?? '';

    $chunkBase = "$chunksDir/$uploadId/" . sha1($filename);
    if (!is_dir($chunkBase)) mkdir($chunkBase, 0777, true);
    $chunkPath = "$chunkBase/chunk_$chunkIndex";

    if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath)) {
        http_response_code(500);
        die($t['upload_error']);
    }

    file_put_contents("$chunkBase/info.txt", $filename);

    if ($isLastFile === '1') {
        // Rebuild files from chunks
        $allComplete = true;
        $fileChunks = [];

        foreach (glob("$chunksDir/$uploadId/*", GLOB_ONLYDIR) as $fileDir) {
            $infoPath = "$fileDir/info.txt";
            $filename = file_exists($infoPath) ? file_get_contents($infoPath) : null;
            if (!$filename) continue;

            $chunks = glob("$fileDir/chunk_*");
            sort($chunks);

            if (count($chunks) === 0) {
                $allComplete = false;
                break;
            }

            $fileChunks[] = ['name' => $filename, 'chunks' => $chunks, 'dir' => $fileDir];
        }

        if ($allComplete) {
            $token = bin2hex(random_bytes(8));
            $fileDataRaw = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
			$uploader = $fileDataRaw[$uploadId]['uploader_email'] ?? '';
			$recipient = $fileDataRaw[$uploadId]['recipient_email'] ?? '';
            $tempDir = "$uploadDir/$token";
            mkdir($tempDir, 0777, true);
			
			$lastFileDir = end($fileChunks)['dir'] ?? '';
			
            foreach ($fileChunks as $item) {
                $relativePath = preg_replace('/[^\w\-\.\/ äöüÄÖÜß]/u', '_', $item['name']);
                $fullPath = "$tempDir/$relativePath";
                $dir = dirname($fullPath);
                if (!is_dir($dir)) mkdir($dir, 0777, true);

                $fp = fopen($fullPath, 'wb');
                foreach ($item['chunks'] as $chunk) {
                    fwrite($fp, file_get_contents($chunk));
                }
                fclose($fp);
            }

            // Create ZIP
            $zipName = "$token.zip";
            $zipPath = "$uploadDir/$zipName";
            $pw = trim($_POST['pw'] ?? '');
            $cmd = "cd " . escapeshellarg($tempDir) . " && zip -r " .
                ($pw !== '' ? "-P " . escapeshellarg($pw) : "") . " " .
                escapeshellarg($zipPath) . " .";
            shell_exec($cmd);
            rrmdir($tempDir);
            rrmdir("$chunksDir/$uploadId");

            $mode = $_POST['mode'] ?? 'once';
            $fileData[$token] = [
                'name' => $zipName,
                'path' => $zipName,
                'time' => time(),
                'type' => in_array($mode, ['1h','3h','6h','12h','1d','3d','7d','14d','30d','forever']) ? 'time' : 'once',
                'duration' => match($mode) {
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
                },
                'used' => false,
                'password' => $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null
            ];

            // Generate Download-Link
            $script = basename($_SERVER['PHP_SELF']);
            $basePath = dirname($_SERVER['PHP_SELF']);
            if ($basePath === '/' || $basePath === '\\') $basePath = '';
            $path = ($script === 'index.php') ? "$basePath/" : "$basePath/$script";
            $link = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $path . "?lang=$lang&t=$token";
            
            $fileData[$token]['link'] = $link;
            
            $fileDataRaw = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

			$uploader = $fileDataRaw[$uploadId]['uploader_email'] ?? '';
			$recipient = $fileDataRaw[$uploadId]['recipient_email'] ?? '';
            
            $fileData[$token]['uploader_email'] = $uploader;
            $fileData[$token]['recipient_email'] = $recipient;
            $fileData[$token]['verified'] = false;
            
			unset($fileData[$uploadId]);

            file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));

            // Send verification-link to Uploader
            $encEmail = encrypt($uploader, $secretKey);
            $encToken = encrypt($token, $secretKey);
            $verifyUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . "$basePath/verify.php?email=$encEmail&token=$encToken";

            $subject = "{$t['title']} - {$t['sent_title_uploader']}";
            $message = "<html><body>
                {$t['sent_message_uploader']}
                {$t['title']}
                <p><a href='$verifyUrl'>$verifyUrl</a></p>
                </body></html>";

			$success = sendSMTPMail($uploader, $subject, $message, $from, $smtpHost, $smtpPort, $smtpUser, $smtpPass);

			// Show link or email-notification
            if (!Config::$default['only_upload']) {
				if (!Config::$default['send_email']) {
					echo $t['your_link'] . " 
					<a id='link' href='$link' target='_blank'>$link</a>
					<button onclick='copyLink()'>{$t['copy']}</button>
					<span id='copied' style='color:#4caf50; display:none;'>{$t['copied']}</span>";
					} else {
                	echo $t['email_sent'];
                }
            }
        }
    }

    http_response_code(200);
    exit;
}
