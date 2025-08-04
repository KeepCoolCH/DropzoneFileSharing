<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

// DOWNLOAD
if (!isset($_GET['t'])) {
    http_response_code(400);
    exit('Missing token');
}

$token = basename($_GET['t']);
if (!isset($fileData[$token])) {
    http_response_code(404);
    exit($t['link_not_found']);
}

$info = $fileData[$token];
$filePath = $uploadDir . '/' . $info['path'];
if (!file_exists($filePath)) {
    unset($fileData[$token]);
    file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
    http_response_code(410);
    exit($t['file_missing']);
}

// Password check
$pwError = false;
if (!empty($info['password'])) {
    $pwValid = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['pw_token'] === $token && isset($_POST['pw_input'])) {
        $pwValid = password_verify($_POST['pw_input'], $info['password']);
        if (!$pwValid) $pwError = true;
    }

    if (!$pwValid) {
        $errorMsg = $pwError ? "<span>{$t['wrong_password']}</span><br><br>" : "";
        echo "<!DOCTYPE html><html lang='$lang'><head><meta charset='UTF-8' name='viewport' content='width=device-width, initial-scale=0.6'><title>{$t['title']} - {$t['password_required']}</title>
        <link rel='stylesheet' href='inc/style.css'>
        </head><body>
        <h2>{$t['title']} - {$t['password_required']}</h2>
            <form method='post'>
			<div id='languageFlags' style='font-size: 2em; cursor: pointer; user-select: none;'>
				<span id='flag-de' title='German' onclick='changeLang(\"de\")' style='margin-right: 10px; " . ($lang === 'de' ? '' : 'opacity:0.5;') . "'>🇩🇪</span>
				<span id='flag-en' title='English' onclick='changeLang(\"en\")' style='margin-right: 10px; " . ($lang === 'en' ? '' : 'opacity:0.5;') . "'>🇬🇧</span>
				<span id='flag-fr' title='Français' onclick='changeLang(\"fr\")' style='margin-right: 10px; " . ($lang === 'fr' ? '' : 'opacity:0.5;') . "'>🇫🇷</span>
				<span id='flag-it' title='Italiano' onclick='changeLang(\"it\")' style='" . ($lang === 'it' ? '' : 'opacity:0.5;') . "'>🇮🇹</span>
			</div>
                <input type='hidden' name='pw_token' value='" . htmlspecialchars($token) . "'>
                <input type='password' name='pw_input' placeholder='{$t['enter_password']}' required autofocus>
                <br><br>
                <button type='submit'>{$t['start_download']}</button>
            </form>
            <br><br>
            <p>$errorMsg</p>
            <footer>{$t['title']} {$t['version']} {$t['footer_text']}</footer>
            <script src='js/main.js'></script>
        </body></html>";
        exit;
    }
}

// mark Download
if ($info['type'] === 'once') {
    $fileData[$token]['used'] = true;
    file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($info['name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache');
header('Pragma: public');
flush();
readfile($filePath);
exit;
