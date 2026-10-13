import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFile,stat} from 'node:fs/promises';

const base = process.env.GITHUB_PAGES === 'true' ? '/steineankauf-homepage/' : '/';
const origin = process.env.GITHUB_PAGES === 'true' ? 'https://coltigerv2.github.io' : 'https://www.steine-ankauf.de';
const read = (p) => readFile(new URL('../dist/' + p,import.meta.url),'utf8');
test('Unterseiten, Suchmaschinen-Adressen und alte Links bleiben korrekt',async()=>{
  for (const p of ['impressum','datenschutz','ankaufbedingungen','shop','presse','team','jobs','links']) {
    const html=await read(p+'/index.html');
    assert.ok(html.includes('href="'+origin+base+p+'/"'),p+' canonical');
    assert.ok(html.includes('href="'+base+'#formular"'),p+' navigation');
    assert.ok(html.includes('id="main-content"'),p+' skip link target');
  }
  assert.ok((await read('ankauf.html')).includes('url='+base+'#ankauf'));
  assert.ok((await read('shop.html')).includes('url='+base+'shop/'));
  const home=await read('index.html');
  assert.ok(home.includes('action="'+base+'api/submit.php"'));
  assert.ok(home.includes('Hauptstraße 58'));
  assert.ok(home.includes('Im Winkel 4'));
  assert.ok(home.includes('content="'+(process.env.GITHUB_PAGES==='true'?'noindex,follow':'index,follow')+'"'));
  assert.ok(home.includes(process.env.GITHUB_PAGES === 'true' ? 'Anfrage testen' : 'Anfrage absenden'));
  const pdf=await readFile(new URL('../dist/file/a/97570cf8b17157022.pdf',import.meta.url));
  assert.equal(pdf.subarray(0,4).toString(),'%PDF');
  assert.ok((await stat(new URL('../dist/assets/og-image.png',import.meta.url))).size>1000);
});
