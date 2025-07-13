<?php
// Create folders & filedata.json
$uploadDir = __DIR__ . '/../uploads';
$chunksDir = $uploadDir . '/.chunks';
$dataFile = $uploadDir . '/.filedata.json';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($chunksDir)) mkdir($chunksDir, 0777, true);

if (!file_exists($dataFile)) file_put_contents($dataFile, '{}');
$fileData = json_decode(file_get_contents($dataFile), true);
if (!is_array($fileData)) $fileData = [];

$now = time();