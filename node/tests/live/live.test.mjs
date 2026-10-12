// Appelle l'API reelle (consomme du quota). Definir IT_TOOLS_API_KEY, IT_TOOLS_API_SECRET et,
// hors production, IT_TOOLS_BASE_URI (+ NODE_TLS_REJECT_UNAUTHORIZED=0 pour un certificat auto-signe).
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { AuthenticationError, ItTools, ValidationError } from '../../dist/index.js';

const apiKey = process.env.IT_TOOLS_API_KEY;
const skip = apiKey ? false : 'IT_TOOLS_API_KEY non defini';

const client = () =>
  new ItTools({ apiKey, apiSecret: process.env.IT_TOOLS_API_SECRET, baseUrl: process.env.IT_TOOLS_BASE_URI || undefined, timeoutMs: 60_000 });

function minimalPdf() {
  const objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>', '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>'];
  let pdf = '%PDF-1.4\n';
  const offsets = [];
  objects.forEach((object, i) => {
    offsets.push(pdf.length);
    pdf += `${i + 1} 0 obj\n${object}\nendobj\n`;
  });
  const xref = pdf.length;
  pdf += `xref\n0 ${objects.length + 1}\n0000000000 65535 f \n`;
  for (const offset of offsets) pdf += `${String(offset).padStart(10, '0')} 00000 n \n`;
  return pdf + `trailer\n<< /Size ${objects.length + 1} /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF\n`;
}

test('chaque outil developpeur repond avec succes', { skip }, async () => {
  const dev = client().dev;
  const calls = {
    'json/format': () => dev.jsonFormat('{"name":"Jean","age":30}', { indent: 4 }),
    'json/minify': () => dev.jsonMinify('{ "a": 1, "b": [1, 2] }'),
    'json-yaml': () => dev.jsonYaml('{"a":[1,2]}'),
    'json-to-csv': () => dev.jsonToCsv('[{"nom":"Alice","age":30},{"nom":"Bob","age":25}]', { separator: ';' }),
    'jwt/decode': () => dev.jwtDecode('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.c2lnbmF0dXJl'),
    'ipv4-subnet': () => dev.ipv4Subnet('192.168.1.10/24'),
    chmod: () => dev.chmod({ octal: '755' }),
    'url/parse': () => dev.urlParse('https://example.com:8080/path?foo=bar#section'),
    slugify: () => dev.slugify('Été à Paris !'),
    'case/convert': () => dev.convertCase('hello world', 'camel'),
    'text-statistics': () => dev.textStatistics('Bonjour le monde.'),
    'html-entities': () => dev.htmlEntities('<a href="x">é</a>'),
    base64: () => dev.base64('Bonjour'),
    hash: () => dev.hash('abc', { algorithms: ['sha1', 'sha256'] }),
    uuid: () => dev.uuid({ count: 2, version: 'v7' }),
    password: () => dev.password({ count: 2, length: 20, symbols: true }),
    token: () => dev.token({ count: 2, length: 16, format: 'hex' }),
    timestamp: () => dev.timestamp({ value: 1700000000, timezone: 'Europe/Paris' }),
    'date/add': () => dev.dateAdd('2024-01-31', 7, { unit: 'days' }),
    'date/diff': () => dev.dateDiff('2024-01-31', '2025-03-01'),
    'fake-data/fields': () => dev.fakeDataFields(),
    'fake-data': () => dev.fakeData(['prenom', 'nom', 'email'], { count: 3, locale: 'fr', seed: 42 }),
    lorem: () => dev.lorem({ mode: 'sentences', count: 2 }),
    color: () => dev.color('#006EDC'),
  };

  for (const [tool, call] of Object.entries(calls)) {
    assert.equal((await call()).type, 'success', tool);
  }

  assert.equal((await dev.slugify('Été à Paris !')).result, 'ete-a-paris');
  assert.equal((await dev.base64('Bonjour')).result, 'Qm9uam91cg==');
  assert.equal((await dev.dateAdd('2024-01-31', 7)).result.date, '2024-02-07');
});

test('une entree invalide leve ValidationError', { skip }, async () => {
  await assert.rejects(client().dev.jsonFormat('{bad'), ValidationError);
});

test('un mauvais secret leve AuthenticationError', { skip }, async () => {
  const wrong = new ItTools({ apiKey, apiSecret: `sk_${'0'.repeat(64)}`, baseUrl: process.env.IT_TOOLS_BASE_URI || undefined });
  await assert.rejects(wrong.dev.uuid(), AuthenticationError);
});

test('CERFA : catalogue, champs, gabarit et generation du PDF', { skip }, async () => {
  const { cerfa } = client();
  const catalog = await cerfa.catalog();
  assert.ok(catalog.available.length > 0);

  const cerfaId = catalog.available[0].cerfaId;
  assert.ok((await cerfa.fields(cerfaId)).fields.length > 0);
  assert.ok('data' in (await cerfa.template(cerfaId)));

  const bytes = await (await cerfa.generate(cerfaId, {})).content();
  assert.equal(new TextDecoder().decode(bytes.slice(0, 5)), '%PDF-');
});

test('operations PDF : compress, merge, rotate', { skip }, async () => {
  const { pdf } = client();
  const source = { data: new TextEncoder().encode(minimalPdf()), filename: 'source.pdf' };

  for (const operation of [() => pdf.compress(source, { level: 'low' }), () => pdf.merge([source, source]), () => pdf.rotate(source, { rotation: 90 })]) {
    const bytes = await (await operation()).content();
    assert.equal(new TextDecoder().decode(bytes.slice(0, 5)), '%PDF-');
  }
});

// Isole : /pdf/info repond 422 sur tout PDF dans l'environnement local (les autres operations PDF fonctionnent).
test('pdf.info', { skip }, async () => {
  const info = await client().pdf.info({ data: new TextEncoder().encode(minimalPdf()), filename: 'source.pdf' });
  assert.ok('info' in info);
});

test('outils : devises, formats, montant en lettres', { skip }, async () => {
  const { tools } = client();
  assert.ok((await tools.currencies()).length > 0);
  assert.ok((await tools.imageFormats()).includes('webp'));
  assert.match((await tools.numberToWords('1234.56', { currency: 'EUR' })).result, /euros/);
});
