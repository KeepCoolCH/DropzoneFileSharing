<?php
require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

loadEnv($envDir . '/.env');

$smtpHost = getenv('SMTP_HOST');
$smtpPort = getenv('SMTP_PORT');
$smtpUser = getenv('SMTP_USER');
$smtpPass = getenv('SMTP_PASS');
$from = $smtpUser;
$secretKey = 'YOUR_SECRET_KEY'; // Must be identical to upload.php

$emailEncrypted = $_GET['email'] ?? '';
$tokenEncrypted = $_GET['token'] ?? '';

$email = decrypt($emailEncrypted, $secretKey);
$token = decrypt($tokenEncrypted, $secretKey);

$verificationMessage = '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $verificationMessage = "<div>{$t['verification_link_error']}</div>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($verificationMessage)) {
    if (!file_exists($dataFile)) {
        $verificationMessage = "<div>{$t['verification_database_error']}</div>";
    } else {
        $fileData = json_decode(file_get_contents($dataFile), true);

        if (!isset($fileData[$token])) {
            $verificationMessage = "<div>{$t['verification_token_error']}</div>";
        } elseif (!isset($fileData[$token]['uploader_email']) || $fileData[$token]['uploader_email'] !== $email) {
            $verificationMessage = "<div>{$t['verification_email_error']}</div>";
        } elseif (!empty($fileData[$token]['verified'])) {
            $verificationMessage = "<div>{$t['verification_verified_error']}</div>";
        } else {
            // Alles OK – weiter
            $link = $fileData[$token]['link'] ?? '';
            $uploader = $fileData[$token]['uploader_email'] ?? '';
            $recipient = $fileData[$token]['recipient_email'] ?? '';

            $recipients = is_array($recipient) ? $recipient : preg_split('/[\s,;]+/', $recipient);
            $validRecipients = array_filter($recipients, fn($r) => filter_var(trim($r), FILTER_VALIDATE_EMAIL));

            if (empty($link) || empty($validRecipients)) {
                $verificationMessage = "<div class='error'>❌ {$t['verification_recipient_error']}</div>";
            } else {
				$mode = $fileData[$token]['mode'] ?? 'once';
				$validText = $t["valid_$mode"] ?? $mode;
                $subject = "{$t['title']} - {$t['sent_title_recipient']}";
                $message = "<html><body>
                {$t['sent_message_recipient1']}
                {$uploader}
                {$t['sent_message_recipient2']}
                {$t['title']}
                <p><a href='$link'>$link</a></p>
				<p>$validText</p>
                </body></html>";

                foreach ($validRecipients as $r) {
                    $success = sendSMTPMail(trim($r), $subject, $message, $from, $smtpHost, $smtpPort, $smtpUser, $smtpPass);
                    if (!$success) {
                        $verificationMessage = "<div class='error'>{$t['email_error']} $r</div>";
                        break;
                    }
                }

                if (empty($verificationMessage)) {
                    $fileData[$token]['verified'] = true;
                    file_put_contents($dataFile, json_encode($fileData, JSON_PRETTY_PRINT));
                    $verificationMessage = "<div>{$t['verification_success']}</div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $t['title'] ?> - <?= $t['verification_title'] ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <logoimg><a href="index.php?lang=<?= $lang ?>"><img src="img/logo.png" alt="Dropzone Logo" width="300"></a></logoimg>
    <div id="main">
    <div id="form">
    <form method="post">
        <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
            <span id="flag-de" title="German" onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
            <span id="flag-en" title="English" onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
            <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
            <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
        </div>
        <h2><?= $t['verification_title'] ?></h2>
        <div><?= $t['verification_text'] ?></div><br><br>
        <input type="hidden" name="email" value="<?= htmlspecialchars($emailEncrypted) ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($tokenEncrypted) ?>">
        <button type="submit"><?= $t['verification_button'] ?></button>
    </form>
    <br><br>
    <?php if (!empty($verificationMessage)): ?>
		<?= $verificationMessage ?><br><br>
	<?php endif; ?>
	</div>
    </div>
	<footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
    <script src="js/main.js"></script>
</body>
</html>
