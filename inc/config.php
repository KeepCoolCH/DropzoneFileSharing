<?php
// Create folders & filedata.json
$uploadDir = __DIR__ . '/../uploads';
$chunksDir = $uploadDir . '/.chunks';
$dataFile = $uploadDir . '/.filedata.json';
$incDir = __DIR__ . '/../inc';
$envDir = $incDir . '/.env';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($chunksDir)) mkdir($chunksDir, 0777, true);
if (!is_dir($envDir)) mkdir($envDir, 0777, true);

if (!file_exists($dataFile)) file_put_contents($dataFile, '{}');
$fileData = json_decode(file_get_contents($dataFile), true);
if (!is_array($fileData)) $fileData = [];

$now = time();

class Config {
    public static $default = [
        'lang_default' => 'de',
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
        'only_upload' => false,
        'send_email' => true,
    ];
}