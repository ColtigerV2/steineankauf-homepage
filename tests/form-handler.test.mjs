import {test} from 'node:test';
import assert from 'node:assert/strict';
import {spawn, spawnSync} from 'node:child_process';
import {mkdtemp, mkdir, copyFile, writeFile, readFile, rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import path from 'node:path';
import {createServer} from 'node:net';

const phpAvailable = !spawnSync('php', ['-v']).error;
test('Formular: Validierung, SMTP-Nachrichten, Wiederholungen und Fehler', {skip: !phpAvailable}, async (t) => {
  const dir = await mkdtemp(path.join(tmpdir(), 'steine-form-test-'));
  const api = path.join(dir, 'public', 'api');
  await mkdir(path.join(api, 'vendor'), {recursive: true});
  for (const name of ['submit.php', 'mailer.php']) await copyFile(new URL('../public/api/' + name, import.meta.url), path.join(api, name));
  const log = path.join(dir, 'messages.jsonl');
  const failure = path.join(dir, 'fail');
  // Test double records messages locally; no network or real email is used.
  await writeFile(path.join(api, 'vendor', 'autoload.php'), `<?php
namespace PHPMailer\\PHPMailer;
#[\\AllowDynamicProperties]
class PHPMailer {
 const ENCRYPTION_SMTPS = 'ssl';
 public function __construct($exceptions) {}
 public function isSMTP() {}
 public function setFrom($from, $name) { $this->from = $from; }
 public function addAddress($to) { $this->to = $to; }
 public function addReplyTo($reply) { $this->reply = $reply; }
 public function send() {
   if (is_file(getenv('MOCKMAIL_FAIL')) && trim(file_get_contents(getenv('MOCKMAIL_FAIL'))) === $this->to) throw new \\RuntimeException('Mock failure');
   file_put_contents(getenv('MOCKMAIL_LOG'), json_encode(['to'=>$this->to,'subject'=>$this->Subject,'body'=>$this->Body,'reply'=>$this->reply ?? null,'tls'=>$this->SMTPSecure,'auth'=>$this->SMTPAuth])."\\n", FILE_APPEND);
 }
}`);
  const config = path.join(dir, 'config.php');
  const quote = (s) => "'" + s.replaceAll('\\', '/').replaceAll("'", "\\'") + "'";
  await writeFile(config, `<?php return ['host'=>'localhost','username'=>'test','password'=>'test','from'=>'sender@example.test','rate_dir'=>${quote(path.join(dir, 'rate'))},'rate_secret'=>'${'a'.repeat(64)}'];`);
  const probe = createServer();
  await new Promise((resolve) => probe.listen(0, '127.0.0.1', resolve));
  const port = probe.address().port;
  await new Promise((resolve) => probe.close(resolve));
  const server = spawn('php', ['-S', '127.0.0.1:' + port, '-t', path.join(dir, 'public')], {env: {...process.env, STEINE_MAIL_CONFIG: config, MOCKMAIL_LOG: log, MOCKMAIL_FAIL: failure}, stdio: ['ignore', 'pipe', 'pipe']});
  const url = 'http://127.0.0.1:' + port + '/api/submit.php';
  try {
    for (let i = 0; i < 50; i++) {
      try {await fetch(url); break;} catch {await new Promise((r) => setTimeout(r, 50));}
    }
    const valid = {name:'Test',email:'customer@example.test',agb:'ja',auszahlung:'Banküberweisung',kategorie:'LEGO Kiloware',uebergabe:'Versand',iban:'TEST-IBAN-PRIVATE',beschreibung:'TEST-BESCHREIBUNG-PRIVATE',case_id:'SA-20261013-1200-' + 'b'.repeat(32)};
    const post = async (change = {}) => {
      const response = await fetch(url, {method:'POST',body:new URLSearchParams({...valid,...change})});
      return {status:response.status,body:await response.json()};
    };
    assert.equal((await fetch(url)).status, 405);
    assert.equal((await post({agb:''})).status, 400);
    assert.equal((await post({email:'test@example.test\nBcc: attacker@example.test'})).status, 400);
    assert.equal((await post({auszahlung:'Bargeld'})).status, 400);
    assert.equal((await post({website:'bot'})).status, 400);
    const arrayInput = new URLSearchParams(valid);
    arrayInput.delete('name'); arrayInput.append('name[]','array');
    assert.equal((await fetch(url,{method:'POST',body:arrayInput})).status,400);
    const accepted = await post();
    assert.equal(accepted.status, 200);
    assert.equal(accepted.body.case_id, valid.case_id);
    const messages = (await readFile(log,'utf8')).trim().split('\n').map(JSON.parse);
    assert.equal(messages.length, 2);
    assert.equal(messages[0].to, 'service@steine-ankauf.de');
    assert.equal(messages[0].reply, valid.email);
    assert.match(messages[0].body, /TEST-IBAN-PRIVATE/);
    assert.equal(messages[1].to, valid.email);
    assert.doesNotMatch(messages[1].body, /TEST-IBAN-PRIVATE|TEST-BESCHREIBUNG-PRIVATE/);
    assert.equal(messages[0].tls, 'ssl');
    assert.equal(messages[0].auth, true);
    assert.equal((await post()).status, 200);
    assert.equal((await readFile(log,'utf8')).trim().split('\n').length, 2, 'Retry must not send duplicate messages');
    const changed = await post({beschreibung:'Geändert'});
    assert.equal(changed.status,200);
    assert.notEqual(changed.body.case_id,valid.case_id,'Reused ID with different contents gets a new ID');
    const generated = await post({case_id:'old-id'});
    assert.match(generated.body.case_id,/^SA-\d{8}-\d{4}-[a-f0-9]{32}$/);
    await writeFile(failure,valid.email);
    const confirmationFailure = await post({case_id:'SA-20261013-1200-'+'c'.repeat(32)});
    assert.equal(confirmationFailure.status,200);
    assert.match(confirmationFailure.body.message,/Bestätigungs-E-Mail konnte/);
    await writeFile(failure,'service@steine-ankauf.de');
    assert.equal((await post({case_id:'SA-20261013-1200-'+'d'.repeat(32)})).status,503);
    assert.equal((await post({case_id:'SA-20261013-1200-'+'e'.repeat(32)})).status,429);
    await rm(config);
    assert.equal((await post()).status,503);
  } finally {
    server.kill();
    await new Promise((resolve) => server.once('exit',resolve));
    await rm(dir,{recursive:true,force:true});
  }
});
