<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Tests;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plateformweb\ItTools\Api\DevToolsApi;
use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Tests\Support\FakeTransport;

final class DevToolsApiTest extends TestCase
{
    /**
     * @param Closure(DevToolsApi): mixed $call
     * @param array<string, mixed>|null   $expectedBody null pour une requete GET
     */
    #[DataProvider('endpoints')]
    public function testEachEndpointCallsTheDocumentedRouteWithOnlyGivenParameters(
        Closure $call,
        string $method,
        string $path,
        ?array $expectedBody,
    ): void {
        $transport = new FakeTransport();
        $result = $call(Client::create('pk_a', 'sk_b', 'https://a.test', transport: $transport)->dev());

        $request = $transport->last();
        self::assertSame($method, $request->method);
        self::assertSame('https://a.test/api/v1/tools/dev' . $path, $request->url);
        if (null === $expectedBody) {
            self::assertNull($request->body);
        } else {
            self::assertEquals($expectedBody, $transport->lastJson());
        }
        self::assertSame(['type' => 'success'], $result);
    }

    /**
     * @return iterable<string, array{Closure, string, string, array<string, mixed>|null}>
     */
    public static function endpoints(): iterable
    {
        yield 'json/format' => [fn (DevToolsApi $a) => $a->jsonFormat('{"a":1}', 4), 'POST', '/json/format', ['json' => '{"a":1}', 'indent' => 4]];
        yield 'json/format structure' => [fn (DevToolsApi $a) => $a->jsonFormat(['a' => 1]), 'POST', '/json/format', ['json' => ['a' => 1]]];
        yield 'json/minify' => [fn (DevToolsApi $a) => $a->jsonMinify('{ "a": 1 }'), 'POST', '/json/minify', ['json' => '{ "a": 1 }']];
        yield 'json-yaml' => [fn (DevToolsApi $a) => $a->jsonYaml('a: 1', 'yaml-to-json', 2), 'POST', '/json-yaml/convert', ['input' => 'a: 1', 'direction' => 'yaml-to-json', 'indent' => 2]];
        yield 'json-to-csv' => [fn (DevToolsApi $a) => $a->jsonToCsv('[{"a":1}]', ';', false, true), 'POST', '/json-to-csv/convert', ['json' => '[{"a":1}]', 'separator' => ';', 'headers' => false, 'neutralizeFormulas' => true]];
        yield 'jwt/decode' => [fn (DevToolsApi $a) => $a->jwtDecode('t.o.k'), 'POST', '/jwt/decode', ['token' => 't.o.k']];
        yield 'ipv4-subnet' => [fn (DevToolsApi $a) => $a->ipv4Subnet('10.0.0.1/8'), 'POST', '/ipv4-subnet/calculate', ['cidr' => '10.0.0.1/8']];
        yield 'chmod octal' => [fn (DevToolsApi $a) => $a->chmod(octal: '755'), 'POST', '/chmod/calculate', ['octal' => '755']];
        yield 'chmod permissions' => [fn (DevToolsApi $a) => $a->chmod(permissions: ['owner' => ['r' => true]]), 'POST', '/chmod/calculate', ['permissions' => ['owner' => ['r' => true]]]];
        yield 'url/parse' => [fn (DevToolsApi $a) => $a->urlParse('https://x.test/?a=1'), 'POST', '/url/parse', ['url' => 'https://x.test/?a=1']];
        yield 'slugify' => [fn (DevToolsApi $a) => $a->slugify('Été', '_', false), 'POST', '/slugify', ['text' => 'Été', 'separator' => '_', 'lowercase' => false]];
        yield 'case/convert' => [fn (DevToolsApi $a) => $a->convertCase('hello world', 'camel'), 'POST', '/case/convert', ['text' => 'hello world', 'case' => 'camel']];
        yield 'text-statistics' => [fn (DevToolsApi $a) => $a->textStatistics('Bonjour.'), 'POST', '/text-statistics', ['text' => 'Bonjour.']];
        yield 'html-entities' => [fn (DevToolsApi $a) => $a->htmlEntities('<a>', 'decode', true), 'POST', '/html-entities/convert', ['text' => '<a>', 'mode' => 'decode', 'encodeAll' => true]];
        yield 'base64' => [fn (DevToolsApi $a) => $a->base64('abc', 'encode', true), 'POST', '/base64/convert', ['text' => 'abc', 'mode' => 'encode', 'urlSafe' => true]];
        yield 'hash' => [fn (DevToolsApi $a) => $a->hash('abc', ['sha256']), 'POST', '/hash', ['text' => 'abc', 'algorithms' => ['sha256']]];
        yield 'uuid defaut' => [fn (DevToolsApi $a) => $a->uuid(), 'POST', '/uuid', []];
        yield 'uuid options' => [fn (DevToolsApi $a) => $a->uuid(2, 'v7', true, true), 'POST', '/uuid', ['count' => 2, 'version' => 'v7', 'uppercase' => true, 'withoutDashes' => true]];
        yield 'password' => [fn (DevToolsApi $a) => $a->password(2, 20, symbols: true, excludeSimilar: false), 'POST', '/password', ['count' => 2, 'length' => 20, 'symbols' => true, 'excludeSimilar' => false]];
        yield 'token' => [fn (DevToolsApi $a) => $a->token(2, 16, 'custom', 'ab'), 'POST', '/token', ['count' => 2, 'length' => 16, 'format' => 'custom', 'custom' => 'ab']];
        yield 'timestamp entier' => [fn (DevToolsApi $a) => $a->timestamp(1700000000, 'Europe/Paris'), 'POST', '/timestamp/convert', ['value' => 1700000000, 'timezone' => 'Europe/Paris']];
        yield 'timestamp iso' => [fn (DevToolsApi $a) => $a->timestamp('2024-01-01T00:00:00Z'), 'POST', '/timestamp/convert', ['value' => '2024-01-01T00:00:00Z']];
        yield 'date/add' => [fn (DevToolsApi $a) => $a->dateAdd('2024-01-31', -3, 'months'), 'POST', '/date/add', ['date' => '2024-01-31', 'value' => -3, 'unit' => 'months']];
        yield 'date/diff' => [fn (DevToolsApi $a) => $a->dateDiff('2024-01-31', '2025-03-01'), 'POST', '/date/diff', ['from' => '2024-01-31', 'to' => '2025-03-01']];
        yield 'fake-data/fields' => [fn (DevToolsApi $a) => $a->fakeDataFields(), 'GET', '/fake-data/fields', null];
        yield 'fake-data' => [fn (DevToolsApi $a) => $a->fakeData(['prenom'], 3, 'fr', 42), 'POST', '/fake-data', ['fields' => ['prenom'], 'count' => 3, 'locale' => 'fr', 'seed' => 42]];
        yield 'lorem' => [fn (DevToolsApi $a) => $a->lorem('sentences', 2, 3, 9, false, 'html', 7), 'POST', '/lorem', ['mode' => 'sentences', 'count' => 2, 'minWords' => 3, 'maxWords' => 9, 'startWithLorem' => false, 'format' => 'html', 'seed' => 7]];
        yield 'color' => [fn (DevToolsApi $a) => $a->color('#006EDC'), 'POST', '/color/convert', ['color' => '#006EDC']];
        yield 'color objet' => [fn (DevToolsApi $a) => $a->color(['r' => 0, 'g' => 110, 'b' => 220]), 'POST', '/color/convert', ['color' => ['r' => 0, 'g' => 110, 'b' => 220]]];
    }
}
