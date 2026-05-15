<?php
// Minimaler all-inkl PHP-Mailhandler. Vor Go-live Zieladresse und Datenschutztext prüfen.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed');
}

function clean($key) {
  return trim(strip_tags($_POST[$key] ?? ''));
}

$name = clean('name');
$email = clean('email');
$telefon = clean('telefon');
$auszahlung = clean('auszahlung');
$kategorie = clean('kategorie');
$uebergabe = clean('uebergabe');
$beschreibung = clean('beschreibung');

if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  exit('Bitte Name und gültige E-Mail angeben.');
}

$to = 'info@steine-ankauf.de';
$subject = 'Neue Ankauf-Anfrage über steine-ankauf.de';
$body = "Name: $name\nE-Mail: $email\nTelefon: $telefon\nAuszahlung: $auszahlung\nKategorie: $kategorie\nÜbergabe: $uebergabe\n\nBeschreibung:\n$beschreibung\n";
$headers = "From: steine-ankauf.de <no-reply@steine-ankauf.de>\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8\r\n";

if (mail($to, $subject, $body, $headers)) {
  header('Location: /danke/');
} else {
  http_response_code(500);
  echo 'E-Mail konnte nicht gesendet werden.';
}
