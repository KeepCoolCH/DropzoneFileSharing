<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';

header('Content-Type: text/plain; charset=utf-8');

$secretToken = 'serverintern';

$ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
if (stripos($ct, 'application/json') !== false) {
    $raw    = file_get_contents('php://input');
    $input  = json_decode($raw, true);
    if (!is_array($input)) $input = [];
} else {
    $input = $_POST;
}

if (!isset($input['token']) || $input['token'] !== $secretToken) {
    http_response_code(403);
    exit('Forbidden');
}

$uploadId      = trim($input['uploadId'] ?? '');
$uploader      = trim($input['uploader_email'] ?? '');
$recipientRaw  = trim($input['recipient_email'] ?? '');

if (!preg_match('/^[a-f0-9]{16}$/', $uploadId)) {
    http_response_code(400);
    exit('Invalid uploadId');
}

if ($uploader !== '' && !filter_var($uploader, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Invalid uploader email');
}

$recipients = preg_split('/[\s,;]+/', $recipientRaw);
$recipients = array_unique(array_filter(array_map('trim', $recipients), function ($email) {
return filter_var($email, FILTER_VALIDATE_EMAIL);
}));

$saved = update_json_file($dataFile, function (array $fileData) use ($uploadId, $uploader, $recipients): array {
    $fileData[$uploadId] = [
        'uploader_email'  => $uploader,
        'recipient_email' => $recipients,
        'keys'            => isset($fileData[$uploadId]['keys']) && is_array($fileData[$uploadId]['keys'])
            ? $fileData[$uploadId]['keys']
            : []
    ];
    return $fileData;
});

if (!$saved) {
    http_response_code(500);
    exit('Could not save upload metadata');
}
http_response_code(200);
echo 'OK';
