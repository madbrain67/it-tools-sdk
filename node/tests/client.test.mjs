import assert from 'node:assert/strict';
import { test } from 'node:test';
import {
  ApiError,
  AuthenticationError,
  ItTools,
  ItToolsError,
  NotFoundError,
  PayloadTooLargeError,
  QuotaExceededError,
  ServerError,
  TransportError,
  UnsupportedMediaTypeError,
  ValidationError,
} from '../dist/index.js';
import { bytes, fakeFetch, json } from './helpers.mjs';

const make = (fetch, extra = {}) => new ItTools({ apiKey: 'pk_a', apiSecret: 'sk_b', baseUrl: 'https://a.test/ignored/path', fetch, ...extra });

test('refuse une configuration invalide', () => {
  assert.throws(() => new ItTools({ apiKey: '  ' }), ItToolsError);
  assert.throws(() => new ItTools({ apiKey: 'pk', baseUrl: 'ftp://a.test' }), ItToolsError);
  assert.throws(() => new ItTools({ apiKey: 'pk', baseUrl: 'pas une url' }), ItToolsError);
  assert.throws(() => new ItTools({ apiKey: 'pk', timeoutMs: 0 }), ItToolsError);
});

test('normalise la base et envoie le secret tel qu\'affiche', async () => {
  const fetch = fakeFetch();
  const client = make(fetch);
  await client.dev.uuid();

  assert.equal(client.baseUrl, 'https://a.test');
  const call = fetch.calls[0];
  assert.equal(call.url, 'https://a.test/api/v1/tools/dev/uuid');
  assert.equal(call.method, 'POST');
  assert.equal(call.headers.get('X-Api-Key'), 'pk_a');
  assert.equal(call.headers.get('X-Api-Secret'), 'sk_b');
  assert.equal(call.headers.get('Content-Type'), 'application/json');
  assert.equal(call.body, '{}');
  assert.equal(call.redirect, 'manual');
});

test('mode navigateur : pas de secret', async () => {
  const fetch = fakeFetch();
  await new ItTools({ apiKey: 'pk_a', baseUrl: 'https://a.test', fetch }).dev.uuid();

  assert.equal(fetch.calls[0].headers.has('X-Api-Secret'), false);
});

test('les statuts d\'erreur deviennent des erreurs typees avec le message de l\'API', async () => {
  const cases = [
    [400, ValidationError],
    [401, AuthenticationError],
    [403, AuthenticationError],
    [404, NotFoundError],
    [413, PayloadTooLargeError],
    [415, UnsupportedMediaTypeError],
    [422, ValidationError],
    [429, QuotaExceededError],
    [500, ServerError],
    [418, ApiError],
  ];

  for (const [status, expected] of cases) {
    await assert.rejects(make(fakeFetch(json({ message: 'Refuse.' }, status))).dev.uuid(), (error) => {
      assert.ok(error instanceof expected, `${status} -> ${expected.name}`);
      assert.equal(error.status, status);
      assert.equal(error.message, 'Refuse.');
      assert.match(error.body, /Refuse/);
      return true;
    });
  }
});

test('un corps d\'erreur non JSON conserve le message par defaut', async () => {
  await assert.rejects(make(fakeFetch(bytes('<html>Bad gateway</html>', 502))).dev.uuid(), (error) => error instanceof ServerError && error.message === 'Erreur HTTP 502.');
});

test('une redirection est une anomalie (les cles ne doivent pas quitter l\'hote)', async () => {
  await assert.rejects(make(fakeFetch(new Response(null, { status: 302, headers: { Location: 'https://evil.test/' } }))).dev.uuid(), (error) => error instanceof ApiError && error.status === 302);
});

test('une panne reseau devient une TransportError', async () => {
  await assert.rejects(make(fakeFetch(new TypeError('fetch failed'))).dev.uuid(), TransportError);
});

test('le delai maximal interrompt la requete', async () => {
  const slow = (url, init) => new Promise((_, reject) => init.signal.addEventListener('abort', () => reject(init.signal.reason)));
  await assert.rejects(make(slow, { timeoutMs: 30 }).dev.uuid(), TransportError);
});

test('le telechargement reste sur l\'hote configure, avec les cles', async () => {
  const fetch = fakeFetch(json({ download_url: 'http://a.test/api/v1/pdf/download/x.pdf/tok?t=1' }), bytes('%PDF-1'));
  const file = await make(fetch).pdf.repair({ data: new Uint8Array([1]), filename: 'a.pdf' });

  assert.equal(file.hasDownload, true);
  assert.equal(new TextDecoder().decode(await file.content()), '%PDF-1');
  assert.equal(fetch.calls[1].url, 'https://a.test/api/v1/pdf/download/x.pdf/tok?t=1');
  assert.equal(fetch.calls[1].method, 'GET');
  assert.equal(fetch.calls[1].headers.get('X-Api-Secret'), 'sk_b');
});

test('le telechargement refuse tout autre hote sans envoyer de requete', async () => {
  const foreign = [
    'https://evil.test/api/v1/pdf/download/x/abc',
    'https://a.test.evil.test/x',
    'https://a.test:9999/x',
    'https://a.test@evil.test/x',
    'https://user:pw@a.test/x',
  ];

  for (const link of foreign) {
    const fetch = fakeFetch(json({ download_url: link }));
    const file = await make(fetch).cerfa.generate('10103-14', {});
    await assert.rejects(file.content(), ItToolsError, link);
    assert.equal(fetch.calls.length, 1, `aucune requete de telechargement pour ${link}`);
  }
});

test('un lien relatif est accepte', async () => {
  const fetch = fakeFetch(json({ download_url: '/api/v1/pdf/download/x.pdf/tok' }), bytes('ok'));
  const file = await make(fetch).cerfa.generate('10103-14', {});

  await file.content();
  assert.equal(fetch.calls[1].url, 'https://a.test/api/v1/pdf/download/x.pdf/tok');
});

test('une reponse sans download_url est signalee clairement', async () => {
  const file = await make(fakeFetch(json({ info: 'x' }))).cerfa.generate('x', {});
  assert.equal(file.hasDownload, false);
  assert.throws(() => file.downloadUrl, ItToolsError);
});
