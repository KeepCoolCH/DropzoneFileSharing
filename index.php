<?php

/* Dropzone File Sharing V1.3
   Developed by Kevin Tobler
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
        'upload_text' => "⬆️ Hochladen",
        'upload_success' => "✅ Hochladen erfolgreich",
        'creating_zip' => "⏳ ZIP wird erstellt...",
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
        'valid_6h' => "🕕 6 Stunden gültig",
        'valid_12h' => "🕛 12 Stunden gültig",
        'valid_1d' => "📅 1 Tag gültig",
        'valid_3d' => "📅 3 Tage gültig",
        'valid_7d' => "📅 7 Tage gültig",
        'valid_14d' => "📅 14 Tage gültig",
        'valid_30d' => "📅 30 Tage gültig",
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
        'upload_text' => "⬆️ Upload",
        'upload_success' => "✅ Upload finished",
        'creating_zip' => "⏳ Creating ZIP...",
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
        'valid_6h' => "🕕 Valid for 6 hours",
        'valid_12h' => "🕛 Valid for 12 hours",
        'valid_1d' => "📅 Valid for 1 day",
        'valid_3d' => "📅 Valid for 3 days",
        'valid_7d' => "📅 Valid for 7 days",
        'valid_14d' => "📅 Valid for 14 days",
        'valid_30d' => "📅 Valid for 30 days",
        'upload_button' => "📤 Upload & create link",
        'selected_files' => "Selected file",
        'selected_files_plural' => "Selected files",
        'language' => "Language",
        'title' => "Dropzone File Sharing",
    ]
];

// Use translations
$t = $T[$lang];

$footer = '<footer>' . $t['title'] . ' V.1.3 © 2025 by Kevin Tobler - <a href="https://kevintobler.ch" target="_blank">www.kevintobler.ch</a></footer>';
							
$css_style = '<style>
        body {
        	font-family: sans-serif;
        	background: #f8f8f8;
        	padding: 40px 40px 120px 40px;
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
			min-width: 500px;
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
        
        progress {
			accent-color: #4caf50;
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

// Create folders
$uploadDir = __DIR__ . '/uploads';
$chunksDir = $uploadDir . '/.chunks';
if (!is_dir($uploadDir)) mkdir($uploadDir);
if (!is_dir($chunksDir)) mkdir($chunksDir, 0777, true);

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

// Generate json-file
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

// Clean up old chunk uploads
$maxAge = 5 * 3600; // 5 Hours
foreach (glob("$chunksDir/*") as $uploadFolder) {
	if (is_dir($uploadFolder) && time() - filemtime($uploadFolder) > $maxAge) {
		rrmdir($uploadFolder);
	}
}

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['chunk'])) {
    $uploadId = $_POST['uploadId'] ?? '';
    $chunkIndex = $_POST['chunkIndex'] ?? '';
    $totalChunks = $_POST['totalChunks'] ?? '';
    $filename = $_POST['name'] ?? '';
    $isLastFile = $_POST['isLastFile'] ?? '';

    $chunkBase = "$chunksDir/$uploadId/" . sha1($filename);
    if (!is_dir($chunkBase)) mkdir($chunkBase, 0777, true);

    $chunkPath = "$chunkBase/chunk_$chunkIndex";
    if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunkPath)) {
        http_response_code(500);
        die('❌ Upload failed');
    }

    file_put_contents("$chunkBase/info.txt", $filename);

    if ($isLastFile === '1') {
        $allComplete = true;
        $fileChunks = [];

        foreach (glob("$chunksDir/$uploadId/*", GLOB_ONLYDIR) as $fileDir) {
            $infoPath = "$fileDir/info.txt";
            $filename = file_exists($infoPath) ? file_get_contents($infoPath) : null;
            if (!$filename) continue;

            $chunks = glob("$fileDir/chunk_*");
            sort($chunks);
            $chunkCount = count($chunks);
            $expected = null;

            if (file_exists($fileDir . '/chunk_0')) {
                $firstChunk = basename($chunks[0]);
                preg_match('/chunk_(\d+)/', end($chunks), $m);
                $expected = isset($m[1]) ? ($m[1] + 1) : null;
            }

            if (!$expected || $chunkCount != $expected) {
                $allComplete = false;
                break;
            }

            $fileChunks[] = ['name' => $filename, 'chunks' => $chunks, 'dir' => $fileDir];
        }

        if ($allComplete) {
            $token = bin2hex(random_bytes(8));
            $tempDir = "$uploadDir/$token";
            mkdir($tempDir, 0777, true);

            foreach ($fileChunks as $item) {
                $relativePath = preg_replace('/[^\w\-\.\/ äöüÄÖÜß]/u', '_', $item['name']);
				$fullPath = "$tempDir/$relativePath";
				
				$dir = dirname($fullPath);
				if (!is_dir($dir)) mkdir($dir, 0777, true);
				
				$fp = fopen($fullPath, 'wb');
                foreach ($item['chunks'] as $chunk) {
                    fwrite($fp, file_get_contents($chunk));
                }
                fclose($fp);
            }

            $zipName = "$token.zip";
            $zipPath = "$uploadDir/$zipName";
            $pw = trim($_POST['pw'] ?? '');
            $cmd = "cd " . escapeshellarg($tempDir) . " && zip -r " .
                ($pw !== '' ? "-P " . escapeshellarg($pw) : "") . " " .
                escapeshellarg($zipPath) . " .";
            shell_exec($cmd);
            rrmdir($tempDir);
            rrmdir("$chunksDir/$uploadId");

            $mode = $_POST['mode'] ?? 'once';
            $fileData[$token] = [
                'name' => $zipName,
                'path' => $zipName,
                'time' => time(),
                'type' => in_array($mode, ['1h','3h','6h','12h','1d','3d','7d','14d','30d']) ? 'time' : 'once',
                'duration' => match($mode) {
                    '1h' => 3600,
                    '3h' => 3 * 3600,
                    '6h' => 6 * 3600,
                    '12h' => 12 * 3600,
                    '1d' => 1 * 24 * 3600,
                    '3d' => 3 * 24 * 3600,
                    '7d' => 7 * 24 * 3600,
                    '14d' => 14 * 24 * 3600,
                    '30d' => 30 * 24 * 3600,
                    default => 30 * 24 * 3600,
                },
                'used' => false,
                'password' => $pw !== '' ? password_hash($pw, PASSWORD_DEFAULT) : null
            ];
            file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));

            $script = basename($_SERVER['PHP_SELF']);
            $basePath = dirname($_SERVER['PHP_SELF']);
            if ($basePath === '/' || $basePath === '\\') $basePath = '';
            $path = ($script === 'index.php') ? "$basePath/" : "$basePath/$script";
            $link = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $path . "?lang=$lang&t=$token";
            echo $t['your_link'] . " 
				<a id='link' href='$link' target='_blank'>$link</a>
				<button onclick='copyLink()'>{$t['copy']}</button>
				<span id='copied' style='color:#4caf50; display:none;'>{$t['copied']}</span>";
			exit;
        }
    }

    http_response_code(200);
    exit;
}

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=0.6" />
    <title><?= $t['title'] ?> - <?= $t['share_files'] ?></title>
    <?php echo $css_style; ?>
<script>
const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
</script>
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
            <option value="6h"><?= $t['valid_6h'] ?></option>
            <option value="12h"><?= $t['valid_12h'] ?></option>
            <option value="1d"><?= $t['valid_1d'] ?></option>
            <option value="3d"><?= $t['valid_3d'] ?></option>
            <option value="7d"><?= $t['valid_7d'] ?></option>
            <option value="14d"><?= $t['valid_14d'] ?></option>
            <option value="30d"><?= $t['valid_30d'] ?></option>
        </select><br><br>
        <button type="submit"><?= $t['upload_button'] ?></button>
        <progress id="progressBar" value="0" max="100" style="width:100%; display:none; margin-top:20px;"></progress>
		<div id="progressText" style="margin-top:5px; display:none;"></div>
		<div id="uploadStatusText" style="margin-top:5px; display:none;"></div>
    </form>

    <p><?= $message ?></p>
    <?php echo $footer; ?>

<script>
let files = [];

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

form.addEventListener('submit', async (e) => {
	e.preventDefault();

	const uploadId = [...crypto.getRandomValues(new Uint8Array(8))].map(b => b.toString(16).padStart(2, '0')).join('');
	const mode = form.querySelector('[name="mode"]').value;
	const pw = form.querySelector('[name="pw"]').value;
	const paths = JSON.parse(document.getElementById('paths').value || '[]');
	const progressBar = document.getElementById('progressBar');
	progressBar.style.display = 'block';
	const progressText = document.getElementById('progressText');
	progressText.style.display = 'block';

	let totalUploaded = 0;
	const totalBytes = files.reduce((sum, file) => sum + file.size, 0);

	for (let i = 0; i < files.length; i++) {
		const file = files[i];
		const chunkSize = 1024 * 1024 * 10; // 10 MB
		const totalChunks = Math.ceil(file.size / chunkSize);
		const rawName = paths[i] || file.name;
		const name = rawName.replace(/^(\.\.[\/\\])+/, '').replace(/^\/+/, '');

		for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
			const chunk = file.slice(chunkIndex * chunkSize, (chunkIndex + 1) * chunkSize);
			const formData = new FormData();
			formData.append('chunk', chunk);
			formData.append('chunkIndex', chunkIndex);
			formData.append('totalChunks', totalChunks);
			formData.append('uploadId', uploadId);
			formData.append('name', name);
			formData.append('pw', pw);
			formData.append('mode', mode);
			const isLastFile = (i === files.length - 1 && chunkIndex === totalChunks - 1) ? '1' : '0';
			formData.append('isLastFile', isLastFile);

			try {
				const response = await fetch('', {
					method: 'POST',
					body: formData
				});

				totalUploaded += chunk.size;
				const percent = Math.min(100, Math.round((totalUploaded / totalBytes) * 100));
				progressBar.value = percent;
				progressText.textContent = t.upload_text + ' ' + percent + '%';
				if (percent >= 90) {
					progressText.style.display = 'none';
					document.getElementById('uploadStatusText').textContent = t.creating_zip;
					document.getElementById('uploadStatusText').style.display = 'block';
				}

				if (isLastFile === '1') {
					if (response.ok) {
						document.getElementById('uploadStatusText').style.display = 'none';
						progressText.textContent = (t.upload_success || 'finished');
						progressText.style.display = 'block';
						progressText.style.color = '#4caf50';
						const html = await response.text();
						document.querySelector('p').innerHTML = html;
					} else {
						alert(t.upload_error);
					}
				}
			} catch (err) {
				alert(t.upload_error);
				progressBar.style.display = 'none';
				progressText.style.display = 'none';
				document.getElementById('uploadStatusText').style.display = 'none';
				return;
			}
		}
	}
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
	if (item.isFile) {
		await new Promise(resolve => {
			item.file(file => {
				const fileWithPath = new File([file], path + file.name, { type: file.type });
				fileList.push(fileWithPath);
				resolve();
			});
		});
	} else if (item.isDirectory) {
		const reader = item.createReader();
		await new Promise(resolve => {
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

fileInput.addEventListener('change', () => {
	files = Array.from(fileInput.files);
	document.getElementById('paths').value = JSON.stringify(files.map(f => f.name));
	updateSelectedFile();
});

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

	files = [];

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

	if (files.length === 0 && dtFiles.length > 0) {
		files = Array.from(dtFiles);
	}

	const dataTransfer = new DataTransfer();
	for (const file of files) {
		dataTransfer.items.add(file);
	}
	fileInput.files = dataTransfer.files;

	document.getElementById('paths').value = JSON.stringify(files.map(f => f.name));
	updateSelectedFile();
});
</script>
</body>
</html>
