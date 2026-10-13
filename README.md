# Steine-Ankauf.de – Relaunch
Astro-Entwurf mit GitHub-Pages-Vorschau und separatem Produktionsbuild für all-inkl.

## Entwicklung und Prüfung
```sh
npm install
npm run dev
npm run build
npm test
```
Für die vollständigen Formular-Tests ist PHP 8.2 mit mbstring erforderlich. Tests nutzen einen lokalen SMTP-Testdouble und senden keine echten E-Mails.

## GitHub-Vorschau
Push auf main startet Build, Prüfungen und Pages-Deployment:
https://coltigerv2.github.io/steineankauf-homepage/

Der Build mit GITHUB_PAGES=true ist eine Demo ohne Formularversand und wird für Suchmaschinen auf noindex gesetzt. Der Schalter wird beim Build festgelegt.

## Livebetrieb bei all-inkl
Die Actions erzeugen zusätzlich ein Artefakt `all-inkl-release` mit Produktions-URLs und SMTP-Bibliothek. Die bisherige Domain wird dadurch nicht automatisch verändert.

Für einen lokalen Produktionsbuild:
```sh
composer install --no-dev --prefer-dist
npm run build
```
Dabei GITHUB_PAGES nicht auf true setzen. Den Inhalt von dist/ in das Webroot hochladen. SMTP-Zugangsdaten ausschließlich außerhalb des Webroots hinterlegen; Anleitung in [docs/relaunch-abstimmung.md](docs/relaunch-abstimmung.md).

## Vor dem Domainwechsel
Betreiberangaben, Preise, DUPLO-Preis, Team und rechtliche Texte bestätigen. Verfahren bei Ablehnung/abweichendem Angebot und Rückversandkosten abstimmen. SMTP-Zustellung mit eigener Testadresse prüfen.

Ladenadresse und Versandadresse sind getrennt. Alte .html-Links erhalten Weiterleitungen. Das bestehende Ankauf-PDF bleibt unter seinem bisherigen Pfad verfügbar.
