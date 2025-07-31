<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

// UPLOAD
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['chunk'])) {
    $uploadId = $_POST['uploadId'] ?? '';
    $chunkIndex = $_POST['chunkIndex'] ?? '';
    $totalChunks = $_POST['totalChunks'] ?? '';
    $filename = $_POST['name'] ?? '';
    $isLastFile = $_POST['isLastFile'] ?? '';

    $chunkBase = "$chunksDir/$uploadId/" . sha1($filename);
    if (!is_dir($chunkBase)) mkdir($chunkBase, 0777, true);

    $chunkPath = "$chunkBase/chunk_$chunkIndex";
    if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath)) {
        http_response_code(500);
        die('❌ Upload failed');
    }

    file_put_contents("$chunkBase/info.txt", $filename);

    if ($isLastFile === '1') {
        $allComplete = true;
        $fileChunks = [];

        foreach (glob("$chunksDir/$uploadId/*", GLOB_ONLYDIR) as $fileDir) {
            $infoPath = "$fileDir/info.txt";
            $filename = file_exists($infoPath) ? file_get_contents($infoPath) : null;
            if (!$filename) continue;

            $chunks = glob("$fileDir/chunk_*");
            sort($chunks);
            $chunkCount = count($chunks);
            $expected = null;

            if (file_exists($fileDir . '/chunk_0')) {
                $firstChunk = basename($chunks[0]);
                preg_match('/chunk_(\d+)/', end($chunks), $m);
                $expected = isset($m[1]) ? ($m[1] + 1) : null;
            }

            if (!$expected || $chunkCount != $expected) {
                $allComplete = false;
                break;
            }

            $fileChunks[] = ['name' => $filename, 'chunks' => $chunks, 'dir' => $fileDir];
        }

        if ($allComplete) {
            $token = bin2hex(random_bytes(8));
            $tempDir = "$uploadDir/$token";
            mkdir($tempDir, 0777, true);

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
                    '1d' => 1 * 24 * 3600,
                    '3d' => 3 * 24 * 3600,
                    '7d' => 7 * 24 * 3600,
                    '14d' => 14 * 24 * 3600,
                    '30d' => 30 * 24 * 3600,
                    'forever' => 0,
                    default => 30 * 24 * 3600,
                },
                'used' => false,
                'password' => $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null
            ];
            file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));

            $script = basename($_SERVER['PHP_SELF']);
            $basePath = dirname($_SERVER['PHP_SELF']);
            if ($basePath === '/' || $basePath === '\\') $basePath = '';
            $path = ($script === 'index.php') ? "$basePath/" : "$basePath/$script";
            if (!Config::$default['onlyupload']) {
			$link = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $path . "?lang=$lang&t=$token";
			echo $t['your_link'] . " 
				<a id='link' href='$link' target='_blank'>$link</a>
				<button onclick='copyLink()'>{$t['copy']}</button>
				<span id='copied' style='color:#4caf50; display:none;'>{$t['copied']}</span>";
        	}
        }
    }

    http_response_code(200);
    exit;
}
