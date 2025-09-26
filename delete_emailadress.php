<?php
require_once 'inc/config.php';

header('Content-Type: text/plain; charset=utf-8');

$secretToken = 'serverintern';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

if (!isset($input['token']) || $input['token'] !== $secretToken) {
    http_response_code(403);
    exit('Forbidden');
}

$uploadId = trim($input['uploadId'] ?? '');
if ($uploadId === '') {
    http_response_code(400);
    exit('No uploadId');
}

$fileData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];

if (!empty($input['cleanup']) && !empty($fileData[$uploadId]['keys'])) {
    foreach ($fileData[$uploadId]['keys'] as $key) {
        @unlink($chunksDir . '/' . $key . '.part');
        @unlink($chunksDir . '/' . $key . '.meta');
    }
}

if (isset($fileData[$uploadId])) {
    unset($fileData[$uploadId]);
    file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
    echo "Deleted entry\n";
} else {
    echo "Not found in DB\n";
}

if (!empty($input['cleanup'])) {
    function rrmdir($dir) {
        if (!is_dir($dir)) return;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : @unlink($file->getRealPath());
        }
        @rmdir($dir);
    }

    $stagingDir = rtrim($stagingRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($uploadId);
    @rrmdir($stagingDir);

    echo "Cleanup done\n";
}
