<?php
require_once 'inc/config.php';

header('Content-Type: text/plain; charset=utf-8');

$ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (stripos($ct, 'application/json') !== false) {
    $raw    = file_get_contents('php://input');
    $input  = json_decode($raw, true);
    if (!is_array($input)) $input = [];
} else {
    $input = $_POST;
}

$uploadId      = trim($input['uploadId'] ?? '');
$uploader      = trim($input['uploader_email'] ?? '');
$recipientRaw  = trim($input['recipient_email'] ?? '');

$recipients = preg_split('/[\s,;]+/', $recipientRaw);
$recipients = array_unique(array_filter(array_map('trim', $recipients), function ($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}));

$fileData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
$fileData[$uploadId] = [
	'uploader_email' => $uploader,
	'recipient_email' => $recipients
];

file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
http_response_code(200);
