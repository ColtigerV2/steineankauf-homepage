import {writeFile} from 'node:fs/promises';
const base = process.env.GITHUB_PAGES === 'true' ? '/steineankauf-homepage/' : '/';
const redirects = {
 'ankauf.html':'#ankauf', 'burscheid.html':'#laden', 'faq.html':'#faq',
 'shop.html':'shop/', 'presse.html':'presse/', 'team.html':'team/',
 'jobs.html':'jobs/', 'links.html':'links/',
 'impressum.html':'impressum/', 'datenschutzinformation.html':'datenschutz/',
 'datenschutz.html':'datenschutz/', 'index.htm':''
};
for (const [old, target] of Object.entries(redirects)) {
 const destination = base + target;
 const canonical = target.startsWith('#') ? base : destination;
 await writeFile(new URL('../dist/' + old, import.meta.url), '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="robots" content="noindex,follow"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="canonical" href="' + canonical + '"><meta http-equiv="refresh" content="0;url=' + destination + '"><title>Seite umgezogen</title></head><body><p>Diese Seite ist umgezogen. <a href="' + destination + '">Zur aktuellen Seite</a></p></body></html>');
}
