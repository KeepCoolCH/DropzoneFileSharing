<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';

header('Content-Type: text/plain; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('DropzoneUserSession');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

if (
    empty($input['csrf_token'])
    || empty($_SESSION['csrf_token'])
    || !hash_equals((string)$_SESSION['csrf_token'], (string)$input['csrf_token'])
) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$uploadId = trim($input['uploadId'] ?? '');
if (!preg_match('/^[a-f0-9]{16}$/', $uploadId)) {
    http_response_code(400);
    exit('Invalid uploadId');
}

$fileData = read_json($dataFile, []);

if (!empty($input['cleanup']) && !empty($fileData[$uploadId]['keys'])) {
    foreach ($fileData[$uploadId]['keys'] as $key) {
        @unlink($chunksDir . '/' . $key . '.part');
        @unlink($chunksDir . '/' . $key . '.meta');
        @unlink($chunksDir . '/' . $key . '.lock');
        foreach (glob($chunksDir . '/' . $key . '-*.current') ?: [] as $currentPath) {
            @unlink($currentPath);
        }
    }
}

if (isset($fileData[$uploadId])) {
    $saved = update_json_file($dataFile, function (array $current) use ($uploadId): array {
        unset($current[$uploadId]);
        return $current;
    });
    if (!$saved) {
        http_response_code(500);
        exit('Could not update upload metadata');
    }
    echo "Deleted entry\n";
} else {
    echo "Not found in DB\n";
}

if (!empty($input['cleanup'])) {
    $stagingDir = rtrim($stagingRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($uploadId);
    if (is_dir($stagingDir)) {
        rrmdir($stagingDir);
    }

    echo "Cleanup done\n";
}
