<?php
declare(strict_types=1);

$isHttps =
  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
  || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

session_name('DropzoneAdminSession');
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => $isHttps,
  'cookie_samesite' => 'Lax',
]);

require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

define('UPLOADS_DIR', $uploadDir);
define('FILEDATA_JSON', $dataFile);

if (empty($_SESSION['logged_in'])) {
    http_response_code(403);
    exit('Forbidden');
}

function read_json(string $path, $default) {
    if (!file_exists($path)) return $default;
    $s = file_get_contents($path);
    $d = $s ? json_decode($s, true) : null;
    return is_array($d) ? $d : $default;
}

function safe_uploads_join(string $rel): ?string {
    $rel = ltrim($rel, '/\\');
    $cand = realpath(UPLOADS_DIR . '/' . $rel);
    $uploads = realpath(UPLOADS_DIR);
    if ($cand && $uploads && str_starts_with($cand, $uploads)) return $cand;
    $cand2 = UPLOADS_DIR . '/' . $rel;
    $norm = str_replace(['//','\\'], ['/', '/'], $cand2);
    if (strpos(realpath(dirname($norm)) ?: '', realpath(UPLOADS_DIR)) === 0) return $norm;
    return null;
}

function pick_entry_by_path(array $data, string $path) {
    foreach ($data as $e)
        if ((string)($e['path'] ?? '') === $path) return $e;
    return null;
}

function header_sane_filename(string $fn): string {
    $base = basename($fn);
    return str_replace(['"', "\r", "\n"], ['\'', '', ''], $base);
}

$path = trim((string)($_GET['path'] ?? ''));
if ($path === '') {
    http_response_code(400);
    exit('Missing path');
}

$data = read_json(FILEDATA_JSON, []);
$entry = pick_entry_by_path($data, $path);
if (!$entry) {
    http_response_code(404);
    exit('Not found');
}

$abs = safe_uploads_join($path);
if (!$abs || !is_file($abs)) {
    http_response_code(404);
    exit('File missing');
}

$filename = (string)($entry['name'] ?? basename($abs));
$size = filesize($abs);
$mime = 'application/octet-stream';
if (function_exists('mime_content_type')) {
    $m = mime_content_type($abs);
    if ($m) $mime = $m;
}

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . header_sane_filename($filename) . '"');
header('Content-Transfer-Encoding: binary');
header('Content-Length: ' . $size);
header('Cache-Control: private, no-transform, no-store, must-revalidate');

$fp = fopen($abs, 'rb');
if ($fp) {
    while (!feof($fp)) {
        echo fread($fp, 8192);
        @ob_flush();
        flush();
    }
    fclose($fp);
}
exit;
?>
