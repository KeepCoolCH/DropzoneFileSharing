<?php
// Clean up temp files and folder
function rrmdir($dir) {
	foreach (scandir($dir) as $item) {
		if ($item === '.' || $item === '..') continue;
		$path = $dir . DIRECTORY_SEPARATOR . $item;
		if (is_dir($path)) rrmdir($path);
		else unlink($path);
	}
	rmdir($dir);
}

// Remove expired files
foreach ($fileData as $token => $info) {
    $duration = $info['duration'] ?? 3600;
    $expired = ($info['type'] === 'once' && $info['used']) || ($info['type'] === 'time' && $now - $info['time'] > $duration);
    if ($expired && file_exists($uploadDir . '/' . $info['path'])) {
        unlink($uploadDir . '/' . $info['path']);
        unset($fileData[$token]);
    }
}
file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));

// Clean up old chunk uploads
$maxAge = 5 * 3600; // 5 Hours
foreach (glob("$chunksDir/*") as $uploadFolder) {
	if (is_dir($uploadFolder) && time() - filemtime($uploadFolder) > $maxAge) {
		rrmdir($uploadFolder);
	}
}

// Generate .htaccess for Upload-Folder (deny direct access)
$htaccessPath = $uploadDir . '/.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = <<<HTACCESS
# Prevent direct access to files
Order deny,allow
Deny from all
HTACCESS;

    file_put_contents($htaccessPath, $htaccessContent);
}
