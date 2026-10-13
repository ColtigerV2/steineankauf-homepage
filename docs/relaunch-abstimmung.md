# Vor dem Wechsel auf die echte Domain

Die GitHub-Vorschau ist eine Demo. Die folgenden Betreiberangaben sind aus der bisherigen Website übernommen und mit Pierre vor dem Domainwechsel zu bestätigen:

- Laden: Hauptstraße 58, 51399 Burscheid; Samstag 9–14 Uhr, sonst nach Absprache.
- Versand/Geschäftsanschrift: Im Winkel 4, 51399 Burscheid.
- Telefon, Rechtsform, USt-ID und Teamrollen auf Aktualität prüfen.
- LEGO-Kiloware 6 €/kg, Baupläne/Kartons 3 €/kg; DUPLO-Preis ausdrücklich festlegen.
- Typische Auszahlung binnen 1–3 Tagen und kein sofortiges Bargeld bei Ladenabgabe bestätigen.
- Verfahren bei Ablehnung/abweichendem Angebot und Rückversandkosten festlegen. Die neue Seite erfindet hierzu keine Gebühren oder Vertragsregeln.
- Aktuelle Stellenangebote prüfen; die Jobs-Seite bietet bis dahin eine allgemeine Kontaktmöglichkeit.
- Ankaufbedingungen und Datenschutz an die tatsächlichen Betriebsabläufe anpassen; die rechtliche Prüfung bleibt offen.

## SMTP bei all-inkl einrichten

1. PHP 8.2 oder neuer mit mbstring und OpenSSL bereitstellen.
2. Das Actions-Artefakt `all-inkl-release` enthält den Produktionsbuild inklusive PHPMailer. Alternativ im Projekt `composer install --no-dev --prefer-dist` und `npm run build` ausführen (ohne GITHUB_PAGES=true).
3. Inhalt von dist/ ins Webroot hochladen. Keinen GitHub-Pages-Build auf die Domain kopieren.
4. `smtp-config.php.example` außerhalb des öffentlich erreichbaren Webroots als `steine-ankauf-mail.php` ablegen. Der Standardpfad ist das übergeordnete Verzeichnis des Webroots; alternativ STEINE_MAIL_CONFIG als Server-Umgebungsvariable setzen.
5. SMTP-Host, Benutzer, Passwort und autorisierte Absenderadresse eintragen. Transport: TLS über Port 465. Keine Zugangsdaten in GitHub speichern.
6. Für rate_secret einen zufälligen Wert generieren, beispielsweise `php -r "echo bin2hex(random_bytes(32));"`. Das private rate_dir muss für PHP schreibbar sein.
7. Ablaufende `sa-*.json`-Dateien im privaten Rate-Verzeichnis zusätzlich regelmäßig über einen Hosting-Cronjob entfernen. Die Anwendung entfernt alte Datensätze beim nächsten erfolgreichen Aufruf. Die Datensätze enthalten ausschließlich HMAC-IP-Schlüssel, Zeitfenster, Zähler und Anfrage-Hashes.
8. Mit einer eigenen Testadresse prüfen: Anfrage bei service@steine-ankauf.de, Bestätigung beim Kunden, keine Zahlungsdaten in der Bestätigung, identische Vorgangsnummer auf Begleitschein und Anfrage. SPF/DKIM/DMARC passend zum tatsächlichen Mailanbieter überprüfen.

Der Handler begrenzt Anfragen auf fünf pro IP-Schlüssel und Stunde und verhindert erneutes Senden derselben Anfrage. Erfolgreiche SMTP-Annahme beweist noch keine Zustellung im Posteingang; den Posteingang und Spamordner im Livetest prüfen.

## Alte URLs

Die Apache-Konfiguration leitet alte .html-Adressen dauerhaft um. GitHub Pages erhält HTML-Weiterleitungen als Vorschau-Fallback. Der historische PDF-Link bleibt unter file/a/97570cf8b17157022.pdf bestehen. Bestehende Suchmaschinen-Einträge nach dem Wechsel über Search Console kontrollieren.
