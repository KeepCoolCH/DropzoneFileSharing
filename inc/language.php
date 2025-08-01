<?php
require_once 'inc/config.php';

$defaultLang = Config::$default['lang_default'] ?? 'de';
$lang = strtolower($_POST['lang'] ?? $_GET['lang'] ?? $defaultLang);
if (!in_array($lang, ['de','en','fr','it'])) $lang = $defaultLang;

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
        'valid_once' => "⏳ Nur 1× herunterladbar",
        'valid_1h' => "🕐 1 Stunde gültig",
        'valid_3h' => "🕒 3 Stunden gültig",
        'valid_6h' => "🕕 6 Stunden gültig",
        'valid_12h' => "🕛 12 Stunden gültig",
        'valid_1d' => "📅 1 Tag gültig",
        'valid_3d' => "📅 3 Tage gültig",
        'valid_7d' => "📅 7 Tage gültig",
        'valid_14d' => "📅 14 Tage gültig",
        'valid_30d' => "📅 30 Tage gültig",
        'valid_forever' => "📅 Unbegrenzt gültig",
        'upload_button' => "📤 Hochladen & Link erstellen",
        'upload_button_onlyupload' => "📤 Hochladen",
        'selected_files' => "Ausgewählte Datei",
        'selected_files_plural' => "Ausgewählte Dateien",
        'language' => "Sprache",
        'title' => "Dropzone File Sharing",
        'version' => "V.1.6",
        'footer_text' => "© 2025 von Kevin Tobler - <a href='https://kevintobler.ch' target='_blank'>www.kevintobler.ch</a>",
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
        'valid_once' => "⏳ Downloadable only 1x",
        'valid_1h' => "🕐 Valid for 1 hour",
        'valid_3h' => "🕒 Valid for 3 hours",
        'valid_6h' => "🕕 Valid for 6 hours",
        'valid_12h' => "🕛 Valid for 12 hours",
        'valid_1d' => "📅 Valid for 1 day",
        'valid_3d' => "📅 Valid for 3 days",
        'valid_7d' => "📅 Valid for 7 days",
        'valid_14d' => "📅 Valid for 14 days",
        'valid_30d' => "📅 Valid for 30 days",
        'valid_forever' => "📅 Valid forever",
        'upload_button' => "📤 Upload & create link",
        'upload_button_onlyupload' => "📤 Upload",
        'selected_files' => "Selected file",
        'selected_files_plural' => "Selected files",
        'language' => "Language",
        'title' => "Dropzone File Sharing",
        'version' => "V.1.6",
        'footer_text' => "© 2025 by Kevin Tobler - <a href='https://kevintobler.ch' target='_blank'>www.kevintobler.ch</a>",
    ],
    'fr' => [
	'link_not_found' => "❌ Le lien n'a pas été trouvé ou il est déjà utilisé.",
	'file_missing' => "❌ Le fichier n'existe pas.",
	'password_required' => "🔐 Un mot de passe est attendu",
	'enter_password' => "Donnez un mot de passe",
	'start_download' => "📥 Commencer le téléchargement",
	'no_file_selected' => "❌ Aucun fichier de sélectionné.",
	'upload_error' => "❌ Erreur d'envoi'.",
	'upload_text' => "⬆️ Envoi",
	'upload_success' => "✅ Envoi terminé",
	'creating_zip' => "⏳ Création du fichier ZIP...",
	'temp_dir_error' => "❌ Impossible de créer le répertoire temporaire.",
	'error_upload_file' => "❌ Erreur d'envoi de fichier ",
	'wrong_password' => "❌ Mot de passe incorrect",
	'your_link' => "Votre lien:",
	'copy' => "📋 Copier",
	'copied' => "✅ Copié!",
	'share_files' => "📤 Partager des fichiers",
	'drag_files' => "📂 Déposer des fichiers ou cliquer ici",
	'password_optional' => "🔐 Mot de passe (optionel)",
	'valid_once' => "⏳ Téléchargeable seulement 1x",
	'valid_1h' => "🕐 Valide pour 1 heure",
	'valid_3h' => "🕒 Valide pour 3 heures",
	'valid_6h' => "🕕 Valide pour 6 heures",
	'valid_12h' => "🕛 Valide pour 12 heures",
	'valid_1d' => "📅 Valide pour 1 jour",
	'valid_3d' => "📅 Valide pour 3 jours",
	'valid_7d' => "📅 Valide pour 7 jours",
	'valid_14d' => "📅 Valide pour 14 jours",
	'valid_30d' => "📅 Valide pour 30 jours",
	'valid_forever' => "📅 Valide indéfiniment",
	'upload_button' => "📤 Envoyer & créer le lien",
	'upload_button_onlyupload' => "📤 Envoyer",
	'selected_files' => "Selectionner fichier",
	'selected_files_plural' => "Selectionner plusieurs fichiers",
	'language' => "Langage",
	'title' => "Dropzone File Sharing",
	'version' => "V.1.6",
	'footer_text' => "© 2025 par Kevin Tobler - <a href='https://kevintobler.ch' target='_blank'>www.kevintobler.ch</a>",
	],
	'it' => [
	'link_not_found' => "❌ Link non trovato o già utilizzato.",
	'file_missing' => "❌ Il file non è più disponibile.",
	'password_required' => "🔐 È richiesta una password",
	'enter_password' => "Inserisci la password",
	'start_download' => "📥 Avvia download",
	'no_file_selected' => "❌ Nessun file selezionato.",
	'upload_error' => "❌ Errore durante il caricamento.",
	'upload_text' => "⬆️ Carica",
	'upload_success' => "✅ Caricamento completato",
	'creating_zip' => "⏳ Creazione dell'archivio ZIP in corso...",
	'temp_dir_error' => "❌ Impossibile creare la cartella temporanea.",
	'error_upload_file' => "❌ Errore nel caricamento del file ",
	'wrong_password' => "❌ Password errata",
	'your_link' => "Il tuo link:",
	'copy' => "📋 Copia",
	'copied' => "✅ Copiato!",
	'share_files' => "📤 Condividi file",
	'drag_files' => "📂 Trascina i file qui o clicca",
	'password_optional' => "🔐 Password (opzionale)",
	'valid_once' => "⏳ Scaricabile solo una volta",
	'valid_1h' => "🕐 Valido per 1 ora",
	'valid_3h' => "🕒 Valido per 3 ore",
	'valid_6h' => "🕕 Valido per 6 ore",
	'valid_12h' => "🕛 Valido per 12 ore",
	'valid_1d' => "📅 Valido per 1 giorno",
	'valid_3d' => "📅 Valido per 3 giorni",
	'valid_7d' => "📅 Valido per 7 giorni",
	'valid_14d' => "📅 Valido per 14 giorni",
	'valid_30d' => "📅 Valido per 30 giorni",
	'valid_forever' => "📅 Valido indefinitamente",
	'upload_button' => "📤 Carica & crea link",
	'upload_button_onlyupload' => "📤 Carica",
	'selected_files' => "File selezionato",
	'selected_files_plural' => "File selezionati",
	'language' => "Lingua",
	'title' => "Dropzone File Sharing",
	'version' => "V.1.6",
	'footer_text' => "© 2025 di Kevin Tobler - <a href='https://kevintobler.ch' target='_blank'>www.kevintobler.ch</a>",
	]
];

// Use translations
$t = $T[$lang];
