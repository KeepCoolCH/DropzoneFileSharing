<?php

/* Dropzone File Sharing V.1.1
made by Kevin Tobler
www.kevintobler.ch
*/

// Language default 'de'
$lang = strtolower($_GET['lang'] ?? 'de');
if (!in_array($lang, ['de','en'])) $lang = 'de';

// Translations
$T = [
    'de' => [
        'link_not_found' => "❌ Link nicht gefunden oder bereits benutzt.",
        'file_missing' => "❌ Datei nicht mehr vorhanden.",
        'password_required' => "🔐 Passwort erforderlich",
        'enter_password' => "Passwort eingeben",
        'start_download' => "📥 Download starten",
        'no_file_selected' => "❌ Keine Datei ausgewählt.",
        'upload_error' => "❌ Fehler beim Hochladen der Datei.",
        'temp_dir_error' => "❌ Konnte temporäres Verzeichnis nicht anlegen.",
        'error_upload_file' => "❌ Fehler beim Hochladen der Datei ",
        'wrong_password' => "❌ Falsches Passwort",
        'your_link' => "Dein Link:",
        'copy' => "📋 Kopieren",
        'copied' => "✅ Kopiert!",
        'share_files' => "📤 Dateien teilen",
        'drag_files' => "📂 Dateien hierher ziehen oder klicken",
        'password_optional' => "🔐 Passwort (optional)",
        'download_once' => "⏳ Nur 1× herunterladbar",
        'valid_1h' => "🕐 1 Stunde gültig",
        'valid_3h' => "🕒 3 Stunden gültig",
        'valid_1d' => "📅 1 Tag gültig",
        'valid_3d' => "📅 3 Tage gültig",
        'valid_7d' => "📅 7 Tage gültig",
        'upload_button' => "📤 Hochladen & Link erstellen",
        'selected_files' => "Ausgewählte Datei",
        'selected_files_plural' => "Ausgewählte Dateien",
        'language' => "Sprache",
        'title' => "Dropzone File Sharing",
    ],
    'en' => [
        'link_not_found' => "❌ Link not found or already used.",
        'file_missing' => "❌ File no longer available.",
        'password_required' => "🔐 Password required",
        'enter_password' => "Enter password",
        'start_download' => "📥 Start download",
        'no_file_selected' => "❌ No file selected.",
        'upload_error' => "❌ Error uploading the file.",
        'temp_dir_error' => "❌ Could not create temporary directory.",
        'error_upload_file' => "❌ Error uploading file ",
        'wrong_password' => "❌ Incorrect password",
        'your_link' => "Your link:",
        'copy' => "📋 Copy",
        'copied' => "✅ Copied!",
        'share_files' => "📤 Share files",
        'drag_files' => "📂 Drag files here or click",
        'password_optional' => "🔐 Password (optional)",
        'download_once' => "⏳ Downloadable only 1x",
        'valid_1h' => "🕐 Valid for 1 hour",
        'valid_3h' => "🕒 Valid for 3 hours",
        'valid_1d' => "📅 Valid for 1 day",
        'valid_3d' => "📅 Valid for 3 days",
        'valid_7d' => "📅 Valid for 7 days",
        'upload_button' => "📤 Upload & create link",
        'selected_files' => "Selected file",
        'selected_files_plural' => "Selected files",
        'language' => "Language",
        'title' => "Dropzone File Sharing",
    ]
];

// Use translations
$t = $T[$lang];

$footer = '<footer>' . $t['title'] . ' V.1.1 © 2025 by Kevin Tobler - <a href="https://kevintobler.ch" target="_blank">www.kevintobler.ch</a></footer>';
							
$css_style = '<style>
        body {
        	font-family: sans-serif;
        	background: #f8f8f8;
        	padding: 40px;
        	text-align: center;
        	margin: 0;
        }
        
        input[type=file], input[type=password], select {
        	padding: 10px;
        	width: 100%;
        	box-sizing: border-box;
        	margin-bottom: 10px;
        	border: 1px solid #ccc;
			border-radius: 5px;
			font-size: 1em;
        }
		
		form {
			background: white;
			display: inline-block;
			padding: 20px;
			border-radius: 10px;
			box-shadow: 0 0 10px #aaa;
			max-width: 500px;
			min-width: 400px;
		}
		
        .password-input {
            max-width: 100%;
            margin: 0px auto 10px auto;
            display: block;
            box-sizing: border-box;
        }
        
        p {
        	margin-top: 20px;
        }
        
        h2 {
			margin-top: 30px;
			margin-bottom: 20px;
		}
        
        footer {
			position: fixed;
			bottom: 0;
			left: 0;
			width: 100%;
			background-color: #ffffff;
			text-align: center;
			margin: 150px 0px 0px 0px;
			padding: 15px;
		}

        #dropzone {
            border: 2px dashed #aaa;
            padding: 40px;
            background: #f9f9f9;
            color: #777;
            margin-top: 20px;
            cursor: pointer;
            box-sizing: border-box;
        }

        #dropzone.dragover {
            background: #e0ffe0;
            border-color: #4caf50;
            color: #000;
        }
        
        /* Spinner style */
        #spinner {
            display: none;
            margin: 20px auto 0 auto;
            border: 6px solid #f3f3f3;
            border-top: 6px solid #4caf50;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Language selector style */
        #languageFlags {
			max-width: 500px;
			margin: 0 auto 20px auto;
			text-align: center;
		}
		
		#languageFlags img {
			width: 32px;
			height: 21px;
			cursor: pointer;
			margin: 0 10px;
			border: 2px solid transparent;
			border-radius: 4px;
			vertical-align: middle;
			transition: border-color 0.3s ease;
		}
		
		.selected {
		  opacity: 1 !important;
		  font-weight: bold;
		}

		button {
			padding: 10px 20px;
			font-size: 1em;
			cursor: pointer;
			border: none;
			background-color: #4caf50;
			color: white;
			border-radius: 5px;
			transition: background-color 0.3s ease;
		}
		
		button:hover {
			background-color: #45a049;
		}
		
		a {
		color: #4caf50;
		text-decoration: none;
		}
		
		a:hover {
			text-decoration: none;
			color: #45a049;
		}
    </style>';
    
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) mkdir($uploadDir);

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

$dataFile = $uploadDir . '/.filedata.json';
if (!file_exists($dataFile)) file_put_contents($dataFile, '{}');

$fileData = json_decode(file_get_contents($dataFile), true);
$now = time();

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

// DOWNLOAD
if (isset($_GET['t'])) {
    $token = basename($_GET['t']);
    if (!isset($fileData[$token])) {
        http_response_code(404);
        die($t['link_not_found']);
    }

    $info = $fileData[$token];
    $filePath = $uploadDir . '/' . $info['path'];
    if (!file_exists($filePath)) {
        unset($fileData[$token]);
        file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
        http_response_code(410);
        die($t['file_missing']);
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
				$errorMsg = $pwError ? "<span style='color:red; font-weight:bold;'>{$t['wrong_password']}</span><br><br>" : "";
				echo "<!DOCTYPE html><html lang='$lang'><head><meta charset='UTF-8' name='viewport' content='width=device-width, initial-scale=0.6'><title>{$t['title']} - {$t['password_required']}</title>
				$css_style
				</head><body>
				<h2>{$t['title']} - {$t['password_required']}</h2>
						<form method='post'>
						$errorMsg
							<input type='hidden' name='pw_token' value='" . htmlspecialchars($token) . "'>
							<input type='password' name='pw_input' placeholder='{$t['enter_password']}' required autofocus>
							<br><br>
							<button type='submit'>{$t['start_download']}</button>
						</form>
				$footer
				</body></html>";
				exit;
			}
		}

    if ($info['type'] === 'once') {
        $fileData[$token]['used'] = true;
        file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($info['name']) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// UPLOAD
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
	$originalPaths = json_decode($_POST['paths'] ?? '[]', true);
    $files = $_FILES['file'];
    $mode = $_POST['mode'] ?? 'once';
    $pw = trim($_POST['pw'] ?? '');

    $fileCount = is_array($files['name']) ? count($files['name']) : 0;

    if ($fileCount === 0) {
        $message = $t['no_file_selected'];
    } else {
        $token = bin2hex(random_bytes(8));
        $targetFiles = [];

        if ($fileCount === 1) {
            $name = basename($files['name'][0]);
            $tmp = $files['tmp_name'][0];
            if (is_uploaded_file($tmp)) {
                $safeName = $token . '-' . preg_replace('/[^a-zA-Z0-9._\- äöüÄÖÜß]/u', '_', $name);
                $target = $uploadDir . '/' . $safeName;
                if (move_uploaded_file($tmp, $target)) {
                    // Save only relative path in JSON
                    $targetFiles[] = ['path' => $safeName, 'name' => $name];
                } else {
                    $message = $t['upload_error'];
                }
            }
        } else {
            $tmpDir = $uploadDir . "/$token";
            if (!mkdir($tmpDir) && !is_dir($tmpDir)) {
                $message = $t['temp_dir_error'];
            } else {
                for ($i = 0; $i < $fileCount; $i++) {
				$originalPath = $originalPaths[$i] ?? $files['name'][$i];
				$tmp = $files['tmp_name'][$i];
			
				if (is_uploaded_file($tmp)) {
					$safeRelativePath = preg_replace('/[^a-zA-Z0-9._\-\/ äöüÄÖÜß]/u', '_', $originalPath);
					$tmpTarget = $tmpDir . '/' . $safeRelativePath;
					
					$targetFolder = dirname($tmpTarget);
					if (!is_dir($targetFolder)) {
						mkdir($targetFolder, 0777, true);
					}
			
					if (!move_uploaded_file($tmp, $tmpTarget)) {
						$message = $t['error_upload_file'] . $originalPath . ".";
						break;
					}
                        // Store path relative to uploads folder
					$relativePath = $token . '/' . $safeRelativePath;
					$targetFiles[] = ['path' => $relativePath, 'name' => basename($originalPath)];
                    }
                }
                if ($message === '') {
                    $zipName = "$token.zip";
                    $zipPath = $uploadDir . '/' . $zipName;

                    $cmd = "cd " . escapeshellarg($tmpDir) . " && zip -r " .
					($pw !== '' ? "-P " . escapeshellarg($pw) : "") . " " .
					escapeshellarg($zipPath) . " .";
                    shell_exec($cmd);
                    
                    rrmdir($tmpDir);

                    $targetFiles = [['path' => $zipName, 'name' => $zipName]];
                }
            }
        }

        if ($message === '' && count($targetFiles) === 1) {
            $fileData[$token] = [
                'name' => $targetFiles[0]['name'],
                'path' => $targetFiles[0]['path'],
                'time' => time(),
                'type' => in_array($mode, ['1h','3h','1d','3d','7d']) ? 'time' : 'once',
                'duration' => match($mode) {
                    '1h' => 3600,
                    '3h' => 3 * 3600,
                    '1d' => 86400,
                    '3d' => 3 * 86400,
                    '7d' => 7 * 86400,
                    default => 7 * 86400,
                },
                'used' => false,
                'password' => $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null
            ];
            file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
            $script = basename($_SERVER['PHP_SELF']);
			$basePath = dirname($_SERVER['PHP_SELF']);
			if ($basePath === '/' || $basePath === '\\') $basePath = '';
			if ($script === 'index.php') {
				$path = "$basePath/";
			} else {
				$path = "$basePath/$script";
			}
			$link = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $path . "?lang=$lang&t=$token";
            $message = $t['your_link'] . " 
                <a id='link' href='$link' target='_blank'>$link</a>
                <button onclick='copyLink()'>{$t['copy']}</button>
                <span id='copied' style='color:green; display:none;'>{$t['copied']}</span>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['share_files'] ?></title>
    <?php echo $css_style; ?>
</head>
<body>
    <h2><?= $t['title'] ?> - <?= $t['share_files'] ?></h2>

    <form method="post" enctype="multipart/form-data" id="uploadForm">
		<div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
		  <span id="flag-de" title="Deutsch" class="<?= $lang === 'de' ? 'selected' : '' ?>" onclick="changeLang('de')" style="margin-right: 10px;"><?= $lang === 'de' ? '🇩🇪' : '<span style="opacity:0.5;">🇩🇪</span>' ?></span>
		  <span id="flag-en" title="English" class="<?= $lang === 'en' ? 'selected' : '' ?>" onclick="changeLang('en')"><?= $lang === 'en' ? '🇬🇧' : '<span style="opacity:0.5;">🇬🇧</span>' ?></span>
		</div>
        <div id="dropzone"><?= $t['drag_files'] ?></div>
        <div id="selectedFile" style="margin-top:10px; font-style: italic; color: #555;"></div>
        <input type="hidden" name="paths" id="paths">
        <input type="file" name="file[]" id="fileInput" multiple required style="display:none;"><br>
        <input type="password" name="pw" placeholder="<?= $t['password_optional'] ?>" class="password-input"><br>
        <select name="mode">
            <option value="once"><?= $t['download_once'] ?></option>
            <option value="1h"><?= $t['valid_1h'] ?></option>
            <option value="3h"><?= $t['valid_3h'] ?></option>
            <option value="1d"><?= $t['valid_1d'] ?></option>
            <option value="3d"><?= $t['valid_3d'] ?></option>
            <option value="7d"><?= $t['valid_7d'] ?></option>
        </select><br><br>
        <button type="submit"><?= $t['upload_button'] ?></button>
        <div id="spinner"></div>
    </form>

    <p><?= $message ?></p>
    <?php echo $footer; ?>

<script>
function copyLink() {
    const link = document.getElementById("link").href;
    navigator.clipboard.writeText(link).then(() => {
        const copied = document.getElementById("copied");
        copied.style.display = "inline";
        setTimeout(() => copied.style.display = "none", 2000);
    });
}

function changeLang(lang) {
	const url = new URL(window.location);
	url.searchParams.set('lang', lang);
	window.location = url.toString();
}

const form = document.getElementById('uploadForm');
const spinner = document.getElementById('spinner');

form.addEventListener('submit', () => {
    spinner.style.display = 'block';
});

// Dropzone handling
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const selectedFileDiv = document.getElementById('selectedFile');

function updateSelectedFile() {
    if (fileInput.files.length > 0) {
        let names = [];
        for (let i = 0; i < fileInput.files.length; i++) {
            names.push(fileInput.files[i].name);
        }
        selectedFileDiv.textContent = (names.length > 1 ? "<?= $t['selected_files_plural'] ?>" : "<?= $t['selected_files'] ?>") + ": " + names.join(', ');
    } else {
        selectedFileDiv.textContent = "";
    }
}

async function traverseFileTree(item, path = '', fileList = []) {
    path = path || '';
    if (item.isFile) {
        await new Promise((resolve) => {
            item.file(file => {
                // Fügt Pfadname als "fake relative path" hinzu
                const fileWithPath = new File([file], path + file.name, { type: file.type });
                fileList.push(fileWithPath);
                resolve();
            });
        });
    } else if (item.isDirectory) {
        const reader = item.createReader();
        await new Promise((resolve) => {
            reader.readEntries(async entries => {
                for (const entry of entries) {
                    await traverseFileTree(entry, path + item.name + '/', fileList);
                }
                resolve();
            });
        });
    }
}

dropzone.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', updateSelectedFile);

dropzone.addEventListener('dragover', e => {
    e.preventDefault();
    dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', async (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');

    const dtItems = e.dataTransfer.items;
    const dtFiles = e.dataTransfer.files;

    const files = [];

    if (dtItems && dtItems.length > 0) {

        const entriesProcessed = [];

        for (let i = 0; i < dtItems.length; i++) {
            const item = dtItems[i].webkitGetAsEntry?.();
            if (item) {
                entriesProcessed.push(traverseFileTree(item, '', files));
            }
        }

        await Promise.all(entriesProcessed);
    }

    if (files.length === 0 && dtFiles && dtFiles.length > 0) {
        for (let i = 0; i < dtFiles.length; i++) {
            files.push(dtFiles[i]);
        }
    }

    const dataTransfer = new DataTransfer();
    for (const file of files) {
        dataTransfer.items.add(file);
    }

    fileInput.files = dataTransfer.files;

    const paths = files.map(file => file.name);
    document.getElementById('paths').value = JSON.stringify(paths);

    updateSelectedFile();
});

</script>
</body>
</html>
