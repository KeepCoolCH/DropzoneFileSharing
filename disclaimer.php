<?php
$isHttps =
  (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
  || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
  || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

  $host = $_SERVER['HTTP_X_FORWARDED_HOST']
    ?? $_SERVER['HTTP_HOST']
    ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
  $host = preg_replace('/\s.*/', '', $host);

session_name('DropzoneUserSession');
session_start([
  'cookie_httponly' => true,
  'cookie_secure'   => $isHttps,
  'cookie_samesite' => 'Lax',
]);

require_once 'inc/config.php';
require_once 'inc/helpers.php';
require_once 'inc/language.php';

$lang = strtolower($_GET['lang'] ?? ($_SESSION['lang'] ?? $defaultLang));
if (!in_array($lang, ['de','en','fr','it'], true)) $lang = $defaultLang;

if (!isset($_GET['lang']) && isset($_SESSION['lang']) && $_SESSION['lang'] !== $defaultLang) {
  $lang = $defaultLang;
}
$_SESSION['lang'] = $lang;

$LEGAL = [
  'de' => [
    'privacy_title' => '🛡️ Datenschutzerklärung',
    'intro' =>
      'Diese Datenschutzerklärung informiert darüber, wie im Rahmen der Nutzung von Dropzone File Sharing Daten bearbeitet werden. Bei Dropzone File Sharing handelt es sich um ein Open-Source-Projekt von Kevin Tobler, welches öffentlich zur Verfügung gestellt wird:',
    'responsibility_h' => '1. Verantwortung',
    'responsibility_p1' => 'Verantwortlich für die Datenbearbeitung ist:',
    'operator_note' => 'Dies ist eine selbstgehostete Installation und <strong>der Betreiber der Instanz</strong> ist verantwortlich.',
    'general_h' => '2. Allgemeines zur Datenbearbeitung',
    'general_p1' => 'Dropzone File Sharing ermöglicht das zeitlich begrenzte Hochladen, Speichern und Teilen von Dateien über individuelle Download-Links. Dabei werden nur jene Daten bearbeitet, die für den technischen Betrieb zwingend erforderlich sind.',
    'general_p2' => 'Der Betreiber verpflichtet sich keine kommerzielle Auswertung, kein Tracking und keine Weitergabe oder Verkauf von Daten vorzunehmen.',
    'processed_h' => '3. Bearbeitete Daten',
    'uploaded_files_h' => '3.1 Hochgeladene Dateien',
    'uploaded_files_li' => [
      'Inhalte der hochgeladenen Dateien im Rahmen der ZIP-Verarbeitung',
      'Dateiname, Dateigrösse, Upload-Zeitpunkt',
    ],
    'logs_h' => '3.2 Zugriffsdaten (Server-Logs)',
    'logs_li' => [
      'IP-Adresse',
      'Datum und Uhrzeit des Zugriffs',
      'Aufgerufene URL / Token',
    ],
    'logs_p' => 'Diese Daten dienen ausschliesslich der Sicherstellung des Betriebs, der Fehleranalyse und dem Schutz vor Missbrauch.',
    'purpose_h' => '4. Zweck der Datenbearbeitung',
    'purpose_li' => [
      'Bereitstellung der Upload- und Download-Funktion',
      'Sicherstellung der Systemsicherheit',
      'Missbrauchs- und Betrugserkennung',
      'Technische Administration',
    ],
    'legalbasis_h' => '5. Rechtsgrundlage',
    'legalbasis_p' => 'Die Bearbeitung erfolgt im Interesse des Betriebs des Dienstes sowie unter Einwilligung der nutzenden Person (durch aktive Nutzung).',
    'retention_h' => '6. Speicherdauer',
    'retention_p' => 'Hochgeladene Dateien werden automatisch gelöscht, sobald:',
    'retention_li' => [
      'das Ablaufdatum erreicht ist oder',
      'der Download-Link verwendet wurde (bei Einmal-Links) oder',
      'eine manuelle Löschung erfolgt',
    ],
    'logs_retention_p' => 'Server-Logs werden zeitlich begrenzt gespeichert und regelmässig gelöscht.',
    'sharing_h' => '7. Weitergabe von Daten',
    'sharing_p' => 'Der Betreiber verpflichtet sich keine Daten an Dritte weiterzugeben, ausser wenn eine gesetzliche Verpflichtung besteht.',
    'hosting_h' => '8. Hosting-Standort',
    'hosting_p' => 'Der Service erfolgt via Docker Container oder Webserver des Betreibers.',
    'rights_h' => '9. Rechte der betroffenen Personen',
    'rights_p' => 'Betroffene Personen haben insbesondere das Recht auf:',
    'rights_li' => [
      'Auskunft über bearbeitete Daten',
      'Berichtigung unrichtiger Daten',
      'Löschung, sofern keine gesetzliche Pflicht entgegensteht',
    ],
    'rights_p2' => 'Anfragen sind an den jeweiligen Betreiber der Instanz zu richten.',
    'security_h' => '10. Datensicherheit',
    'security_p' => 'Das Tool verwendet angemessene technische und organisatorische Massnahmen, insbesondere:',
    'security_li' => [
      'HTTPS-Verschlüsselung (wenn der Betreiber diese nutzt)',
      'Token-basierte Download-Links',
      'optionale Passwort-Absicherung',
      'zeitlich begrenzte Verfügbarkeit',
    ],
    'changes_h' => '11. Änderungen',
    'changes_p' => 'Diese Datenschutzerklärung kann jederzeit angepasst werden, insbesondere bei technischen oder rechtlichen Änderungen.',
    'date_place' => '24.12.2025',

    'disclaimer_title' => '⚠️ Haftungsausschluss',
    'd1_h' => '1. Haftung für Inhalte',
    'd1_p' => 'Die Nutzung von Dropzone File Sharing erfolgt auf eigene Verantwortung. Der Betreiber übernimmt keine Haftung für die hochgeladenen Inhalte, insbesondere nicht für Urheberrechtsverletzungen sowie rechtswidrige, schädliche oder missbräuchliche Inhalte.',
    'd2_h' => '2. Haftung für Datenverlust',
    'd2_p' => 'Trotz sorgfältiger Umsetzung kann ein vollständiger Schutz vor Datenverlust nicht garantiert werden. Der Betreiber übernimmt keine Haftung für verlorene oder beschädigte Dateien.',
    'd3_h' => '3. Verfügbarkeit',
    'd3_p' => 'Es besteht kein Anspruch auf dauerhafte Verfügbarkeit des Dienstes.',
    'd4_h' => '4. Links / Downloads',
    'd4_p' => 'Download-Links führen zu Inhalten, die von Nutzenden bereitgestellt wurden. Der Betreiber hat keinen Einfluss auf deren Inhalt.',
    'd5_h' => '5. Open-Source / Software-Haftung',
    'd5_p' => 'Dropzone File Sharing wird als Open-Source-Projekt bereitgestellt. Die Bereitstellung erfolgt „wie gesehen“ (AS IS) ohne Garantie auf Fehlerfreiheit oder Eignung für einen bestimmten Zweck.',
    'd6_h' => '6. Anwendbares Recht',
    'd6_p' => 'Es gilt das jeweilige Recht im Land des Betreibers. Gerichtsstand ist - soweit zulässig - der Sitz des Betreibers.',
  ],

  'en' => [
    'privacy_title' => '🛡️ Privacy Policy',
    'intro' =>
      'This privacy policy explains how data is processed when using Dropzone File Sharing. Dropzone File Sharing is an open-source project by Kevin Tobler and is publicly available at:',
    'responsibility_h' => '1. Responsibility',
    'responsibility_p1' => 'Responsible for data processing is:',
    'operator_note' => 'This is a self-hosted installation and <strong>the instance operator</strong> is responsible.',
    'general_h' => '2. General Information on Data Processing',
    'general_p1' => 'Dropzone File Sharing enables time-limited uploading, storing, and sharing of files via individual download links. Only data required for technical operation is processed.',
    'general_p2' => 'The operator undertakes not to perform any commercial analysis, no tracking, and not to share, disclose, or sell any data.',
    'processed_h' => '3. Data Processed',
    'uploaded_files_h' => '3.1 Uploaded Files',
    'uploaded_files_li' => [
      'Contents of uploaded files as part of ZIP processing',
      'File name, file size, upload timestamp',
    ],
    'logs_h' => '3.2 Access Data (Server Logs)',
    'logs_li' => [
      'IP address',
      'Date and time of access',
      'Requested URL / token',
    ],
    'logs_p' => 'This data is used exclusively to ensure operation, analyze errors, and protect against abuse.',
    'purpose_h' => '4. Purpose of Processing',
    'purpose_li' => [
      'Providing upload and download functionality',
      'Ensuring system security',
      'Abuse and fraud detection',
      'Technical administration',
    ],
    'legalbasis_h' => '5. Legal Basis',
    'legalbasis_p' => 'Processing is carried out in the interest of operating the service and based on the user’s consent (by active use).',
    'retention_h' => '6. Retention Period',
    'retention_p' => 'Uploaded files are automatically deleted as soon as:',
    'retention_li' => [
      'the expiry date is reached, or',
      'the download link has been used (for one-time links), or',
      'manual deletion occurs',
    ],
    'logs_retention_p' => 'Server logs are stored for a limited time and deleted regularly.',
    'sharing_h' => '7. Disclosure to Third Parties',
    'sharing_p' => 'The operator undertakes not to disclose any data to third parties, unless there is a legal obligation to do so.',
    'hosting_h' => '8. Hosting Location',
    'hosting_p' => 'The service is operated via a Docker container or the operator’s web server.',
    'rights_h' => '9. Rights of Data Subjects',
    'rights_p' => 'Data subjects have in particular the right to:',
    'rights_li' => [
      'Information about processed data',
      'Correction of incorrect data',
      'Deletion, unless legal obligations prevent it',
    ],
    'rights_p2' => 'Requests must be addressed to the respective instance operator.',
    'security_h' => '10. Data Security',
    'security_p' => 'The tool uses appropriate technical and organizational measures, including:',
    'security_li' => [
      'HTTPS encryption (if enabled by the operator)',
      'Token-based download links',
      'optional password protection',
      'time-limited availability',
    ],
    'changes_h' => '11. Changes',
    'changes_p' => 'This privacy policy may be updated at any time, especially in case of technical or legal changes.',
    'date_place' => '24.12.2025',

    'disclaimer_title' => '⚠️ Disclaimer',
    'd1_h' => '1. Liability for Content',
    'd1_p' => 'Use of Dropzone File Sharing is at your own risk. The operator assumes no liability for uploaded content, including copyright infringements as well as illegal, harmful, or abusive content.',
    'd2_h' => '2. Liability for Data Loss',
    'd2_p' => 'Despite careful implementation, complete protection against data loss cannot be guaranteed. The operator assumes no liability for lost or damaged files.',
    'd3_h' => '3. Availability',
    'd3_p' => 'There is no entitlement to continuous availability of the service.',
    'd4_h' => '4. Links / Downloads',
    'd4_p' => 'Download links lead to content provided by users. The operator has no influence over that content.',
    'd5_h' => '5. Open Source / Software Liability',
    'd5_p' => 'Dropzone File Sharing is provided as an open-source project. It is provided “as is” (AS IS) without warranties of any kind, including fitness for a particular purpose or error-free operation.',
    'd6_h' => '6. Applicable Law',
    'd6_p' => 'The applicable law is the law of the operator’s country. The place of jurisdiction is - where legally permissible - the operator’s place of business.',
  ],

  'fr' => [
    'privacy_title' => '🛡️ Politique de confidentialité',
    'intro' =>
      'La présente politique de confidentialité explique comment les données sont traitées dans le cadre de l’utilisation de Dropzone File Sharing. Dropzone File Sharing est un projet open source de Kevin Tobler, mis à disposition publiquement:',
    'responsibility_h' => '1. Responsable du traitement',
    'responsibility_p1' => 'Le responsable du traitement des données est :',
    'operator_note' => 'Il s’agit d’une installation auto-hébergée et <strong>l’exploitant de l’instance</strong> en est responsable.',
    'general_h' => '2. Généralités sur le traitement des données',
    'general_p1' => 'Dropzone File Sharing permet le téléversement, le stockage et le partage de fichiers pour une durée limitée via des liens de téléchargement individuels. Seules les données nécessaires au fonctionnement technique sont traitées.',
    'general_p2' => 'L’exploitant s’engage à ne procéder à aucune exploitation commerciale, à aucun suivi (tracking) et à ne transmettre, divulguer ni vendre des données.',
    'processed_h' => '3. Données traitées',
    'uploaded_files_h' => '3.1 Fichiers téléversés',
    'uploaded_files_li' => [
      'Contenu des fichiers téléversés dans le cadre du traitement ZIP',
      'Nom de fichier, taille, date/heure de téléversement',
    ],
    'logs_h' => '3.2 Données d’accès (logs serveur)',
    'logs_li' => [
      'Adresse IP',
      'Date et heure de l’accès',
      'URL / jeton (token) consulté',
    ],
    'logs_p' => 'Ces données servent exclusivement à assurer le fonctionnement, l’analyse d’erreurs et la protection contre les abus.',
    'purpose_h' => '4. Finalité du traitement',
    'purpose_li' => [
      'Fourniture des fonctions de téléversement et de téléchargement',
      'Sécurité du système',
      'Détection des abus et des fraudes',
      'Administration technique',
    ],
    'legalbasis_h' => '5. Base légale',
    'legalbasis_p' => 'Le traitement est effectué dans l’intérêt de l’exploitation du service ainsi que sur la base du consentement de la personne utilisatrice (par l’utilisation active).',
    'retention_h' => '6. Durée de conservation',
    'retention_p' => 'Les fichiers téléversés sont supprimés automatiquement dès que :',
    'retention_li' => [
      'la date d’expiration est atteinte, ou',
      'le lien de téléchargement est utilisé (liens à usage unique), ou',
      'une suppression manuelle intervient',
    ],
    'logs_retention_p' => 'Les logs serveur sont conservés pour une durée limitée et supprimés régulièrement.',
    'sharing_h' => '7. Transmission des données',
    'sharing_p' => 'L’exploitant s’engage à ne transmettre aucune donnée à des tiers, sauf en cas d’obligation légale.',
    'hosting_h' => '8. Lieu d’hébergement',
    'hosting_p' => 'Le service est exploité via un conteneur Docker ou le serveur web de l’exploitant.',
    'rights_h' => '9. Droits des personnes concernées',
    'rights_p' => 'Les personnes concernées disposent notamment du droit de:',
    'rights_li' => [
      'Accès aux données traitées',
      'Rectification des données inexactes',
      'Suppression, sauf obligation légale contraire',
    ],
    'rights_p2' => 'Les demandes doivent être adressées à l’exploitant de l’instance concernée.',
    'security_h' => '10. Sécurité des données',
    'security_p' => 'L’outil met en œuvre des mesures techniques et organisationnelles appropriées, notamment :',
    'security_li' => [
      'Chiffrement HTTPS (si l’exploitant l’utilise)',
      'Liens de téléchargement basés sur un jeton',
      'protection par mot de passe (optionnelle)',
      'disponibilité limitée dans le temps',
    ],
    'changes_h' => '11. Modifications',
    'changes_p' => 'La présente politique de confidentialité peut être adaptée à tout moment, notamment en cas de modifications techniques ou juridiques.',
    'date_place' => '24.12.2025',

    'disclaimer_title' => '⚠️ Exclusion de responsabilité',
    'd1_h' => '1. Responsabilité pour les contenus',
    'd1_p' => 'L’utilisation de Dropzone File Sharing se fait à vos risques et périls. L’exploitant n’assume aucune responsabilité pour les contenus téléversés, notamment en cas de violation de droits d’auteur ainsi que pour les contenus illégaux, nuisibles ou abusifs.',
    'd2_h' => '2. Responsabilité en cas de perte de données',
    'd2_p' => 'Malgré une mise en œuvre soignée, une protection complète contre la perte de données ne peut être garantie. L’exploitant n’assume aucune responsabilité pour les fichiers perdus ou endommagés.',
    'd3_h' => '3. Disponibilité',
    'd3_p' => 'Aucun droit à une disponibilité permanente du service n’est garanti.',
    'd4_h' => '4. Liens / téléchargements',
    'd4_p' => 'Les liens de téléchargement renvoient à des contenus fournis par les utilisateurs. L’exploitant n’a aucune influence sur leur contenu.',
    'd5_h' => '5. Open source / responsabilité logicielle',
    'd5_p' => 'Dropzone File Sharing est fourni en tant que projet open source. Le logiciel est fourni « tel quel » (AS IS) sans garantie, notamment d’absence d’erreurs ou d’adéquation à un usage particulier.',
    'd6_h' => '6. Droit applicable',
    'd6_p' => 'Le droit applicable est celui du pays de l’exploitant. Le for - dans la mesure où la loi le permet - se situe au siège de l’exploitant.',
  ],

  'it' => [
    'privacy_title' => '🛡️ Informativa sulla privacy',
    'intro' =>
      'La presente informativa spiega come vengono trattati i dati nell’ambito dell’uso di Dropzone File Sharing. Dropzone File Sharing è un progetto open source di Kevin Tobler, reso disponibile pubblicamente:',
    'responsibility_h' => '1. Responsabilità',
    'responsibility_p1' => 'Il titolare del trattamento è:',
    'operator_note' => 'Si tratta di un’installazione self-hosted e <strong>il gestore dell’istanza</strong> è responsabile.',
    'general_h' => '2. Informazioni generali sul trattamento dei dati',
    'general_p1' => 'Dropzone File Sharing consente il caricamento, la conservazione e la condivisione di file per un periodo limitato tramite link di download individuali. Vengono trattati solo i dati necessari al funzionamento tecnico.',
    'general_p2' => 'Il gestore si impegna a non effettuare alcuna analisi commerciale, nessun tracciamento e a non trasmettere né vendere dati.',
    'processed_h' => '3. Dati trattati',
    'uploaded_files_h' => '3.1 File caricati',
    'uploaded_files_li' => [
      'Contenuti dei file caricati nell’ambito dell’elaborazione ZIP',
      'Nome file, dimensione file, data/ora di caricamento',
    ],
    'logs_h' => '3.2 Dati di accesso (log del server)',
    'logs_li' => [
      'Indirizzo IP',
      'Data e ora dell’accesso',
      'URL / token richiesto',
    ],
    'logs_p' => 'Questi dati sono utilizzati esclusivamente per garantire il funzionamento, l’analisi degli errori e la protezione da abusi.',
    'purpose_h' => '4. Finalità del trattamento',
    'purpose_li' => [
      'Fornitura delle funzioni di upload e download',
      'Sicurezza del sistema',
      'Rilevamento di abusi e frodi',
      'Amministrazione tecnica',
    ],
    'legalbasis_h' => '5. Base giuridica',
    'legalbasis_p' => 'Il trattamento avviene nell’interesse dell’esercizio del servizio e sulla base del consenso dell’utente (mediante uso attivo).',
    'retention_h' => '6. Durata di conservazione',
    'retention_p' => 'I file caricati vengono eliminati automaticamente non appena:',
    'retention_li' => [
      'viene raggiunta la data di scadenza, oppure',
      'il link di download viene utilizzato (per link monouso), oppure',
      'avviene una cancellazione manuale',
    ],
    'logs_retention_p' => 'I log del server vengono conservati per un periodo limitato e cancellati regolarmente.',
    'sharing_h' => '7. Comunicazione a terzi',
    'sharing_p' => 'Il gestore si impegna a non comunicare alcun dato a terzi, salvo nei casi in cui sussista un obbligo legale.',
    'hosting_h' => '8. Luogo di hosting',
    'hosting_p' => 'Il servizio è gestito tramite container Docker o il webserver del gestore.',
    'rights_h' => '9. Diritti delle persone interessate',
    'rights_p' => 'Le persone interessate hanno in particolare il diritto di:',
    'rights_li' => [
      'Ottenere informazioni sui dati trattati',
      'Rettifica dei dati inesatti',
      'Cancellazione, salvo obblighi legali contrari',
    ],
    'rights_p2' => 'Le richieste vanno indirizzate al rispettivo gestore dell’istanza.',
    'security_h' => '10. Sicurezza dei dati',
    'security_p' => 'Lo strumento utilizza misure tecniche e organizzative adeguate, in particolare:',
    'security_li' => [
      'Cifratura HTTPS (se utilizzata dal gestore)',
      'Link di download basati su token',
      'protezione con password (opzionale)',
      'disponibilità limitata nel tempo',
    ],
    'changes_h' => '11. Modifiche',
    'changes_p' => 'La presente informativa può essere modificata in qualsiasi momento, in particolare in caso di cambiamenti tecnici o giuridici.',
    'date_place' => '24.12.2025',

    'disclaimer_title' => '⚠️ Esclusione di responsabilità',
    'd1_h' => '1. Responsabilità per i contenuti',
    'd1_p' => 'L’utilizzo di Dropzone File Sharing avviene a proprio rischio. Il gestore non si assume alcuna responsabilità per i contenuti caricati, in particolare per violazioni del diritto d’autore nonché per contenuti illeciti, dannosi o abusivi.',
    'd2_h' => '2. Responsabilità per perdita di dati',
    'd2_p' => 'Nonostante un’implementazione accurata, non è possibile garantire una protezione completa contro la perdita di dati. Il gestore non si assume alcuna responsabilità per file persi o danneggiati.',
    'd3_h' => '3. Disponibilità',
    'd3_p' => 'Non sussiste alcun diritto alla disponibilità continua del servizio.',
    'd4_h' => '4. Link / download',
    'd4_p' => 'I link di download rimandano a contenuti forniti dagli utenti. Il gestore non ha alcuna influenza sul loro contenuto.',
    'd5_h' => '5. Open source / responsabilità software',
    'd5_p' => 'Dropzone File Sharing è fornito come progetto open source. Il software è fornito “così com’è” (AS IS) senza garanzia di assenza di errori o idoneità a uno scopo specifico.',
    'd6_h' => '6. Diritto applicabile',
    'd6_p' => 'Si applica il diritto del Paese del gestore. Il foro competente è - nei limiti consentiti - la sede del gestore.',
  ],
];

$L = $LEGAL[$lang] ?? $LEGAL[$defaultLang];

function render_li(array $items): string {
  $out = '';
  foreach ($items as $it) $out .= '<li>' . $it . '</li>';
  return $out;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=0.6">
  <title><?= htmlspecialchars($t['title']) ?> - <?= htmlspecialchars($t['share_files']) ?></title>
  <link rel="icon" href="img/favicon.png">
  <link rel="apple-touch-icon" href="img/favicon.png">
  <link rel="stylesheet" href="css/style.css">
  <script>
    const t = <?= json_encode($t, JSON_UNESCAPED_UNICODE) ?>;
  </script>
</head>
<body class="main">
  <div id="form">
    <logoimg>
      <a href="index.php?lang=<?= htmlspecialchars($lang) ?>">
        <img src="img/logo.png" alt="Dropzone File Sharing" width="300">
      </a>
    </logoimg>

    <div id="languageFlags" style="font-size: 2em; cursor: pointer; user-select: none;">
      <span id="flag-de" title="German"   onclick="changeLang('de')" style="margin-right: 10px; <?= $lang === 'de' ? '' : 'opacity:0.5;' ?>">🇩🇪</span>
      <span id="flag-en" title="English"  onclick="changeLang('en')" style="margin-right: 10px; <?= $lang === 'en' ? '' : 'opacity:0.5;' ?>">🇬🇧</span>
      <span id="flag-fr" title="Français" onclick="changeLang('fr')" style="margin-right: 10px; <?= $lang === 'fr' ? '' : 'opacity:0.5;' ?>">🇫🇷</span>
      <span id="flag-it" title="Italiano" onclick="changeLang('it')" style="<?= $lang === 'it' ? '' : 'opacity:0.5;' ?>">🇮🇹</span>
    </div>

    <h2><?= htmlspecialchars($t['title']) ?> - <?= htmlspecialchars($t['share_files']) ?></h2>
  </div>

  <div style="display:flex; flex-wrap: wrap; text-align: left; gap: 20px; margin-top: 20px;">

    <!-- Privacy -->
    <div id="form" style="flex: 1 1 500px;">
      <h1><?= $L['privacy_title'] ?></h1>

      <p><?= $L['intro'] ?></p>

      <p>
        🌐 <a href="https://github.com/KeepCoolCH/DropzoneFileSharing" target="_blank" rel="noopener">github.com/KeepCoolCH/DropzoneFileSharing</a><br>
        🌐 <a href="https://hub.docker.com/r/keepcoolch/dropzonefilesharing" target="_blank" rel="noopener">hub.docker.com/keepcoolch/dropzonefilesharing</a>
      </p>

      <h2><?= $L['responsibility_h'] ?></h2>
      <p><?= $L['responsibility_p1'] ?></p>

      <p>
        <?= $host ?>
      </p>

      <p><?= $L['operator_note'] ?></p>

      <h2><?= $L['general_h'] ?></h2>
      <p><?= $L['general_p1'] ?></p>
      <p><?= $L['general_p2'] ?></p>

      <h2><?= $L['processed_h'] ?></h2>

      <h3><?= $L['uploaded_files_h'] ?></h3>
      <ul><?= render_li($L['uploaded_files_li']) ?></ul>

      <h3><?= $L['logs_h'] ?></h3>
      <ul><?= render_li($L['logs_li']) ?></ul>
      <p><?= $L['logs_p'] ?></p>

      <h2><?= $L['purpose_h'] ?></h2>
      <ul><?= render_li($L['purpose_li']) ?></ul>
      <br>
    </div>

    <!-- Privacy continued -->
    <div id="form" style="flex: 1 1 500px;">
      <h2><?= $L['legalbasis_h'] ?></h2>
      <p><?= $L['legalbasis_p'] ?></p>

      <h2><?= $L['retention_h'] ?></h2>
      <p><?= $L['retention_p'] ?></p>
      <ul><?= render_li($L['retention_li']) ?></ul>
      <p><?= $L['logs_retention_p'] ?></p>

      <h2><?= $L['sharing_h'] ?></h2>
      <p><?= $L['sharing_p'] ?></p>

      <h2><?= $L['hosting_h'] ?></h2>
      <p><?= $L['hosting_p'] ?></p>

      <h2><?= $L['rights_h'] ?></h2>
      <p><?= $L['rights_p'] ?></p>
      <ul><?= render_li($L['rights_li']) ?></ul>
      <p><?= $L['rights_p2'] ?></p>

      <h2><?= $L['security_h'] ?></h2>
      <p><?= $L['security_p'] ?></p>
      <ul><?= render_li($L['security_li']) ?></ul>

      <h2><?= $L['changes_h'] ?></h2>
      <p><?= $L['changes_p'] ?></p>

      <p><br><?= $L['date_place'] ?></p>
      <br>
    </div>

    <!-- Disclaimer -->
    <div id="form" style="flex: 1 1 500px;">
      <h1><?= $L['disclaimer_title'] ?></h1>

      <h2><?= $L['d1_h'] ?></h2>
      <p><?= $L['d1_p'] ?></p>

      <h2><?= $L['d2_h'] ?></h2>
      <p><?= $L['d2_p'] ?></p>

      <h2><?= $L['d3_h'] ?></h2>
      <p><?= $L['d3_p'] ?></p>

      <h2><?= $L['d4_h'] ?></h2>
      <p><?= $L['d4_p'] ?></p>

      <h2><?= $L['d5_h'] ?></h2>
      <p><?= $L['d5_p'] ?></p>

      <h2><?= $L['d6_h'] ?></h2>
      <p><?= $L['d6_p'] ?></p>

      <p><br><?= $L['date_place'] ?></p>
      <br>
    </div>

  </div>
  <footer><?= $t['title'] . ' ' . $t['version'] . ' ' . $t['footer_text'] ?></footer>
  <script src="js/lang.js"></script>
</body>
</html>