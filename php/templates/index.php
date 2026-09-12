<?php

declare(strict_types=1);

/** @var array<string, string> $countries */
/** @var array<string, string> $types */
/** @var array<string, mixed> $scheme */

$e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PI-SPI QR, démo PHP</title>
<style>
  :root {
    --ground: #ffffff;
    --panel: #f6f7f9;
    --line: #e2e5ea;
    --ink: #14181f;
    --muted: #5d6570;
    --accent: #b8860b;
    --bad: #b4232c;
    --ok: #1a7f4b;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      --ground: #14181f; --panel: #1c222b; --line: #2c3340;
      --ink: #e8ebef; --muted: #9aa3af; --accent: #e0b341;
    }
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; padding: 2rem 1rem; background: var(--ground); color: var(--ink);
    font: 15px/1.55 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  }
  .wrap { max-width: 980px; margin: 0 auto; }
  h1 { font-size: 1.4rem; margin: 0 0 .25rem; }
  .lede { color: var(--muted); margin: 0 0 1.75rem; }
  .cols { display: grid; gap: 1.5rem; grid-template-columns: 1fr; }
  @media (min-width: 820px) { .cols { grid-template-columns: 1fr 360px; } }
  fieldset {
    border: 1px solid var(--line); border-radius: 10px;
    padding: 1.15rem 1.25rem 1.35rem; margin: 0 0 1.25rem; background: var(--panel);
  }
  legend { padding: 0 .45rem; font-weight: 600; font-size: .9rem; }
  label { display: block; font-size: .82rem; color: var(--muted); margin: .8rem 0 .25rem; }
  input, select, textarea {
    width: 100%; padding: .5rem .6rem; font: inherit; color: var(--ink);
    background: var(--ground); border: 1px solid var(--line); border-radius: 6px;
  }
  textarea { font-family: ui-monospace, Consolas, monospace; font-size: .82rem; min-height: 5.5rem; }
  button {
    margin-top: 1rem; padding: .55rem 1.1rem; font: inherit; font-weight: 600;
    color: var(--ground); background: var(--ink); border: 0; border-radius: 6px; cursor: pointer;
  }
  button:hover { opacity: .88; }
  .row { display: grid; gap: .75rem; grid-template-columns: 1fr 1fr; }
  .qr { text-align: center; }
  .qr svg { max-width: 100%; height: auto; }
  pre {
    margin: .5rem 0 0; padding: .7rem .8rem; overflow-x: auto; background: var(--panel);
    border: 1px solid var(--line); border-radius: 6px;
    font-family: ui-monospace, Consolas, monospace; font-size: .78rem; white-space: pre-wrap; word-break: break-all;
  }
  table { width: 100%; border-collapse: collapse; font-size: .84rem; }
  td { padding: .3rem .1rem; border-bottom: 1px solid var(--line); vertical-align: top; }
  td:first-child { color: var(--muted); width: 38%; }
  td:last-child { font-family: ui-monospace, Consolas, monospace; word-break: break-all; }
  .err { color: var(--bad); font-size: .85rem; margin-top: .6rem; }
  .ok { color: var(--ok); }
  .hint { font-size: .78rem; color: var(--muted); margin-top: .35rem; }
  [hidden] { display: none !important; }
</style>
</head>
<body>
<div class="wrap">

  <h1>PI-SPI QR, démo PHP</h1>
  <p class="lede">
    Payload construit et relu avec <code>catidegla/pi-spi-qr</code>, image dessinée avec
    <code>bacon/bacon-qr-code</code>. Schéma <code><?= $e((string) $scheme['gui']) ?></code>,
    devise <code><?= $e((string) $scheme['devise']) ?></code>, exposant <?= (int) $scheme['exposant'] ?>.
  </p>

  <div class="cols">
    <div>
      <form id="gen">
        <fieldset>
          <legend>Générer</legend>

          <div class="row">
            <div>
              <label for="qr_type">Type</label>
              <select id="qr_type" name="qr_type">
                <?php foreach ($types as $key => $label): ?>
                  <option value="<?= $e($key) ?>"<?= $key === 'DYNAMIC' ? ' selected' : '' ?>><?= $e($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="country">Pays</label>
              <select id="country" name="country">
                <?php foreach ($countries as $code => $name): ?>
                  <option value="<?= $e($code) ?>"<?= $code === 'SN' ? ' selected' : '' ?>><?= $e($name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <label for="alias">Alias du bénéficiaire</label>
          <input id="alias" name="alias" value="00000000000000000000000000000000SN01" maxlength="36" required>
          <p class="hint">36 caractères, l'adresse de paiement du bénéficiaire.</p>

          <div class="row">
            <div>
              <label for="amount">Montant, en francs</label>
              <input id="amount" name="amount" inputmode="numeric" value="1500">
              <p class="hint">Entier. Le XOF n'a pas de sous-unité.</p>
            </div>
            <div>
              <label for="reference">Référence</label>
              <input id="reference" name="reference" value="TX-001" maxlength="25">
              <p class="hint">Obligatoire pour un QR dynamique.</p>
            </div>
          </div>

          <button type="submit">Générer</button>
          <p class="err" id="gen-err" hidden></p>
        </fieldset>
      </form>

      <form id="dec">
        <fieldset>
          <legend>Décoder</legend>
          <label for="payload">Payload EMV</label>
          <textarea id="payload" name="payload" placeholder="0002010236..."></textarea>
          <button type="submit">Décoder</button>
          <p class="err" id="dec-err" hidden></p>
        </fieldset>
      </form>
    </div>

    <div>
      <fieldset>
        <legend>Résultat</legend>
        <div class="qr" id="qr"></div>
        <pre id="raw" hidden></pre>
        <table id="fields" hidden></table>
      </fieldset>
    </div>
  </div>
</div>

<script>
const $ = (id) => document.getElementById(id);

function show(data) {
  if (data.svg) $('qr').innerHTML = data.svg;
  if (data.payload) { $('raw').textContent = data.payload; $('raw').hidden = false; $('payload').value = data.payload; }

  const rows = Object.entries(data.decoded || {})
    .filter(([, v]) => v !== null && v !== '' && !(Array.isArray(v) && !v.length))
    .map(([k, v]) => `<tr><td>${k}</td><td>${Array.isArray(v) ? v.join(' ') : v}</td></tr>`);

  if (data.valid === false && (data.problems || []).length) {
    rows.unshift(`<tr><td>problèmes</td><td>${data.problems.join('<br>')}</td></tr>`);
  }

  $('fields').innerHTML = rows.join('');
  $('fields').hidden = rows.length === 0;
}

async function post(url, body, errEl) {
  errEl.hidden = true;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = await res.json();

  if (!res.ok || data.error) {
    errEl.textContent = data.error || `Erreur ${res.status}`;
    errEl.hidden = false;
    return;
  }

  show(data);
}

$('gen').addEventListener('submit', (ev) => {
  ev.preventDefault();
  post('/generate', Object.fromEntries(new FormData(ev.target)), $('gen-err'));
});

$('dec').addEventListener('submit', (ev) => {
  ev.preventDefault();
  $('qr').innerHTML = '';
  post('/decode', Object.fromEntries(new FormData(ev.target)), $('dec-err'));
});
</script>
</body>
</html>
