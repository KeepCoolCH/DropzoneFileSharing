<?php
/* Dropzone File Sharing
   Developed by Kevin Tobler
   www.kevintobler.ch
*/

if (isset($_GET['t'])) {
    require 'download.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'upload.php';
    exit;
}

require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['share_files'] ?></title>
    <link rel="stylesheet" href="inc/style.css">
    <script>
    const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body>
    <h2><?= $t['title'] ?> - <?= $t['share_files'] ?></h2>

    <form method="post" enctype="multipart/form-data" id="uploadForm">
        <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
          <span id="flag-de" title="German" onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
		  <span id="flag-en" title="English" onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
		  <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
		  <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
        </div>
        <div id="dropzone"><?= $t['drag_files'] ?></div>
        <div id="selectedFile" style="margin-top:10px; font-style: italic; color: #555;"></div>
        <input type="hidden" name="paths" id="paths">
        <input type="file" name="file[]" id="fileInput" multiple required style="display:none;"><br>
        <?php if (Config::$default['onlyupload']): ?>
        <input type="hidden" name="pw" value="">
    	<input type="hidden" name="mode" value="forever">
        <button type="submit"><?= $t['upload_button_onlyupload'] ?></button>
		<?php else: ?>
		<input type="password" name="pw" placeholder="<?= $t['password_optional'] ?>" class="password-input"><br>
        <select name="mode">
		<?php
		$validOptions = [
			'1h', '3h', '6h', '12h', '1d', '3d', '7d', '14d', '30d', 'forever'
		];
		
		foreach ($validOptions as $opt) {
			if (!empty(Config::$default["valid_$opt"])) {
				echo "<option value=\"$opt\">{$t["valid_$opt"]}</option>";
			}
		}
		?>
        </select>
        <br><br>
        <button type="submit"><?= $t['upload_button'] ?></button>
		<?php endif; ?>
        <progress id="progressBar" value="0" max="100" style="width:100%; display:none; margin-top:20px;"></progress>
        <div id="progressText" style="margin-top:5px; display:none;"></div>
        <div id="uploadStatusText" style="margin-top:5px; display:none;"></div>
    </form>

	<p></p>
	
	<footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
    <script src="js/main.js"></script>
</body>
</html>
