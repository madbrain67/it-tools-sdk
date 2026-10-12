import assert from 'node:assert/strict';
import { test } from 'node:test';
import { ItTools } from '../dist/index.js';
import { fakeFetch } from './helpers.mjs';

const endpoints = [
  ['json/format', (d) => d.jsonFormat('{"a":1}', { indent: 4 }), 'POST', '/json/format', { json: '{"a":1}', indent: 4 }],
  ['json/format structure', (d) => d.jsonFormat({ a: 1 }), 'POST', '/json/format', { json: { a: 1 } }],
  ['json/minify', (d) => d.jsonMinify('{ "a": 1 }'), 'POST', '/json/minify', { json: '{ "a": 1 }' }],
  ['json-yaml', (d) => d.jsonYaml('a: 1', { direction: 'yaml-to-json', indent: 2 }), 'POST', '/json-yaml/convert', { input: 'a: 1', direction: 'yaml-to-json', indent: 2 }],
  ['json-to-csv', (d) => d.jsonToCsv('[{"a":1}]', { separator: ';', headers: false, neutralizeFormulas: true }), 'POST', '/json-to-csv/convert', { json: '[{"a":1}]', separator: ';', headers: false, neutralizeFormulas: true }],
  ['jwt/decode', (d) => d.jwtDecode('t.o.k'), 'POST', '/jwt/decode', { token: 't.o.k' }],
  ['ipv4-subnet', (d) => d.ipv4Subnet('10.0.0.1/8'), 'POST', '/ipv4-subnet/calculate', { cidr: '10.0.0.1/8' }],
  ['chmod', (d) => d.chmod({ octal: '755' }), 'POST', '/chmod/calculate', { octal: '755' }],
  ['url/parse', (d) => d.urlParse('https://x.test/?a=1'), 'POST', '/url/parse', { url: 'https://x.test/?a=1' }],
  ['slugify', (d) => d.slugify('Été', { separator: '_', lowercase: false }), 'POST', '/slugify', { text: 'Été', separator: '_', lowercase: false }],
  ['case/convert', (d) => d.convertCase('hello world', 'camel'), 'POST', '/case/convert', { text: 'hello world', case: 'camel' }],
  ['text-statistics', (d) => d.textStatistics('Bonjour.'), 'POST', '/text-statistics', { text: 'Bonjour.' }],
  ['html-entities', (d) => d.htmlEntities('<a>', { mode: 'decode', encodeAll: true }), 'POST', '/html-entities/convert', { text: '<a>', mode: 'decode', encodeAll: true }],
  ['base64', (d) => d.base64('abc', { mode: 'encode', urlSafe: true }), 'POST', '/base64/convert', { text: 'abc', mode: 'encode', urlSafe: true }],
  ['hash', (d) => d.hash('abc', { algorithms: ['sha256'] }), 'POST', '/hash', { text: 'abc', algorithms: ['sha256'] }],
  ['uuid defaut', (d) => d.uuid(), 'POST', '/uuid', {}],
  ['uuid options', (d) => d.uuid({ count: 2, version: 'v7', uppercase: true, withoutDashes: true }), 'POST', '/uuid', { count: 2, version: 'v7', uppercase: true, withoutDashes: true }],
  ['password', (d) => d.password({ count: 2, length: 20, symbols: true, excludeSimilar: false }), 'POST', '/password', { count: 2, length: 20, symbols: true, excludeSimilar: false }],
  ['token', (d) => d.token({ count: 2, length: 16, format: 'custom', custom: 'ab' }), 'POST', '/token', { count: 2, length: 16, format: 'custom', custom: 'ab' }],
  ['timestamp entier', (d) => d.timestamp({ value: 1700000000, timezone: 'Europe/Paris' }), 'POST', '/timestamp/convert', { value: 1700000000, timezone: 'Europe/Paris' }],
  ['timestamp vide', (d) => d.timestamp(), 'POST', '/timestamp/convert', {}],
  ['date/add', (d) => d.dateAdd('2024-01-31', -3, { unit: 'months' }), 'POST', '/date/add', { date: '2024-01-31', value: -3, unit: 'months' }],
  ['date/diff', (d) => d.dateDiff('2024-01-31', '2025-03-01'), 'POST', '/date/diff', { from: '2024-01-31', to: '2025-03-01' }],
  ['fake-data/fields', (d) => d.fakeDataFields(), 'GET', '/fake-data/fields', null],
  ['fake-data', (d) => d.fakeData(['prenom'], { count: 3, locale: 'fr', seed: 42 }), 'POST', '/fake-data', { fields: ['prenom'], count: 3, locale: 'fr', seed: 42 }],
  ['lorem', (d) => d.lorem({ mode: 'sentences', count: 2, minWords: 3, maxWords: 9, startWithLorem: false, format: 'html', seed: 7 }), 'POST', '/lorem', { mode: 'sentences', count: 2, minWords: 3, maxWords: 9, startWithLorem: false, format: 'html', seed: 7 }],
  ['color', (d) => d.color('#006EDC'), 'POST', '/color/convert', { color: '#006EDC' }],
  ['color objet', (d) => d.color({ r: 0, g: 110, b: 220 }), 'POST', '/color/convert', { color: { r: 0, g: 110, b: 220 } }],
];

for (const [name, call, method, path, body] of endpoints) {
  test(`dev ${name} : route documentee, seuls les parametres fournis sont envoyes`, async () => {
    const fetch = fakeFetch();
    const client = new ItTools({ apiKey: 'pk_a', apiSecret: 'sk_b', baseUrl: 'https://a.test', fetch });

    const result = await call(client.dev);

    const sent = fetch.calls[0];
    assert.equal(sent.method, method);
    assert.equal(sent.url, `https://a.test/api/v1/tools/dev${path}`);
    if (body === null) {
      assert.equal(sent.body, undefined);
    } else {
      assert.deepEqual(JSON.parse(sent.body), body);
    }
    assert.equal(result.type, 'success');
  });
}
