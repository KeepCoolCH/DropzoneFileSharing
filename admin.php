<?php

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
define('USER_JSON',  $incDir . '/.users.json');
if (!defined('CONFIG_FILE')) {
    define('CONFIG_FILE', $incDir . '/config.php');
}
define('ENV_FILE', $incDir . '/.env/.env');
define('UPLOADS_DIR', $uploadDir);
define('FILEDATA_JSON', $dataFile);

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

    $_SESSION['config_message'] = $t['configuration_save'];
    header('Location: admin.php?lang=' . urlencode($lang));
    exit;
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

$config_message = $_SESSION['config_message'] ?? '';
unset($_SESSION['config_message']);

$env_message = $_SESSION['env_message'] ?? '';
unset($_SESSION['env_message']);

$notice = $_SESSION['notice'] ?? '';
unset($_SESSION['notice']);

$user_message = $_SESSION['user_message'] ?? '';
unset($_SESSION['user_message']);

$admin_message = $_SESSION['admin_message'] ?? '';
unset($_SESSION['admin_message']);

if (isset($_POST['env_update'])) {
    $envData['SMTP_HOST'] = trim($_POST['SMTP_HOST'] ?? '');
    $envData['SMTP_PORT'] = trim($_POST['SMTP_PORT'] ?? '');
    $envData['SMTP_USER'] = trim($_POST['SMTP_USER'] ?? '');
    $envData['SMTP_PASS'] = trim($_POST['SMTP_PASS'] ?? '');
    $envData['SMTP_FROM_ADDRESS']  = trim($_POST['SMTP_FROM_ADDRESS'] ?? '');
    saveEnvFile(ENV_FILE, $envData);

    $_SESSION['env_message'] = $t['smtp_save'];
    header('Location: admin.php?lang=' . urlencode($lang));
    exit;
}

// --- User Management (.user.json) ---

$users = read_json(USER_JSON, []);

// Add / Update / Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add User
    if (isset($_POST['user_add'])) {
        $newName = trim($_POST['user_name'] ?? '');
        $pw1     = $_POST['user_password']  ?? '';
        $pw2     = $_POST['user_password2'] ?? '';

        if ($newName === '' || $pw1 === '') {
            $_SESSION['user_message'] = $t['register_error'];
        } elseif ($pw1 !== $pw2) {
            $_SESSION['user_message'] = $t['password_error'];
        } else {
            foreach ($users as $u) {
                if (hash_equals($u['username'], $newName)) {
                    $_SESSION['user_message'] = $t['user_error_exists'];
                    header('Location: admin.php?lang=' . urlencode($lang));
                    exit;
                }
            }

            $users[] = [
                'username'      => $newName,
                'password_hash' => password_hash($pw1, PASSWORD_DEFAULT),
                'created_at'    => time(),
            ];

            write_json(USER_JSON, $users);
            $_SESSION['user_message'] = $t['user_save'];
        }

        header('Location: admin.php?lang=' . urlencode($lang));
        exit;
    }

    // Update User Password
    if (isset($_POST['user_change_pw'])) {
        $name = trim($_POST['user_name'] ?? '');
        $pw1  = $_POST['user_new_password']  ?? '';
        $pw2  = $_POST['user_new_password2'] ?? '';

        if ($name === '' || $pw1 === '') {
            $_SESSION['user_message'] = $t['register_error'];
        } elseif ($pw1 !== $pw2) {
            $_SESSION['user_message'] = $t['password_error'];
        } else {
            $changed = false;
            foreach ($users as &$u) {
                if (hash_equals($u['username'], $name)) {
                    $u['password_hash'] = password_hash($pw1, PASSWORD_DEFAULT);
                    $changed = true;
                    break;
                }
            }
            unset($u);

            if ($changed) {
                write_json(USER_JSON, $users);
                $_SESSION['user_message'] = $t['password_change'];
            } else {
                $_SESSION['user_message'] = $t['user_error_notfound'];
            }
        }

        header('Location: admin.php?lang=' . urlencode($lang));
        exit;
    }

    // Delete User
    if (isset($_POST['user_delete'])) {
        $name = trim($_POST['user_name'] ?? '');
        $new  = [];

        foreach ($users as $u) {
            if (!hash_equals($u['username'], $name)) {
                $new[] = $u;
            }
        }

        write_json(USER_JSON, $new);
        $_SESSION['user_message'] = $t['entry_delete'];

        header('Location: admin.php?lang=' . urlencode($lang));
        exit;
    }

    // Change Admin account
    if (isset($_POST['admin_update'])) {
        $admin = read_json(ADMIN_JSON, []);

        $newUsername = trim($_POST['admin_username'] ?? '');
        $pw1         = $_POST['admin_password']  ?? '';
        $pw2         = $_POST['admin_password2'] ?? '';

        if ($newUsername === '') {
            $_SESSION['admin_message'] = $t['register_error'];
        } elseif ($pw1 !== '' && $pw1 !== $pw2) {
            $_SESSION['admin_message'] = $t['password_error'];
        } else {
            $admin['username'] = $newUsername;

            if ($pw1 !== '') {
                $admin['password_hash'] = password_hash($pw1, PASSWORD_DEFAULT);
                $_SESSION['admin_message'] = $t['password_change'];
            } else {
                $_SESSION['admin_message'] = $t['admin_save'];
            }

            $admin['created_at'] = time();

            write_json(ADMIN_JSON, $admin);
        }

        header('Location: admin.php?lang=' . urlencode($lang));
        exit;
    }
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
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.6">
    <title><?= $t['title'] ?> - <?= $t['admin_setup'] ?></title>
    <link rel="icon" href="img/favicon.png">
    <link rel="apple-touch-icon" href="img/favicon.png">
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
<script src="js/lang.js"></script>
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.6">
    <title><?= $t['title'] ?> - <?= $t['admin_login'] ?></title>
    <link rel="icon" href="img/favicon.png">
    <link rel="apple-touch-icon" href="img/favicon.png">
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
<script src="js/lang.js"></script>
</body>
</html>
<?php exit;
}

// --- Actions ---
if($_SERVER['REQUEST_METHOD']==='POST'){
    $data=read_json(FILEDATA_JSON,[]);
    if(isset($_POST['delete_path'])){
        $path=$_POST['delete_path']; $new=[];
        foreach ($data as $key => $e) {
        if (($e['path'] ?? '') === $path) { $abs=safe_uploads_join($path); if($abs) @unlink($abs); }
            else $new[$key]=$e;
        }
        write_json(FILEDATA_JSON,$new); $_SESSION['notice'] = $t['entry_delete'];
        header('Location: admin.php?lang=' . urlencode($lang));
        exit;
    }
    elseif(isset($_POST['validity_path'],$_POST['validity_mode'])){
        $path=$_POST['validity_path']; $mode=$_POST['validity_mode'];
        $presets=[
            'once'    => 86400,
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
                if ($mode === 'once') {
                    $e['type'] = 'once';
                    $e['duration'] = $presets[$mode] ?? 86400;
                } else {
                    $e['type'] = 'time';
                    $e['duration'] = $presets[$mode] ?? 86400;
                }
            }
        }
        unset($e);
        write_json(FILEDATA_JSON,$data); $_SESSION['notice'] = $t['mode_change'] . "$mode.";
        header('Location: admin.php?lang=' . urlencode($lang));
        exit;
    }
    elseif(isset($_POST['toggle_verified_path'])){
        $path=$_POST['toggle_verified_path'];
        foreach($data as &$e){ if(($e['path']??'')===$path){ $e['verified']=!empty($e['verified'])?false:true;
            }
        }
        unset($e);
        write_json(FILEDATA_JSON,$data); $_SESSION['notice'] = $t['verification_change'];
        header('Location: admin.php?lang=' . urlencode($lang));
        exit;
    }


    elseif(isset($_POST['password_path'], $_POST['new_password'])){
        $path=$_POST['password_path']; $new=$_POST['new_password'];
        foreach($data as &$e){
            if(($e['path']??'')===$path){
                $e['password']=trim($new)===''?null:password_hash($new,PASSWORD_DEFAULT);
            }
        }
        unset($e);
        write_json(FILEDATA_JSON,$data); $_SESSION['notice'] = $t['password_change'];
        header('Location: admin.php?lang=' . urlencode($lang));
        exit;
    }
}

$entries=read_json(FILEDATA_JSON,[]);
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=0.6">
    <title><?= $t['title'] ?> - <?= $t['admin_panel'] ?></title>
    <link rel="icon" href="img/favicon.png">
    <link rel="apple-touch-icon" href="img/favicon.png">
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
        
        <!-- Upload List -->
        <?php if($notice): ?>
        <p style="color:green"><?= htmlspecialchars($notice) ?></p>
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
            $upl_user=$e['upload_user']??'—';
            if(is_string($rec)) $rec=[$rec];
        ?>
        <div id="tableSection" class="table-responsive" style="display:none;">
        <table>
            <thead>
            <tr>
                <th style="width: 200px;"><?= $t['th_file'] ?></th><th style="width: 60px;"><?= $t['th_size'] ?></th><th style="width: 100px;"><?= $t['th_uploaddate'] ?></th><th style="width: 100px;"><?= $t['th_expirationdate'] ?></th><th><?= $t['th_mode'] ?></th><th style="width: 110px;"><?= $t['th_status'] ?></th><th><?= $t['th_password'] ?></th><th style="min-width: 200px;"><?= $t['th_email'] ?></th><th style="width: 160px;"><?= $t['th_actions'] ?></th>
            </tr>
            </thead>
            <tr>
                <td data-label="<?= $t['th_file'] ?>"><strong><?=htmlspecialchars($name)?></strong><br><br><?=htmlspecialchars($e['link'])?><br><br><?= $t['uploaded_by'] ?>: <i><?=$upl_user?></i></td>
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
                <td data-label="<?= $t['th_status'] ?>"><?=($ver?$t['verified']:$t['notverified'])?><br><br><?= $e['downloads'] ?? 0 ?><br><br><?= tsfmt($e['last_download'] ?? null) ?></td>
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

    <!-- General Configuration -->
    <div id="form" style="flex: 1 1 500px;">
    <h2><?= $t['general_configuration'] ?></h2>
    <?php if (!empty($config_message)) echo "<p style='color:green'>$config_message</p>"; ?>
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
                <label style="display:block; margin-top: 10px; margin-bottom:20px;">
                    <?= $t['admin_email_title'] ?>:
                </label>
                <input type="email" name="admin_email" placeholder="<?= $t['admin_email_title'] ?>" value="<?= htmlspecialchars($configData['admin_email'] ?? '') ?>" style="width:100%;">

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
                'user_upload'    => $t['user_upload_mode'],
                'send_email'     => $t['send_email_mode'],
                'admin_notify'   => $t['admin_notify_mode'],
                'show_dp'        => $t['showdp_mode'],
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

    <!-- SMTP Configuration -->
    <div id="form" style="flex: 1 1 500px;">
    <h2><?= $t['smtp_configuration'] ?></h2>
    <?php if (!empty($env_message)) echo "<p style='color:green'>$env_message</p>"; ?>
        <form method="post">
            <label style="display:block; margin-bottom:20px;"><?= $t['smtphost_title'] ?>:</label>
            <input type="text" name="SMTP_HOST" placeholder="<?= $t['smtphost_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_HOST'] ?? '') ?>">
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['smtpport_title'] ?>:</label>
            <input type="text" name="SMTP_PORT" placeholder="<?= $t['smtpport_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_PORT'] ?? '') ?>">
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['smtpuser_title'] ?>:</label>
            <input type="text" name="SMTP_USER" placeholder="<?= $t['smtpuser_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_USER'] ?? '') ?>">
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['smtppass_title'] ?>:</label>
            <input type="text" name="SMTP_PASS" placeholder="<?= $t['smtppass_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_PASS'] ?? '') ?>">
            <label style="display:block; margin-top: 10px; margin-bottom:20px;"><?= $t['smtpfrom_title'] ?>:</label>
            <input type="text" name="SMTP_FROM_ADDRESS" placeholder="<?= $t['smtpfrom_title'] ?>" value="<?= htmlspecialchars($envData['SMTP_FROM_ADDRESS'] ?? '') ?>">
            <button type="submit" style="margin-top:10px; margin-bottom: 30px;" name="env_update" value="1"><?= $t['smtp_save_button'] ?></button>
        </form>

        <!-- Admin Account Management -->
        <h2><?= $t['admin_configuration'] ?></h2>
        <?php if (!empty($admin_message)) echo "<p style='color:green'>$admin_message</p>"; ?>
        <?php
        $adminData = read_json(ADMIN_JSON, []);
        $adminUsername = $adminData['username'] ?? '';
        $adminCreated  = $adminData['created_at'] ?? null;
        ?>
        
        <label style="display:block; margin-top: 30px; margin-bottom:20px;"><?php if ($adminCreated): ?><?= $t['user_date'] ?>: <?= date('Y-m-d H:i', $adminCreated) ?><?php endif; ?></label>
        
        <form method="post" style="display:flex; flex-wrap:wrap; column-gap:20px; align-items:center;">
            <input type="text" name="admin_username" value="<?= htmlspecialchars($adminUsername) ?>" required placeholder="<?= $t['username'] ?>" style="min-width:180px;">
            <input type="text" name="admin_password" placeholder="<?= $t['password_new'] ?>" style="flex:1; min-width:180px;">
            <input type="text" name="admin_password2" placeholder="<?= $t['password_confirm'] ?>" style="flex:1; min-width:180px;">
            <button type="submit" name="admin_update" value="1" style="margin-top:10px; margin-bottom: 30px;"><?= $t['admin_save_button'] ?></button>
        </form>
        </div>

    <!-- User Management -->
    <div id="form" style="flex: 1 1 500px;">
        <h2><?= $t['user_configuration'] ?>:</h2>
        <?php if (!empty($user_message)) echo "<p style='color:green'>$user_message</p>"; ?>

        <label style="display:block; margin-bottom:20px;"><?= $t['user_existing_title'] ?>:</label>
        <?php
        $users = $users ?? read_json(USER_JSON, []);
        if (empty($users)): ?>
            <p><?= $t['user_empty'] ?></p>
        <?php else: ?>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $t['username'] ?></th>
                    <th><?= $t['user_date'] ?></th>
                    <th><?= $t['th_actions'] ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td>👤 <?= htmlspecialchars($u['username'] ?? '') ?></td>
                    <td>
                        <?php
                        $ct = $u['created_at'] ?? null;
                        echo $ct ? date('Y-m-d H:i', $ct) : '—';
                        ?>
                    </td>
                    <td>
                        <!-- Change Password -->
                        <form method="post" style="display:flex; flex-wrap:wrap; column-gap:20px; align-items:center;">
                            <input type="hidden" name="user_name" value="<?= htmlspecialchars($u['username'] ?? '') ?>">
                            <input type="text" name="user_new_password"  placeholder="<?= $t['password_new'] ?>" style="flex:1; min-width:180px;">
                            <input type="text" name="user_new_password2" placeholder="<?= $t['password_confirm'] ?>" style="flex:1; min-width:180px;">
                            <button type="submit" name="user_change_pw" value="1"><?= $t['save_button'] ?></button>
                        </form>

                        <!-- Delete -->
                        <form method="post" onsubmit="return confirm('<?= htmlspecialchars($t['delete_confirm'], ENT_QUOTES) ?>');">
                            <input type="hidden" name="user_name" value="<?= htmlspecialchars($u['username'] ?? '') ?>">
                            <button type="submit" style="margin-bottom: 22px;" name="user_delete" value="1"><?= $t['delete_button'] ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>

        <label style="display:block; margin-top: 30px; margin-bottom:20px;"><?= $t['user_add_title'] ?>:</label>
        <form method="post" style="display:flex; flex-wrap:wrap; column-gap:20px; align-items:center;">
            <input type="text" name="user_name" placeholder="<?= $t['username'] ?>" required style="min-width:180px;">
            <input type="text" name="user_password" placeholder="<?= $t['password_new'] ?>" required style="flex:1; min-width:180px;">
            <input type="text" name="user_password2" placeholder="<?= $t['password_confirm'] ?>" required style="flex:1; min-width:180px;">
            <button type="submit" style="margin-top:10px; margin-bottom: 30px;" name="user_add" value="1"><?= $t['user_add_button'] ?></button>
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
<script src="js/lang.js"></script>
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
