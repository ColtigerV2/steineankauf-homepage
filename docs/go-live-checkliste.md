# STEINE-ANKAUF.de Go-live-Checkliste

Diese Checkliste ist fuer den Umzug von GitHub Pages auf all-inkl gedacht.

## 1. Vor dem Build pruefen

- [ ] Impressum final gegenchecken
- [ ] Datenschutzerklaerung final gegenchecken
- [ ] Telefonnummer ergaenzen oder bewusst weglassen
- [ ] USt-ID / Rechtsform / Kleinunternehmer-Hinweis pruefen
- [ ] Ankaufpreise final von Pierre bestaetigen lassen
- [ ] SMTP-Konfiguration und Zieladresse pruefen: `public/api/submit.php`
- [ ] SMTP-Absender passend zum tatsächlichen Postfach konfigurieren (private Konfiguration)
- [ ] Shop-, Presse- und Social-Links prüfen
- [ ] Bilder lokal im Build vorhanden: `public/assets/photos/`

## 2. Build lokal oder per GitHub Actions erzeugen

```bash
npm install
composer install --no-dev --prefer-dist
npm run build
```

Ergebnisordner:

```text
dist/
```

## 3. Upload zu all-inkl

Den Inhalt von `dist/` in den Webroot der Domain hochladen.

Wichtig: Nicht den Ordner `dist` selbst hochladen, sondern dessen Inhalt.

Typische Dateien/Ordner:

```text
index.html
.htaccess
404.html
api/submit.php
assets/
danke/
datenschutz/
impressum/
```

## 4. Nach Upload testen

- [ ] Startseite laedt
- [ ] Logo sichtbar
- [ ] Bilder sichtbar
- [ ] Impressum erreichbar: `/impressum/`
- [ ] Datenschutz erreichbar: `/datenschutz/`
- [ ] 404-Seite testbar: `/test-404/`
- [ ] Formular Pflichtfelder funktionieren
- [ ] Begleitschein-Druck funktioniert
- [ ] Formular sendet Mail an Pierre
- [ ] Danke-Seite zeigt Vorgangsnummer
- [ ] Testmail landet nicht im Spam
- [ ] Mobile Ansicht pruefen

## 5. Formular-Livetest

Testdaten senden:

```text
Name: Test Ankauf
E-Mail: eigene Testadresse
Kategorie: LEGO Kiloware
Menge: 1 Testkarton
Beschreibung: Go-live Test, bitte ignorieren
```

Erwartung:

- Mail kommt bei `service@steine-ankauf.de` an
- Reply-To zeigt auf die eingegebene Kundenmail
- Vorgangsnummer ist im Betreff und auf Danke-Seite sichtbar

## 6. Nach Go-live

- [ ] Cache im Browser hart leeren
- [ ] Google Search Console / Sitemap spaeter anmelden
- [ ] alte Baukasten-Seite sichern
- [ ] alte Dateien nicht sofort loeschen, sondern Backup behalten
- [ ] nach 24h Formular nochmal testen


## Ergänzungen zum Relaunch

- [ ] docs/relaunch-abstimmung.md mit Pierre durchgehen
- [ ] SMTP-Konfiguration außerhalb des Webroots anlegen
- [ ] Bestätigungs-E-Mail ohne Zahlungsdaten prüfen
- [ ] Alte .html-URLs und den historischen PDF-Link testen
- [ ] Private Rate-Dateien regelmäßig bereinigen
- [ ] Mobile Navigation, Tastaturbedienung und Begleitschein prüfen
- [ ] PageSpeed und Search Console nach Domainwechsel prüfen
