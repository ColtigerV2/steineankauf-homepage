<?php
require_once __DIR__ . '/mailer.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
function respond(int $status, string $message, ?string $caseId = null): void {
    http_response_code($status);
    echo json_encode(['message' => $message, 'case_id' => $caseId], JSON_UNESCAPED_UNICODE);
    exit;
}
function clean(string $key, int $maxLength = 2000): string {
    $value = $_POST[$key] ?? '';
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value, 'UTF-8') > $maxLength) respond(400, 'Bitte prüfe die Eingaben.');
    return trim(str_replace(["\r", "\0"], '', strip_tags($value)));
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST'); respond(405, 'Bitte verwende das Ankauf-Formular.');
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 20000) respond(413, 'Die Anfrage ist zu groß.');
if (clean('website', 200) !== '') respond(400, 'Die Anfrage konnte nicht verarbeitet werden.');
$name = clean('name', 120);
$email = clean('email', 180);
$agb = clean('agb', 20);
if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) respond(400, 'Bitte Name und gültige E-Mail angeben.');
if ($agb !== 'ja') respond(400, 'Bitte bestätige Volljährigkeit und Ankaufbedingungen.');
$fields = [];
foreach (['telefon' => 80, 'adresse' => 250, 'auszahlung' => 80, 'kontoinhaber' => 120, 'paypal' => 180, 'iban' => 80, 'kategorie' => 120, 'menge' => 160, 'beschreibung' => 4000, 'uebergabe' => 80, 'pakete' => 80] as $key => $max) $fields[$key] = clean($key, $max);
$choices = [
    'auszahlung' => ['PayPal', 'Banküberweisung', 'Noch offen'],
    'kategorie' => ['LEGO Kiloware', 'DUPLO', 'Sets / Sammlung', 'Minifiguren', 'Bauanleitungen / Kartons', 'Gemischt', 'Händlerposten / großer Bestand'],
    'uebergabe' => ['Versand', 'Ladenabgabe', 'Noch offen']
];
foreach ($choices as $key => $allowed) if (!in_array($fields[$key], $allowed, true)) respond(400, 'Bitte wähle die Formularoptionen erneut aus.');
$submittedId = clean('case_id', 80);
$caseId = preg_match('/^SA-[0-9]{8}-[0-9]{4}-[a-f0-9]{32}$/D', $submittedId) ? $submittedId : 'SA-' . date('Ymd-Hi') . '-' . bin2hex(random_bytes(16));
$configPath = getenv('STEINE_MAIL_CONFIG') ?: dirname($_SERVER['DOCUMENT_ROOT']) . '/steine-ankauf-mail.php';
if (!is_file($configPath) || !is_file(__DIR__ . '/vendor/autoload.php')) respond(503, 'Der Formularversand ist noch nicht eingerichtet. Bitte kontaktiere service@steine-ankauf.de.');
$config = require $configPath;
foreach (['host', 'username', 'password', 'from', 'rate_dir', 'rate_secret'] as $key) {
    if (!isset($config[$key]) || !is_string($config[$key]) || $config[$key] === '') respond(503, 'Der Formularversand ist derzeit nicht verfügbar.');
}
if (!filter_var($config['from'], FILTER_VALIDATE_EMAIL) || strlen($config['rate_secret']) < 32) respond(503, 'Der Formularversand ist derzeit nicht verfügbar.');
$config['to'] = 'service@steine-ankauf.de';
// Rate records contain keyed IP hashes and request hashes, never submitted values.
if (!is_dir($config['rate_dir']) && !mkdir($config['rate_dir'], 0700, true)) respond(503, 'Die Anfrage konnte nicht verarbeitet werden.');
$key = hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', $config['rate_secret']);
$ratePath = $config['rate_dir'] . '/sa-' . $key . '.json';
$handle = fopen($ratePath, 'c+');
if (!$handle || !flock($handle, LOCK_EX)) respond(503, 'Die Anfrage konnte nicht verarbeitet werden.');
chmod($ratePath, 0600);
$state = json_decode(stream_get_contents($handle), true);
$now = time();
if (!is_array($state) || ($state['since'] ?? 0) < $now - 3600) $state = ['since' => $now, 'count' => 0, 'sent' => []];
$fingerprint = hash('sha256', json_encode([$name, $email, $fields]));
if (isset($state['sent'][$caseId]) && hash_equals($state['sent'][$caseId], $fingerprint)) {
    flock($handle, LOCK_UN); fclose($handle);
    respond(200, 'Diese Anfrage wurde bereits übertragen.', $caseId);
}
if (isset($state['sent'][$caseId])) $caseId = 'SA-' . date('Ymd-Hi') . '-' . bin2hex(random_bytes(16));
if ($state['count'] >= 5) {
    flock($handle, LOCK_UN); fclose($handle);
    header('Retry-After: 3600'); respond(429, 'Zu viele Anfragen. Bitte versuche es in einer Stunde erneut.');
}
$state['count']++;
function save_state($handle, array $state): void {
    rewind($handle); ftruncate($handle, 0);
    fwrite($handle, json_encode($state)); fflush($handle);
}
save_state($handle, $state);
$body = "Neue Ankauf-Anfrage\nVorgangsnummer: " . $caseId . "\nZeitpunkt: " . date('c') . "\nName: " . $name . "\nE-Mail: " . $email . "\n";
foreach ($fields as $label => $value) $body .= $label . ': ' . ($value ?: '—') . "\n";
$body .= "Volljährigkeit und Ankaufbedingungen: bestätigt\n";
try {
    send_smtp($config, $config['to'], 'Neue Ankauf-Anfrage ' . $caseId, $body, $email);
    $state['sent'][$caseId] = $fingerprint;
    save_state($handle, $state);
} catch (Throwable $error) {
    error_log('STEINE-ANKAUF: SMTP-Anfrage fehlgeschlagen; Vorgang ' . $caseId);
    flock($handle, LOCK_UN); fclose($handle);
    respond(503, 'Deine Anfrage konnte nicht übertragen werden.');
}
flock($handle, LOCK_UN); fclose($handle);
$confirmationSent = true;
try {
    send_smtp($config, $email, 'Deine Ankauf-Anfrage ' . $caseId, "Danke für deine Anfrage bei STEINE-ANKAUF.de.\nVorgangsnummer: " . $caseId . "\nBitte drucke den Begleitschein auf der Website und lege ihn unterschrieben oben ins Paket.\nVersandadresse: Im Winkel 4, 51399 Burscheid.\nLaden: Hauptstraße 58, 51399 Burscheid.\nEine Anfrage ist noch keine verbindliche Ankaufzusage. Bei besonderen Sammlungen bitte vor dem Versand Kontakt aufnehmen.\nKontakt: service@steine-ankauf.de\n", $config['to']);
} catch (Throwable $error) {
    $confirmationSent = false;
    error_log('STEINE-ANKAUF: Bestätigung fehlgeschlagen; Vorgang ' . $caseId);
}
// Remove expired rate records when the endpoint is used; idle cleanup is scheduled on the host.
foreach (glob($config['rate_dir'] . '/sa-*.json') ?: [] as $old) {
    if (filemtime($old) < $now - 3600) @unlink($old);
}
respond(200, $confirmationSent ? 'Deine Anfrage wurde an den Mailserver übergeben. Eine Bestätigung wurde ebenfalls versendet.' : 'Deine Anfrage wurde übertragen, die Bestätigungs-E-Mail konnte jedoch nicht versendet werden.', $caseId);
