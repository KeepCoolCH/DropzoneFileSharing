<?php
// Create folders & filedata.json
$baseDir = dirname(__DIR__);
$envUploadBase = getenv('DROPZONE_UPLOAD_DIR');
if ($envUploadBase && trim($envUploadBase) !== '') {
    $uploadBase = rtrim($envUploadBase, '/');
} else {
    $uploadBase = $baseDir . '/uploads';
}

$uploadDir  = $uploadBase;
$chunksDir  = $uploadBase . '/.chunks';
$stagingRoot = $uploadBase . '/.staging';
$dataFile = $uploadBase . '/.filedata.json';
$incDir   = $baseDir . '/inc';
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
    public static $default =   [
    'lang_default' => 'de',
    'timezone' => 'Europe/Zurich',
    'admin_email' => 'you@example.com',
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
    'user_upload' => false,
    'send_email' => false,
    'admin_notify' => false,
    'show_dp' => true,
    'pwzip' => false,
  ];
}
