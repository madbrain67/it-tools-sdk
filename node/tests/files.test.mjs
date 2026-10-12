import assert from 'node:assert/strict';
import { mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import { ItToolsError, ItTools } from '../dist/index.js';
import { bytes, fakeFetch, formEntries, formObject, json } from './helpers.mjs';

const make = (fetch) => new ItTools({ apiKey: 'pk_a', apiSecret: 'sk_b', baseUrl: 'https://a.test', fetch });
const pdf = (name = 'a.pdf') => ({ data: new TextEncoder().encode('%PDF-data'), filename: name });

test('merge envoie chaque PDF sous un nom indexe, sans Content-Type force', async () => {
  const fetch = fakeFetch(json({ download_url: '/api/v1/pdf/download/m.pdf/tok' }));
  const result = await make(fetch).pdf.merge([pdf('a.pdf'), pdf('b.pdf')]);

  const call = fetch.calls[0];
  assert.equal(call.url, 'https://a.test/api/v1/pdf/merge');
  assert.equal(call.headers.has('Content-Type'), false, 'fetch fixe lui-meme le Content-Type multipart et sa frontiere');
  assert.deepEqual(formEntries(call).map(([name, value]) => [name, value.filename, value.type]), [
    ['pdfs[0]', 'a.pdf', 'application/pdf'],
    ['pdfs[1]', 'b.pdf', 'application/pdf'],
  ]);
  assert.equal(result.hasDownload, true);
});

test('booleens et nombres deviennent des chaines multipart, undefined est omis', async () => {
  const fetch = fakeFetch();
  await make(fetch).pdf.protect(pdf(), { userPassword: 'secret', allowPrint: true, allowCopy: false, ownerPassword: undefined });

  const form = formObject(fetch.calls[0]);
  assert.equal(form.userPassword, 'secret');
  assert.equal(form.allowPrint, 'true');
  assert.equal(form.allowCopy, 'false');
  assert.equal('ownerPassword' in form, false);
  assert.equal(form.pdf.filename, 'a.pdf');
});

test('un fichier optionnel est joint sous son nom de champ', async () => {
  const fetch = fakeFetch();
  await make(fetch).pdf.addWatermark(pdf(), { type: 'image', watermarkImage: { data: new Uint8Array([1]), filename: 'w.png' } });

  const form = formObject(fetch.calls[0]);
  assert.equal(form.watermarkImage.filename, 'w.png');
  assert.equal(form.watermarkImage.type, 'image/png');
  assert.equal(form.type, 'image');
});

test('un chemin disque est lu avec son nom et son type', async () => {
  const dir = await mkdtemp(join(tmpdir(), 'itt-'));
  try {
    const path = join(dir, 'rapport.pdf');
    await writeFile(path, '%PDF-disque');
    const fetch = fakeFetch();
    await make(fetch).pdf.info(path);

    const form = formObject(fetch.calls[0]);
    assert.equal(form.pdf.filename, 'rapport.pdf');
    assert.equal(form.pdf.type, 'application/pdf');
    assert.equal(form.pdf.size, 11);
  } finally {
    await rm(dir, { recursive: true, force: true });
  }
});

test('un fichier introuvable ou une entree invalide est refuse sans requete', async () => {
  const fetch = fakeFetch();
  await assert.rejects(make(fetch).pdf.info(join(tmpdir(), 'inexistant-itt.pdf')), ItToolsError);
  await assert.rejects(make(fetch).pdf.info({ nope: true }), ItToolsError);
  assert.equal(fetch.calls.length, 0);
});

test('un Blob nomme (File) conserve son nom', async () => {
  const fetch = fakeFetch();
  await make(fetch).pdf.info(new File(['%PDF-x'], 'upload.pdf', { type: 'application/pdf' }));

  assert.equal(formObject(fetch.calls[0]).pdf.filename, 'upload.pdf');
});

test('ProcessedFile.save telecharge puis ecrit le fichier', async () => {
  const dir = await mkdtemp(join(tmpdir(), 'itt-'));
  try {
    const fetch = fakeFetch(json({ download_url: '/api/v1/pdf/download/x.pdf/tok' }), bytes('%PDF-final'));
    const target = join(dir, 'out.pdf');

    const written = await (await make(fetch).pdf.repair(pdf())).save(target);

    assert.equal(written, 10);
    assert.equal(await readFile(target, 'utf8'), '%PDF-final');
  } finally {
    await rm(dir, { recursive: true, force: true });
  }
});

test('cerfa : identifiant encode, data vide envoyee comme objet', async () => {
  const fetch = fakeFetch();
  await make(fetch).cerfa.generate('../x?y', {});

  assert.equal(fetch.calls[0].url, 'https://a.test/api/v1/cerfa/..%2Fx%3Fy/generate');
  assert.equal(fetch.calls[0].body, '{"data":{}}');
});

test('booklet : sections encodees en JSON et fichiers joints', async () => {
  const fetch = fakeFetch();
  await make(fetch).booklet.create('Titre', { sections: [{ title: 'A', text: 'é' }], logoImage: { data: new Uint8Array([1]), filename: 'l.png' }, qrLogoKey: 'github' });

  const form = formObject(fetch.calls[0]);
  assert.equal(form.title, 'Titre');
  assert.equal(form.sections, '[{"title":"A","text":"é"}]');
  assert.equal(form.qrLogoKey, 'github');
  assert.equal(form.logoImage.filename, 'l.png');
});

test('booklet : le QR code passe le format en query string', async () => {
  const fetch = fakeFetch(bytes('<svg/>'));
  const body = await make(fetch).booklet.qrCode('mon-slug', { format: 'png' });

  assert.equal(new TextDecoder().decode(body), '<svg/>');
  assert.equal(fetch.calls[0].url, 'https://a.test/api/v1/tools/booklet/mon-slug/qrcode?format=png');
});

test('kit d\'images : listes jointes, puis attente jusqu\'a done', async () => {
  const fetch = fakeFetch(json({ jobId: 12 }), json({ status: 'processing' }), json({ status: 'done', download_url: '/api/v1/tools/image-kit/download/k.zip/tok' }));
  const client = make(fetch);

  const job = await client.imageKit.create({ data: new Uint8Array([1]), filename: 'a.png' }, { presets: ['desktop', 'mobile'], formats: ['webp'], watermarkOpacity: 0.5 });
  const form = formObject(fetch.calls[0]);
  assert.equal(form.presets, 'desktop,mobile');
  assert.equal(form.formats, 'webp');
  assert.equal(form.watermarkOpacity, '0.5');

  const final = await client.imageKit.waitUntilDone(job.jobId, { ownerToken: 'owner', intervalMs: 1 });
  assert.equal(final.status, 'done');
  assert.equal(fetch.calls[2].url, 'https://a.test/api/v1/tools/image-kit/12/status?ownerToken=owner');
});

test('kit d\'images : echec et delai depasse sont signales', async () => {
  await assert.rejects(make(fakeFetch(json({ status: 'failed', message: 'Image illisible.' }))).imageKit.waitUntilDone(1, { intervalMs: 1 }), /Image illisible\./);
  await assert.rejects(make(fakeFetch(json({ status: 'pending' }))).imageKit.waitUntilDone(1, { timeoutMs: 1, intervalMs: 1 }), ItToolsError);
});

test('tools : image convertie en binaire, montant en lettres, formats', async () => {
  const fetch = fakeFetch(bytes('RIFFwebp', 200, 'image/webp'), json({ type: 'success', result: 'mille' }), json(['webp', 'jpg']));
  const client = make(fetch);

  assert.equal(new TextDecoder().decode(await client.tools.convertImage({ data: new Uint8Array([1]), filename: 'a.png' }, { format: 'webp', quality: 80 })), 'RIFFwebp');
  assert.deepEqual(Object.fromEntries(formEntries(fetch.calls[0]).filter(([n]) => n !== 'file')), { format: 'webp', quality: '80' });

  await client.tools.numberToWords(1234.5, { currency: 'EUR' });
  assert.deepEqual(JSON.parse(fetch.calls[1].body), { amount: '1234.5', currency: 'EUR' });

  assert.deepEqual(await client.tools.imageFormats(), ['webp', 'jpg']);
});

test('qrcode : seuls les champs fournis sont envoyes', async () => {
  const fetch = fakeFetch();
  await make(fetch).qrCode.generate('https://x.test', { format: 'png', size: 200 });

  assert.deepEqual(JSON.parse(fetch.calls[0].body), { content: 'https://x.test', format: 'png', size: 200 });
});
