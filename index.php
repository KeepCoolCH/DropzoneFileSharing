<?php
/* Dropzone File Sharing
   Developed by Kevin Tobler
   www.kevintobler.ch
*/

$isHttps =
  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
  || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

session_name('DropzoneUserSession');
session_start([
  'cookie_httponly' => true,
  'cookie_secure' => $isHttps,
  'cookie_samesite' => 'Lax',
]);

require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? $defaultLang);
if (!isset($_GET['lang']) && isset($_SESSION['lang']) && $_SESSION['lang'] !== $defaultLang) {
    $lang = $defaultLang;
}
$_SESSION['lang'] = $lang;

if (isset($_GET['t'])) {
    require 'download.php';
    exit;
}

if (Config::$default['user_upload']):
define('USER_JSON', $incDir . '/.users.json');
$users = read_json(USER_JSON, []);
if (empty($users)) {
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['user_login'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body class="main">
<div id="form">
    <logoimg><a href="index.php?lang=<?= $lang ?>"><img src="img/logo.png" alt="Dropzone File Sharing" width="300"></a></logoimg>

    <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
        <span id="flag-de" title="German" onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
        <span id="flag-en" title="English" onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
        <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
        <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
    </div>
    <h2><?= $t['user_login'] ?></h2>
    <?= $t['user_register'] ?>
    <br><br>
</div>
<footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
<script>
function changeLang(lang) {
    const url = new URL(window.location);
    url.searchParams.set('lang', lang);
    window.location = url.toString();
}
</script>
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

    header('Location: index.php?lang=' . urlencode($lang));
    exit;
}

if(empty($_SESSION['logged_in'])){
    if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['login'])){
        $u=trim($_POST['username']??''); $p=$_POST['password']??'';
        $ok = false;
        foreach ($users as $user) {
            if (isset($user['username'], $user['password_hash']) && hash_equals($user['username'], $u) && password_verify($p, $user['password_hash'])) {
                $ok = true;
                break;
            }
        }
        if ($ok) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user'] = $u;
            header('Location: index.php?lang=' . urlencode($lang));
            exit;
        } else {
            $err = $t['login_error'];
        }
    }
?>
<!doctype html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['user_login'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body class="main">
<div id="form">
    <logoimg><a href="index.php?lang=<?= $lang ?>"><img src="img/logo.png" alt="Dropzone File Sharing" width="300"></a></logoimg>

    <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
        <span id="flag-de" title="German" onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
        <span id="flag-en" title="English" onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
        <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
        <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
    </div>
    <h2><?= $t['user_login'] ?></h2>
    <?php if(!empty($err)) echo "<p style='color:red'>$err</p>"; ?>
    <form method="post">
        <input type="hidden" name="login" value="1">
        <input type="text" name="username" placeholder="<?= $t['username'] ?>" required><br>
        <input type="text" name="password" placeholder="<?= $t['password'] ?>" autocomplete="current-password" inputmode="text" spellcheck="false" style="-webkit-text-security: disc;"><br>
        <button><?= $t['login_button'] ?></button>
    </form>
</div>
<footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
<script>
function changeLang(lang) {
    const url = new URL(window.location);
    url.searchParams.set('lang', lang);
    window.location = url.toString();
}
</script>
</body>
</html>
<?php exit;
}
endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'upload.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['share_files'] ?></title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body>
    <logoimg><a href="index.php?lang=<?= $lang ?>"><img src="img/logo.png" alt="Dropzone File Sharing" width="300"></a></logoimg>
    <div id="main">
    <div id="form">
    <form method="post" enctype="multipart/form-data" id="uploadForm">
        <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
          <span id="flag-de" title="German" onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
        <span id="flag-en" title="English" onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
        <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
        <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
        </div>

        <div id="dropzone"><?= $t['drag_files'] ?></div>
        
        <input type="hidden" name="paths" id="paths">
        <input type="file" name="file[]" id="fileInput" multiple required style="display:none;"><br>
        <?php if (Config::$default['only_upload']): ?>
        <input type="hidden" name="pw" value="">
        <input type="hidden" name="mode" value="forever">
        <input type="hidden" name="uploader_email" value="">
        <input type="hidden" name="recipient_email" value="">
        <?php else: ?>
        <input type="text" name="pw" placeholder="<?= $t['password_optional'] ?>" class="password-input"><br>
        <select name="mode">
        <?php
        $validOptions = [
            'once', '1h', '3h', '6h', '12h', '1d', '3d', '7d', '14d', '30d', 'forever'
        ];
        
        foreach ($validOptions as $opt) {
            if (!empty(Config::$default["valid_$opt"])) {
                echo "<option value=\"$opt\">⇅ {$t["valid_$opt"]}</option>";
            }
        }
        ?>
        </select>
        <?php if (Config::$default['send_email']): ?>
        <select id="mailChoice" name="mailChoice">
            <option value="yes">⇅ <?= $t['send_email_yes'] ?></option>
            <option value="no">⇅ <?= $t['send_email_no'] ?></option>
        </select>
        <div id="emailWrapper"></div>
        <?php else: ?>
        <input type="hidden" name="uploader_email" value="">
        <input type="hidden" name="recipient_email" value="">
        <?php endif; ?>
        <br><br>
        <?php if (!Config::$default['only_upload'] && !Config::$default['send_email']): ?>
        <br>
        <button type="submit" id="btnDefault"><?= $t['upload_button'] ?></button>
        <?php endif; ?>
        <?php if (!Config::$default['only_upload'] && Config::$default['send_email']): ?>
        <button type="submit" id="btnNormal"><?= $t['upload_button'] ?></button>
        <button type="submit" id="btnEmail" style="display:none;"><?= $t['upload_button_send_email'] ?></button>
        <?php endif; ?>
        <?php endif; ?>
        <?php if (Config::$default['only_upload']): ?>
        <br>
        <button type="submit" id="btnDefault"><?= $t['upload_button_only_upload'] ?></button>
        <?php endif; ?>
        <progress id="progressBar" value="0" max="100" style="width:100%; display:none; margin-top:50px;"></progress>
        <div id="progressText" style="margin-top:5px; display:none;"></div>
        <div id="uploadStatusText" style="margin-top:5px; display:none;"></div>
    </form>
    <div id="uploadResult"></div>
    </div>
    <div id="selectedFile"></div>
    </div>
    <?php if (Config::$default['user_upload']): ?>
    <div style="width:500px; margin-top:20px; margin-bottom: 100px;">
        <form method="get" style="text-align:center">
            <input type="hidden" name="logout" value="1">
            <button><?= $t['logout_button'] ?></button>
        </form>
    </div>
    <?php endif; ?>
    <footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
    <script src="js/main.js"></script>
    
    <script>
    function renderEmailFields(wrapper) {
        wrapper.innerHTML = `
        <input type="email" name="uploader_email" id="uploader_email" placeholder="<?= $t['uploader_email'] ?>" required class="email-input">
        <input type="text"  name="recipient_email" id="recipient_email" placeholder="<?= $t['recipient_email'] ?>" required class="email-input" style="margin-bottom:10px;">
        `;
    }
    
    function updateUI(choice) {
      const wrapper    = document.getElementById('emailWrapper');
      const btnNormal  = document.getElementById('btnNormal');
      const btnEmail   = document.getElementById('btnEmail');
      const btnDefault = document.getElementById('btnDefault');

      if (!wrapper) return;

      if (choice === 'yes') {
        renderEmailFields(wrapper);
        if (btnNormal)  btnNormal.style.display  = 'none';
        if (btnEmail)   btnEmail.style.display   = 'inline-block';
        if (btnDefault) btnDefault.style.display = 'none';
      } else {
        wrapper.innerHTML = '<input type="hidden" name="uploader_email" value=""><input type="hidden" name="recipient_email" value="">';
        if (btnNormal)  btnNormal.style.display  = 'inline-block';
        if (btnEmail)   btnEmail.style.display   = 'none';
        if (btnDefault) btnDefault.style.display = 'inline-block';
      }
    }

    const mailChoiceEl = document.getElementById('mailChoice');
    if (mailChoiceEl) {
      mailChoiceEl.addEventListener('change', function () {
        updateUI(this.value);
      });

      document.addEventListener('DOMContentLoaded', function () {
        updateUI(mailChoiceEl.value);
      });
    }

    function disableUploadButtons() {
      const btnNormal  = document.getElementById('btnNormal');
      const btnEmail   = document.getElementById('btnEmail');
      const btnDefault = document.getElementById('btnDefault');

      const disableStyle = (btn) => {
        btn.disabled = true;
        btn.textContent = '<?= $t['uploading'] ?>';
        btn.style.backgroundColor = '#ccc';
        btn.style.color = '#666';
        btn.style.cursor = 'not-allowed';
      };

      if (btnNormal)  disableStyle(btnNormal);
      if (btnEmail)   disableStyle(btnEmail);
      if (btnDefault) disableStyle(btnDefault);
    }

    document.getElementById('uploadForm').addEventListener('submit', function () {
      disableUploadButtons();
    });
    </script>
</body>
</html>
