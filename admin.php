<?php

declare(strict_types=0);

session_name('DropzoneAdminSession');
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => isset($_SERVER['HTTPS']),
  'cookie_samesite' => 'Lax',
]);

require 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

if (!empty(Config::$default['timezone'])) {
    date_default_timezone_set(Config::$default['timezone']);
}

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? $defaultLang);
if (!isset($_GET['lang']) && isset($_SESSION['lang']) && $_SESSION['lang'] !== $defaultLang) {
    $lang = $defaultLang;
}
$_SESSION['lang'] = $lang;

define('ADMIN_JSON', $incDir . '/.admin.json');
define('CONFIG_FILE', $incDir . '/config.php');
define('ENV_FILE', $incDir . '/.env/.env');
define('UPLOADS_DIR', $uploadDir);
define('FILEDATA_JSON', $dataFile);

function saveConfigFile(string $path, array $data): void {
    $header = '';
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (preg_match('/^\s*class\s+Config\b/', $line)) break;
            $header .= $line . "\n";
        }
    } else {
        $header = "<?php\n\n// Dropzone File Sharing configuration (auto-created)\n\n";
    }

    $export = var_export($data, true);
    $export = preg_replace(['/^array\s*\(/', '/\)(\s*)$/'], ['[', ']$1'], $export);
    $exportIndented = preg_replace('/^/m', '  ', $export);

    $php = $header .
        "class Config {\n" .
        "    public static \$default = " . $exportIndented . ";\n" .
        "}\n";

    $tmp = $path . '.tmp';
    file_put_contents($tmp, $php);
    rename($tmp, $path);
}

$configData = Config::$default;

if (isset($_POST['config_update'])) {
    foreach ($configData as $key => $val) {
        if (isset($_POST[$key])) {
            $configData[$key] = ($val === true || $val === false)
                ? ($_POST[$key] === '1')
                : trim($_POST[$key]);
        } elseif ($val === true || $val === false) {
            $configData[$key] = false;
        }
    }
    saveConfigFile(CONFIG_FILE, $configData);
    $config_message = $t['configuration_save'];
}

function loadEnvFile(string $path): array {
    $data = [];
    if (!file_exists($path)) return $data;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $val] = array_pad(explode('=', $line, 2), 2, '');
        $data[trim($key)] = trim($val);
    }
    return $data;
}

function saveEnvFile(string $path, array $data): void {
    $lines = [];
    if (file_exists($path)) {
        $existing = file($path, FILE_IGNORE_NEW_LINES);
        foreach ($existing as $line) {
            if (str_starts_with(trim($line), '#')) {
                $lines[] = $line;
            }
        }
    } else {
        $lines[] = '# SMTP Configuration';
    }

    foreach ($data as $k => $v) {
        $lines[] = "$k=$v";
    }
    file_put_contents($path, implode("\n", $lines) . "\n");
}

$envData = loadEnvFile(ENV_FILE);

if (isset($_POST['env_update'])) {
    $envData['SMTP_HOST'] = trim($_POST['SMTP_HOST'] ?? '');
    $envData['SMTP_PORT'] = trim($_POST['SMTP_PORT'] ?? '');
    $envData['SMTP_USER'] = trim($_POST['SMTP_USER'] ?? '');
    $envData['SMTP_PASS'] = trim($_POST['SMTP_PASS'] ?? '');
    saveEnvFile(ENV_FILE, $envData);
    $env_message = $t['smtp_save'];
}

function read_json(string $p, $def) {
    if (!file_exists($p)) return $def;
    $s = file_get_contents($p);
    $d = $s ? json_decode($s, true) : null;
    return is_array($d) ? $d : $def;
}
function write_json(string $p, $d): bool {
    $tmp = $p . '.tmp';
    file_put_contents($tmp, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return rename($tmp, $p);
}
function hb(int $b): string {
    $u=['B','KB','MB','GB','TB']; $i=0;
    while($b>=1024&&$i<4){$b/=1024;$i++;}
    return sprintf('%.1f %s',$b,$u[$i]);
}
function tsfmt(?int $t): string { return $t ? date('Y-m-d H:i:s',$t) : '—'; }
function now(): int { return time(); }
function safe_uploads_join(string $r): ?string {
    $r=ltrim($r,'/\\'); $a=realpath(UPLOADS_DIR.'/'.$r);
    return ($a && str_starts_with($a,realpath(UPLOADS_DIR))) ? $a : (is_file(UPLOADS_DIR.'/'.$r)?UPLOADS_DIR.'/'.$r:null);
}
function compute_end_time(array $e): ?int {
    if(!isset($e['time'],$e['duration'])) return null;
    $t=(int)$e['time']; $d=(int)$e['duration'];
    return $d>0?$t+$d:null;
}

// --- Setup ---
$admin = read_json(ADMIN_JSON, null);
if (!$admin) {
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['setup'])) {
        $u=trim($_POST['username']??''); $p=$_POST['password']??''; $p2=$_POST['password2']??'';
        if ($u==''||$p=='') $err=$t['register_error'];
        elseif ($p!==$p2) $err=$t['password_error'];
        else {
            write_json(ADMIN_JSON,['username'=>$u,'password_hash'=>password_hash($p,PASSWORD_DEFAULT),'created_at'=>time()]);
            $_SESSION['logged_in']=true; $_SESSION['user']=$u; header('Location: admin.php?lang=' . urlencode($lang)); exit;
        }
    }
    ?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['admin_setup'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body>
<div id="form">
    <logoimg><a href="index.php?lang=<?= $lang ?>"><img src="img/logo.png" alt="Dropzone File Sharing" width="300"></a></logoimg>

    <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
        <span id="flag-de" title="German" onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
        <span id="flag-en" title="English" onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
        <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
        <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
    </div>
    <h2><?= $t['admin_setup'] ?></h2>
    <?php if(!empty($err)) echo "<p style='color:red'>$err</p>"; ?>
    <form method="post">
        <input type="hidden" name="setup" value="1">
        <input type="text" name="username" placeholder="<?= $t['username'] ?>" required><br>
        <input type="text" name="password" placeholder="<?= $t['password'] ?>" autocomplete="current-password" inputmode="text" spellcheck="false" style="-webkit-text-security: disc;"><br>
        <input type="text" name="password2" placeholder="<?= $t['password_confirm'] ?>" autocomplete="current-password" inputmode="text" spellcheck="false" style="-webkit-text-security: disc;"><br>
        <button><?= $t['create_admin_button'] ?></button>
    </form>
</div>
<footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
<!-- START: Invisible form not needed in the admin panel because of main.js-->
<form id="uploadForm" style="display:none;" aria-hidden="true" novalidate>
  <input type="file" id="fileInput" style="display:none;">
  <div id="dropzone" style="display:none;"></div>
  <div id="selectedFile" style="display:none;"></div>
</form>
<!-- END: Invisible form not needed in the admin panel because of main.js-->
<script src="js/main.js"></script>
</body>
</html>
<?php exit;
}

// --- Auth ---
if (isset($_GET['logout'])) {
    session_destroy();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    header('Location: admin.php?lang=' . urlencode($lang));
    exit;
}
if(empty($_SESSION['logged_in'])){
    if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login'])){
        $u=trim($_POST['username']??''); $p=$_POST['password']??''; $adm=read_json(ADMIN_JSON,null);
        if($adm && hash_equals($adm['username'],$u) && password_verify($p,$adm['password_hash'])){
            $_SESSION['logged_in']=true; $_SESSION['user']=$u; header('Location: admin.php?lang=' . urlencode($lang)); exit;
        } else $err=$t['login_error'];
    }
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['admin_login'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body class="admin">
<div id="form">
    <logoimg><a href="index.php?lang=<?= $lang ?>"><img src="img/logo.png" alt="Dropzone File Sharing" width="300"></a></logoimg>

    <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
        <span id="flag-de" title="German" onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
        <span id="flag-en" title="English" onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
        <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
        <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
    </div>
    <h2><?= $t['admin_login'] ?></h2>
    <?php if(!empty($err)) echo "<p style='color:red'>$err</p>"; ?>
    <form method="post">
        <input type="hidden" name="login" value="1">
        <input type="text" name="username" placeholder="<?= $t['username'] ?>" required><br>
        <input type="text" name="password" placeholder="<?= $t['password'] ?>" autocomplete="current-password" inputmode="text" spellcheck="false" style="-webkit-text-security: disc;"><br>
        <button><?= $t['login_button'] ?></button>
    </form>
</div>
<footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
<!-- START: Invisible form not needed in the admin panel because of main.js-->
<form id="uploadForm" style="display:none;" aria-hidden="true" novalidate>
  <input type="file" id="fileInput" style="display:none;">
  <div id="dropzone" style="display:none;"></div>
  <div id="selectedFile" style="display:none;"></div>
</form>
<!-- END: Invisible form not needed in the admin panel because of main.js-->
<script src="js/main.js"></script>
</body>
</html>
<?php exit;
}

// --- Actions ---
$notice='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $data=read_json(FILEDATA_JSON,[]);
    if(isset($_POST['delete_path'])){
        $path=$_POST['delete_path']; $new=[];
        foreach($data as $e){
            if(($e['path']??'')===$path){ $abs=safe_uploads_join($path); if($abs) @unlink($abs); }
            else $new[]=$e;
        }
        write_json(FILEDATA_JSON,$new); $notice=$t['entry_delete'];
    }
    elseif(isset($_POST['validity_path'],$_POST['validity_mode'])){
        $path=$_POST['validity_path']; $mode=$_POST['validity_mode'];
        $presets=[
            '1h' => 3600,
            '3h' => 3 * 3600,
            '6h' => 6 * 3600,
            '12h' => 12 * 3600,
            '1d' => 1 * 86400,
            '3d' => 3 * 86400,
            '7d' => 7 * 86400,
            '14d' => 14 * 86400,
            '30d' => 30 * 86400,
            'forever' => 0
        ];
        foreach($data as &$e){
            if(($e['path']??'') === $path){
                $e['mode'] = $mode;
                $e['type'] = $mode;
                $e['duration'] = $presets[$mode] ?? 86400;
            }
        }
        write_json(FILEDATA_JSON,$data); $notice=$t['mode_change'] . "$mode.";
    }
    elseif(isset($_POST['toggle_verified_path'])){
        $path=$_POST['toggle_verified_path'];
        foreach($data as &$e){ if(($e['path']??'')===$path){ $e['verified']=!empty($e['verified'])?false:true; } }
        write_json(FILEDATA_JSON,$data); $notice=$t['verification_change'];
    }
    elseif(isset($_POST['password_path'], $_POST['new_password'])){
        $path=$_POST['password_path']; $new=$_POST['new_password'];
        foreach($data as &$e){
            if(($e['path']??'')===$path){
                $e['password']=trim($new)===''?null:password_hash($new,PASSWORD_DEFAULT);
            }
        }
        write_json(FILEDATA_JSON,$data); $notice=$t['password_change'];
    }
}

$entries=read_json(FILEDATA_JSON,[]);
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['admin_panel'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body class="admin">
    <logoimg><a href="index.php?lang=<?= $lang ?>"><img src="img/logo.png" alt="Dropzone File Sharing" width="300"></a></logoimg>
    <div id="main">
    <div id="form">
        <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
            <span id="flag-de" title="German" onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
            <span id="flag-en" title="English" onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
            <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
            <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
        </div>
        <h2><?= $t['admin_panel'] ?></h2>

        <?php if($notice): ?>
        <p style="color:green"><?=$notice?></p>
        <script>
        setTimeout(() => location.reload(), 1500);
        </script>
        <?php endif; ?>

        <button type="button" id="toggleTableBtn" style="margin:10px 0;">
          <?= htmlspecialchars($t['show_uploads_button']) ?>
        </button>

        <input type="text" style="margin-top: 10px" id="liveSearch" placeholder="<?= $t['search_placeholder'] ?>">

        <?php if(empty($entries)): ?>
            <p><?= $t['noentry'] ?></p>
        <?php else: ?>
        <?php foreach($entries as $e):
            $path=$e['path']??''; $abs=safe_uploads_join($path);
            $name=$e['name']??'—';
            $upl=tsfmt($e['time']??0);
            $end=tsfmt(compute_end_time($e));
            $size=$abs?hb(filesize($abs)):'';
            $mode = $e['mode'] ?? ($e['type'] ?? 'once');
            $used=!empty($e['used']); $ver=!empty($e['verified']);
            $pw=!empty($e['password']);
            $up=$e['uploader_email']??''; $rec=$e['recipient_email']??[];
            if(is_string($rec)) $rec=[$rec];
        ?>
        <div id="tableSection" class="table-responsive" style="display:none;">
        <table>
            <thead>
            <tr>
                <th style="width: 200px;"><?= $t['th_file'] ?></th><th style="width: 60px;"><?= $t['th_size'] ?></th><th style="width: 100px;"><?= $t['th_uploaddate'] ?></th><th style="width: 100px;"><?= $t['th_expirationdate'] ?></th><th><?= $t['th_mode'] ?></th><th style="width: 60px;"><?= $t['th_status'] ?></th><th><?= $t['th_password'] ?></th><th style="min-width: 200px;"><?= $t['th_email'] ?></th><th style="width: 160px;"><?= $t['th_actions'] ?></th>
            </tr>
            </thead>
            <tr>
                <td data-label="<?= $t['th_file'] ?>"><?=htmlspecialchars($name)?><br><br><?=htmlspecialchars($e['link'])?></td>
                <td data-label="<?= $t['th_size'] ?>"><?=$size?></td>
                <td data-label="<?= $t['th_uploaddate'] ?>"><?=$upl?></td>
                <td data-label="<?= $t['th_expirationdate'] ?>"><?=$end?></td>
                <td data-label="<?= $t['th_mode'] ?>">
                <form method="post">
                    <input type="hidden" name="validity_path" value="<?=htmlspecialchars($path)?>">
                    <?php
                        $mode = $e['mode'] ?? ($e['type'] ?? 'once');

                        $validOptions = [
                            'once', '1h', '3h', '6h', '12h', '1d', '3d', '7d', '14d', '30d', 'forever'
                        ];
                    ?>
                    <select name="validity_mode" style="flex:1;">
                        <?php
                        foreach ($validOptions as $opt) {
                            if (!empty(Config::$default["valid_$opt"])) {
                                $sel = ($mode === $opt) ? 'selected' : '';
                                echo "<option value=\"$opt\" $sel>⇅ {$t["valid_$opt"]}</option>";
                            }
                        }
                        ?>
                    </select>
                    <button type="submit"><?= $t['save_button'] ?></button>
                </form>
                </td>
                <td data-label="<?= $t['th_status'] ?>"><?=($ver?$t['verified']:$t['notverified'])?></td>
                <td data-label="<?= $t['th_password'] ?>">
                    <?=$pw?$t['password_set']:$t['password_notset']?>
                    <form method="post">
                        <input type="hidden" name="password_path" value="<?=htmlspecialchars($path)?>">
                        <input type="text" name="new_password" placeholder="<?= $t['password_notice'] ?>">
                        <button><?= $t['save_button'] ?></button>
                    </form>
                </td>
                <td data-label="<?= $t['th_email'] ?>">
                    <?php if($up): ?><div><?= $t['uploader'] ?>: <?=htmlspecialchars($up)?></div><?php endif; ?><br>
                    <?php if(!empty($rec)): ?><div><?= $t['recipient'] ?>: <?=htmlspecialchars(implode(', ',$rec))?></div><?php endif; ?>
                </td>
                <td data-label="<?= $t['th_actions'] ?>">
                    <?php if($abs && is_file($abs)): ?>
                        <form method="get" action="admin_download.php">
                            <input type="hidden" name="path" value="<?=htmlspecialchars($path)?>">
                            <button class="btn"><?= $t['download_button'] ?></button>
                        </form>
                    <?php endif; ?>
                    <form method="post" onsubmit="return confirm('<?= htmlspecialchars($t['delete_confirm'], ENT_QUOTES) ?>')">
                        <input type="hidden" name="delete_path" value="<?=htmlspecialchars($path)?>">
                        <button><?= $t['delete_button'] ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <?php endif; ?>
    </div>
    <div style="display:flex; flex-wrap: wrap; text-align: left; gap: 20px; margin-top: 20px;">
    <div id="form" style="flex: 1 1 500px;">
    <h2><?= $t['admin_configuration'] ?></h2>
    <?php if (!empty($config_message)) echo "<p style='color:green'>$config_message</p>"; ?>
        <?php if (!empty($config_message)): ?>
        <script>
        setTimeout(() => location.reload(), 1500);
        </script>
        <?php endif; ?>
        <form method="post">
            <label style="display:block; margin-bottom:24px;"><?= $t['lang_title'] ?>:</label>
            <select name="lang_default" style="width:100%;">
                <?php foreach(['de','en','fr','it'] as $lg): ?>
                <option value="<?= $lg ?>" <?= ($configData['lang_default']??'de')===$lg?'selected':'' ?>>⇅ <?= strtoupper($lg) ?></option>
                <?php endforeach; ?>
            </select>
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['timezone_title'] ?>:</label>
            <select name="timezone" style="width:100%; padding:10px;">
                <?php
                $currentTimezone = $configData['timezone'] ?? 'Europe/Zurich';
                foreach (DateTimeZone::listIdentifiers() as $tz) {
                    $selected = ($tz === $currentTimezone) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($tz) . "\" $selected>⇅ $tz</option>";
                }
                ?>
            </select>

            <?php
            $boolKeys = [
                'valid_once'     => $t['valid_once'],
                'valid_1h'       => $t['valid_1h'],
                'valid_3h'       => $t['valid_3h'],
                'valid_6h'       => $t['valid_6h'],
                'valid_12h'      => $t['valid_12h'],
                'valid_1d'       => $t['valid_1d'],
                'valid_3d'       => $t['valid_3d'],
                'valid_7d'       => $t['valid_7d'],
                'valid_14d'      => $t['valid_14d'],
                'valid_30d'      => $t['valid_30d'],
                'valid_forever'  => $t['valid_forever'],
                'only_upload'    => $t['only_upload_mode'],
                'send_email'     => $t['send_email_mode'],
                'pwzip'          => $t['pwzip_mode'],
                ];
            ?>

            
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['values_title'] ?>:</label>
            <?php foreach ($boolKeys as $key => $desc): ?>
            <label style="display:flex; text-align:left; align-items:center; gap:10px; margin-bottom:10px;">
                <input type="checkbox" name="<?= $key ?>" value="1" <?= !empty($configData[$key]) ? 'checked' : '' ?>>
                <span><strong><?= htmlspecialchars($key) ?></strong> – <?= htmlspecialchars($desc) ?></span>
            </label>
            <?php endforeach; ?>
            <button type="submit" style="margin-top:20px; margin-bottom: 30px;" name="config_update" value="1"><?= $t['configuration_save_button'] ?></button>
        </form>
    </div>
    <div id="form" style="flex: 1 1 500px;">
    <h2><?= $t['smtp_configuration'] ?></h2>
    <?php if (!empty($env_message)) echo "<p style='color:green'>$env_message</p>"; ?>
        <?php if (!empty($env_message)): ?>
        <script>
        setTimeout(() => location.reload(), 1500);
        </script>
        <?php endif; ?>
        <form method="post">
            <label style="display:block; margin-bottom:20px;"><?= $t['smtphost_title'] ?>:</label>
            <input type="text" name="SMTP_HOST" placeholder="<?= $t['smtphost_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_HOST'] ?? '') ?>">
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['smtpport_title'] ?>:</label>
            <input type="text" name="SMTP_PORT" placeholder="<?= $t['smtpport_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_PORT'] ?? '') ?>">
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['smtpuser_title'] ?>:</label>
            <input type="text" name="SMTP_USER" placeholder="<?= $t['smtpuser_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_USER'] ?? '') ?>">
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['smtppass_title'] ?>:</label>
            <input type="text" name="SMTP_PASS" placeholder="<?= $t['smtppass_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_PASS'] ?? '') ?>">
            <button type="submit" style="margin-top:10px; margin-bottom: 30px;" name="env_update" value="1"><?= $t['smtp_save_button'] ?></button>
        </form>
        </div>
        </div>
        <div style="margin-top:20px; margin-bottom: 100px;">
        <form method="get" style="text-align:center">
            <input type="hidden" name="logout" value="1">
            <button><?= $t['logout_button'] ?></button>
        </form>
        </div>
    </div>
    </div>
<footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
<!-- START: Invisible form not needed in the admin panel because of main.js-->
<form id="uploadForm" style="display:none;" aria-hidden="true" novalidate>
  <input type="file" id="fileInput" style="display:none;">
  <div id="dropzone" style="display:none;"></div>
  <div id="selectedFile" style="display:none;"></div>
</form>
<!-- END: Invisible form not needed in the admin panel because of main.js-->
<script src="js/main.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {

  const TEXT_SHOW_UPLOADS = "<?= addslashes($t['show_uploads_button']) ?>";
  const TEXT_HIDE_UPLOADS = "<?= addslashes($t['hide_uploads_button']) ?>";
  const toggleBtn = document.getElementById('toggleTableBtn');
  const tableSection = document.getElementById('tableSection');

  if (toggleBtn && tableSection) {
    if (localStorage.getItem('showEntries') === 'true') {
      tableSection.style.display = 'block';
      toggleBtn.textContent = TEXT_HIDE_UPLOADS;
    } else {
      tableSection.style.display = 'none';
      toggleBtn.textContent = TEXT_SHOW_UPLOADS;
    }

    toggleBtn.addEventListener('click', () => {
      const isVisible = tableSection.style.display === 'block';
      tableSection.style.display = isVisible ? 'none' : 'block';
      toggleBtn.textContent = isVisible ? TEXT_SHOW_UPLOADS : TEXT_HIDE_UPLOADS;
      localStorage.setItem('showEntries', !isVisible);
    });
  }

  const liveSearch = document.getElementById('liveSearch');
  if (liveSearch) {
    liveSearch.addEventListener('input', function() {
      const term = this.value.toLowerCase();

      document.querySelectorAll('#tableSection table').forEach(table => {
        const rows = table.querySelectorAll('tr:not(thead tr)');
        const thead = table.querySelector('thead');
        let hasMatch = false;

        rows.forEach(tr => {
          const text = tr.innerText.trim().toLowerCase();

          if (text === '') {
            tr.style.display = 'none';
            return;
          }

          const match = text.includes(term);
          tr.style.display = match ? '' : 'none';
          if (match) hasMatch = true;
        });

        if (thead) thead.style.display = hasMatch ? '' : 'none';
        table.style.display = hasMatch ? '' : 'none';
      });
    });
  }

});
</script>
</body>
</html>
