<?php
// all-inkl PHP-Mailhandler fuer das Ankauf-Formular.
// Vor Go-live Zieladresse und Absenderdomain final pruefen.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed');
}

function clean(string $key, int $maxLength = 2000): string {
  $value = trim((string)($_POST[$key] ?? ''));
  $value = str_replace(["\r", "\0"], '', $value);
  $value = strip_tags($value);
  if (mb_strlen($value, 'UTF-8') > $maxLength) {
    $value = mb_substr($value, 0, $maxLength, 'UTF-8');
  }
  return $value;
}

function mail_line(string $label, string $value): string {
  return $label . ': ' . ($value !== '' ? $value : '-') . "\n";
}

// Honeypot gegen einfache Bots. Das passende Feld ist im Formular visuell versteckt.
if (clean('website', 200) !== '') {
  header('Location: /danke/');
  exit;
}

$name = clean('name', 120);
$email = clean('email', 180);
$telefon = clean('telefon', 80);
$adresse = clean('adresse', 250);
$auszahlung = clean('auszahlung', 80);
$kontoinhaber = clean('kontoinhaber', 120);
$paypal = clean('paypal', 180);
$iban = clean('iban', 80);
$kategorie = clean('kategorie', 120);
$menge = clean('menge', 160);
$beschreibung = clean('beschreibung', 4000);
$uebergabe = clean('uebergabe', 80);
$pakete = clean('pakete', 80);
$agb = clean('agb', 20);
$submittedCaseId = clean('case_id', 40);

if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit('Bitte Name und gueltige E-Mail angeben.');
}

if (preg_match('/^SA-[0-9]{8}-[0-9]{4}$/', $submittedCaseId)) {
  $caseId = $submittedCaseId;
} else {
  $caseId = 'SA-' . date('Ymd-Hi');
}

$to = 'info@steine-ankauf.de';
$subject = 'Neue Ankauf-Anfrage ' . $caseId . ' ueber steine-ankauf.de';

$body = '';
$body .= "Neue Ankauf-Anfrage\n";
$body .= "===================\n\n";
$body .= mail_line('Vorgangsnummer', $caseId);
$body .= mail_line('Zeitpunkt', date('d.m.Y H:i'));
$body .= mail_line('IP-Adresse', $_SERVER['REMOTE_ADDR'] ?? '-');
$body .= "\nKontakt\n-------\n";
$body .= mail_line('Name', $name);
$body .= mail_line('E-Mail', $email);
$body .= mail_line('Telefon', $telefon);
$body .= mail_line('Adresse', $adresse);
$body .= "\nAuszahlung\n----------\n";
$body .= mail_line('Gewuenschte Auszahlung', $auszahlung);
$body .= mail_line('Kontoinhaber', $kontoinhaber);
$body .= mail_line('PayPal', $paypal);
$body .= mail_line('IBAN', $iban);
$body .= "\nWare\n----\n";
$body .= mail_line('Kategorie', $kategorie);
$body .= mail_line('Menge grob', $menge);
$body .= mail_line('Versand / Abgabe', $uebergabe);
$body .= mail_line('Anzahl Pakete', $pakete);
$body .= "\nBeschreibung\n------------\n" . ($beschreibung !== '' ? $beschreibung : '-') . "\n\n";
$body .= "Bestaetigungen\n--------------\n";
$body .= mail_line('Volljaehrigkeit / Ankaufbedingungen bestaetigt', $agb !== '' ? 'Ja' : 'Nicht uebermittelt');

$headers = [];
$headers[] = 'From: steine-ankauf.de <no-reply@steine-ankauf.de>';
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $body, implode("\r\n", $headers))) {
  header('Location: /danke/?vorgang=' . rawurlencode($caseId));
  exit;
}

http_response_code(500);
echo 'E-Mail konnte nicht gesendet werden. Bitte versuche es spaeter erneut oder kontaktiere STEINE-ANKAUF.de direkt.';
