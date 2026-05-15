# Steine-Ankauf.de v2

Erster Astro-Projektstand für die neue Steine-Ankauf-Seite.

## Lokal starten

```bash
npm install
npm run dev
```

## Build

```bash
npm run build
npm run preview
```

## GitHub Pages

1. Repo Settings → Pages → Source: GitHub Actions.
2. Push auf `main` triggert Deployment.
3. Preview-URL: `https://coltigerv2.github.io/steineankauf-homepage/`

## all-inkl Go-live

1. `astro.config.mjs`: `base: '/'`, `site: 'https://www.steine-ankauf.de'`.
2. `npm run build`.
3. Inhalt aus `dist/` per SFTP in das Webroot der Domain hochladen.
4. `public/api/submit.php` Zieladresse prüfen.

## Offene Punkte

- Logo-Datei final einsetzen.
- Ladenbild im Hero ersetzen.
- Datenschutz/Impressum aus Bestand übernehmen und prüfen.
- Formular-Mailadresse final bestätigen.
- PDF-/Begleitschein final designen.
