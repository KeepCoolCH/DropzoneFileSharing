<?php

declare(strict_types=0);

session_name('DropzoneAdminSession');
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => isset($_SERVER['HTTPS']),
  'cookie_samesite' => 'Lax',
]);

require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

if (!empty(Config::$default['timezone'])) {
    date_default_timezone_set(Config::$default['timezone']);
}

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? $defaultLang);
$_SESSION['lang'] = $lang;

define('ADMIN_JSON', $incDir . '/.admin.json');
define('UPLOADS_DIR', $uploadDir);
define('FILEDATA_JSON', $dataFile);

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
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.2" />
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

        <?php if($notice): ?><p style="color:green"><?=$notice?></p><?php endif; ?>

        <?php if(empty($entries)): ?>
            <p><?= $t['noentry'] ?></p>
        <?php else: ?>
        <table>
            <tr>
                <th style="padding: 10px;"><?= $t['th_file'] ?></th><th style="padding: 10px;"><?= $t['th_size'] ?></th><th style="padding: 10px;"><?= $t['th_uploaddate'] ?></th><th style="padding: 10px;"><?= $t['th_expirationdate'] ?></th><th style="padding: 10px; min-width: 180px;"><?= $t['th_mode'] ?></th><th style="padding: 10px;"><?= $t['th_status'] ?></th><th style="padding: 10px; min-width: 180px;"><?= $t['th_password'] ?></th><th style="padding: 10px;"><?= $t['th_email'] ?></th><th style="padding: 10px; min-width: 180px;"><?= $t['th_actions'] ?></th>
            </tr>

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
            <tr>
                <td><?=htmlspecialchars($name)?><br><br><?=htmlspecialchars($e['link'])?></td>
                <td><?=$size?></td>
                <td><?=$upl?></td>
                <td><?=$end?></td>
                <td>
                <form method="post" style="margin:10px;">
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
                    <button type="submit" style="margin-top:-10px"><?= $t['save_button'] ?></button>
                </form>
                </td>
                <td><?=($used?'used ':'')?><?=($ver?'verified':'not verified')?></td>
                <td>
                    <div style="margin:10px;">
                        <?=$pw?$t['password_set']:$t['password_notset']?>
                    </div>
                    <form method="post" style="margin:10px">
                        <input type="hidden" name="password_path" value="<?=htmlspecialchars($path)?>">
                        <input type="text" name="new_password" placeholder="<?= $t['password_notice'] ?>">
                        <button style="margin-top:-10px"><?= $t['save_button'] ?></button>
                    </form>
                </td>
                <td>
                    <?php if($up): ?><div><?= $t['uploader'] ?>: <?=htmlspecialchars($up)?></div><?php endif; ?><br>
                    <?php if(!empty($rec)): ?><div><?= $t['recipient'] ?>: <?=htmlspecialchars(implode(', ',$rec))?></div><?php endif; ?>
                </td>
                <td>
                    <?php if($abs && is_file($abs)): ?>
                        <form method="get" action="admin_download.php" style="margin: 10px;">
                            <input type="hidden" name="path" value="<?=htmlspecialchars($path)?>">
                            <button class="btn"><?= $t['download_button'] ?></button>
                        </form>
                    <?php endif; ?>
                    <form method="post" style="margin: 10px;" onsubmit="return confirm('<?= htmlspecialchars($t['delete_confirm'], ENT_QUOTES) ?>')">
                        <input type="hidden" name="delete_path" value="<?=htmlspecialchars($path)?>">
                        <button><?= $t['delete_button'] ?></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        <br>
        <form method="get" style="text-align:center">
            <input type="hidden" name="logout" value="1">
            <button><?= $t['logout_button'] ?></button>
        </form>
    </div>
    </div>
    <footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
    <script src="js/main.js"></script>
</body>
</html>
