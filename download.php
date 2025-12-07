<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

function hb(int $b): string {
    $u=['B','KB','MB','GB','TB']; $i=0;
    while($b>=1024&&$i<4){$b/=1024;$i++;}
    return sprintf('%.1f %s',$b,$u[$i]);
}

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
$pwValid = false;
if (!empty($info['password'])) {

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['pw_token'] === $token && isset($_POST['pw_input'])) {
        $pwValid = password_verify($_POST['pw_input'], $info['password']);
        if (!$pwValid) $pwError = true;
    }

    if (!$pwValid) {

        $fileName = basename($info['name'] ?? $filePath);
        $fileSize = hb(filesize($filePath));

        $errorMsg = $pwError ? "<span>{$t['wrong_password']}</span><br><br>" : "";
        echo "<!DOCTYPE html><html lang='$lang'><head><meta charset='UTF-8' name='viewport' content='width=device-width, initial-scale=0.6'><title>{$t['title']} - {$t['password_required']}</title>
        <link rel='stylesheet' href='css/style.css'>
        </head><body>
        <logoimg><a href='index.php?lang=$lang'><img src='img/logo.png' alt='Dropzone Logo' width='300'></a></logoimg>
        <div id='main'>
        <div id='form'>
            <form method='post'>
            <div id='languageFlags' style='font-size: 2em; cursor: pointer; user-select: none;'>
                <span id='flag-de' title='German' onclick='changeLang(\"de\")' style='margin-right: 10px; " . ($lang === 'de' ? '' : 'opacity:0.5;') . "'>🇩🇪</span>
                <span id='flag-en' title='English' onclick='changeLang(\"en\")' style='margin-right: 10px; " . ($lang === 'en' ? '' : 'opacity:0.5;') . "'>🇬🇧</span>
                <span id='flag-fr' title='Français' onclick='changeLang(\"fr\")' style='margin-right: 10px; " . ($lang === 'fr' ? '' : 'opacity:0.5;') . "'>🇫🇷</span>
                <span id='flag-it' title='Italiano' onclick='changeLang(\"it\")' style='" . ($lang === 'it' ? '' : 'opacity:0.5;') . "'>🇮🇹</span>
            </div>
            <h2>{$t['password_required']}</h2>
                <strong>{$t['file']}:</strong> {$fileName}
                <br><br>
                <strong>{$t['th_size']}:</strong> {$fileSize}
                <br><br><br>
                <input type='hidden' name='pw_token' value='" . htmlspecialchars($token) . "'>
                <input type='text' name='pw_input' placeholder='{$t['enter_password']}' required autofocus>
                <br><br>
                <button type='submit' style='margin-top: 10px;'>{$t['start_download']}</button>
            </form>
            <br><br>
            $errorMsg
            <br>
            <footer>{$t['title']} {$t['version']} {$t['footer_text']}</footer>
            <script src='js/main.js'></script>
        </body></html>";
        exit;
    }
}

// show Download Page
    if (!empty(Config::$default['show_dp']) && (($_GET['dl'] ?? '') !== '1') && !$pwValid) {

        $fileName = basename($info['name'] ?? $filePath);
        $fileSize = hb(filesize($filePath));
        $scriptName = basename(__FILE__);

        echo "<!DOCTYPE html><html lang='$lang'><head><meta charset='UTF-8' name='viewport' content='width=device-width, initial-scale=0.6'><title>{$t['title']} - {$t['start_download']}</title>
        <link rel='stylesheet' href='css/style.css'>
        </head><body>
        <logoimg><a href='index.php?lang=$lang'><img src='img/logo.png' alt='Dropzone Logo' width='300'></a></logoimg>
        <div id='main'>
        <div id='form'>
                <div id='languageFlags' style='font-size: 2em; cursor: pointer; user-select: none;'>
                    <span id='flag-de' title='German' onclick='changeLang(\"de\")' style='margin-right: 10px; " . ($lang === 'de' ? '' : 'opacity:0.5;') . "'>🇩🇪</span>
                    <span id='flag-en' title='English' onclick='changeLang(\"en\")' style='margin-right: 10px; " . ($lang === 'en' ? '' : 'opacity:0.5;') . "'>🇬🇧</span>
                    <span id='flag-fr' title='Français' onclick='changeLang(\"fr\")' style='margin-right: 10px; " . ($lang === 'fr' ? '' : 'opacity:0.5;') . "'>🇫🇷</span>
                    <span id='flag-it' title='Italiano' onclick='changeLang(\"it\")' style='" . ($lang === 'it' ? '' : 'opacity:0.5;') . "'>🇮🇹</span>
                </div>
                <h2>{$t['start_download']}</h2>
                <strong>{$t['file']}:</strong> {$fileName}
                <br><br>
                <strong>{$t['th_size']}:</strong> {$fileSize}
                <br><br><br>
                <button onclick=\"window.location.href='" . htmlspecialchars($scriptName, ENT_QUOTES, 'UTF-8') . "?lang=$lang&t=" . urlencode($token) . "&dl=1'\" style='margin-top: 10px;'>{$t['start_download']}</button>
                <br><br><br><br>
                <footer>{$t['title']} {$t['version']} {$t['footer_text']}</footer>
            </div>
        </div>
        <script src='js/main.js'></script>
    </body></html>";
    exit;
}

// mark Download
if (($fileData[$token]['type'] ?? '') === 'once') {
    $fileData[$token]['used'] = true;
}

// tracking Download
if (!isset($fileData[$token]['downloads'])) {
    $fileData[$token]['downloads'] = 0;
}

$fileData[$token]['downloads'] += 1;
$fileData[$token]['last_download'] = time();
file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));


header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($info['name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache');
header('Pragma: public');
flush();
readfile($filePath);
exit;
