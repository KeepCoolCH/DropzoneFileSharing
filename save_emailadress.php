<?php
require_once 'inc/config.php';

$input = json_decode(file_get_contents('php://input'), true);

$uploadId = $input['uploadId'] ?? '';
$uploader = trim($input['uploader_email'] ?? '');
$recipientRaw = trim($input['recipient_email'] ?? '');

if (!$uploadId || !$uploader || !$recipientRaw) {
	http_response_code(400);
	die('Missing parameters');
}

$recipients = preg_split('/[\s,;]+/', $recipientRaw);
$recipients = array_unique(array_filter(array_map('trim', $recipients), function ($email) {
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}));

if (empty($recipients)) {
	http_response_code(400);
	die('No valid recipient emails');
}

$fileData = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
$fileData[$uploadId] = [
	'uploader_email' => $uploader,
	'recipient_email' => $recipients
];

file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
http_response_code(200);
