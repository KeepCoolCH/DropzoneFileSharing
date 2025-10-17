<?php
// Create folders & filedata.json
$uploadDir = __DIR__ . '/../uploads';
$chunksDir = $uploadDir . '/.chunks';
$stagingRoot = $uploadDir . '/.staging';
$dataFile = $uploadDir . '/.filedata.json';
$incDir = __DIR__ . '/../inc';
$envDir = $incDir . '/.env';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($chunksDir)) mkdir($chunksDir, 0777, true);
if (!is_dir($stagingRoot)) mkdir($stagingRoot, 0777, true);
if (!is_dir($envDir)) mkdir($envDir, 0777, true);

if (!file_exists($dataFile)) file_put_contents($dataFile, '{}');
$fileData = json_decode(file_get_contents($dataFile), true);
if (!is_array($fileData)) $fileData = [];

$now = time();

class Config {
    public static $default = [
        'lang_default' => 'de',
        'timezone' => 'Europe/Zurich',
        'valid_once' => true,
        'valid_1h' => true,
        'valid_3h' => true,
        'valid_6h' => true,
        'valid_12h' => true,
        'valid_1d' => true,
        'valid_3d' => true,
        'valid_7d' => true,
        'valid_14d' => true,
        'valid_30d' => true,
        'valid_forever' => true,
        'only_upload' => false,   // Set to true to enable upload-only mode (no link generation). If false, the normal mode is active.
        'send_email' => true,   // Set to true if you have configured a valid mail server in the .env file. If false, only link sharing is available.
        'pwzip' => false,   // Set to true to protect the ZIP file itself with the password. If false, only the download is password-protected, not the ZIP file.
    ];
}
