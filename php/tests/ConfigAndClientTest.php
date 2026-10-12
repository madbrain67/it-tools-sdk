<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Config;
use Plateformweb\ItTools\Exception\ApiException;
use Plateformweb\ItTools\Exception\AuthenticationException;
use Plateformweb\ItTools\Exception\ItToolsException;
use Plateformweb\ItTools\Exception\NotFoundException;
use Plateformweb\ItTools\Exception\PayloadTooLargeException;
use Plateformweb\ItTools\Exception\QuotaExceededException;
use Plateformweb\ItTools\Exception\ServerException;
use Plateformweb\ItTools\Exception\UnsupportedMediaTypeException;
use Plateformweb\ItTools\Exception\ValidationException;
use Plateformweb\ItTools\Tests\Support\FakeTransport;
use Plateformweb\ItTools\Transport\HttpResponse;

final class ConfigAndClientTest extends TestCase
{
    public function testConfigNormalizesBaseUriAndSecret(): void
    {
        $config = new Config('pk_a', '  ', 'https://api.example.com:8443/ignored/path?x=1');

        self::assertSame('https://api.example.com:8443', $config->baseUri);
        self::assertNull($config->apiSecret);
    }

    #[DataProvider('invalidConfigs')]
    public function testConfigRejectsInvalidValues(string $key, string $base, float $timeout): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Config($key, null, $base, $timeout);
    }

    /**
     * @return iterable<string, array{string, string, float}>
     */
    public static function invalidConfigs(): iterable
    {
        yield 'cle vide' => ['  ', 'https://a.test', 30.0];
        yield 'schema ftp' => ['pk_a', 'ftp://a.test', 30.0];
        yield 'sans hote' => ['pk_a', 'not a url', 30.0];
        yield 'delai nul' => ['pk_a', 'https://a.test', 0.0];
    }

    public function testSecretIsSentAsDisplayedWithKey(): void
    {
        $transport = new FakeTransport();
        Client::create('pk_abc', 'sk_def', 'https://a.test', transport: $transport)->dev()->uuid();

        $request = $transport->last();
        self::assertSame('pk_abc', $request->headers['X-Api-Key']);
        self::assertSame('sk_def', $request->headers['X-Api-Secret']);
        self::assertSame('POST', $request->method);
        self::assertSame('https://a.test/api/v1/tools/dev/uuid', $request->url);
    }

    public function testBrowserModeOmitsSecret(): void
    {
        $transport = new FakeTransport();
        Client::create('pk_abc', null, 'https://a.test', transport: $transport)->dev()->uuid();

        self::assertArrayNotHasKey('X-Api-Secret', $transport->last()->headers);
    }

    public function testEmptyPayloadIsEncodedAsJsonObject(): void
    {
        $transport = new FakeTransport();
        Client::create('pk_a', null, 'https://a.test', transport: $transport)->dev()->uuid();

        self::assertSame('{}', $transport->last()->body);
        self::assertSame('application/json', $transport->last()->headers['Content-Type']);
    }

    #[DataProvider('errorStatuses')]
    public function testErrorStatusesMapToTypedExceptions(int $status, string $expected): void
    {
        $transport = (new FakeTransport())->queue(FakeTransport::json(['message' => 'Refuse.'], $status));
        $client = Client::create('pk_a', null, 'https://a.test', transport: $transport);

        try {
            $client->dev()->uuid();
            self::fail('Exception attendue.');
        } catch (ApiException $e) {
            self::assertInstanceOf($expected, $e);
            self::assertSame($status, $e->statusCode());
            self::assertSame('Refuse.', $e->getMessage());
            self::assertStringContainsString('Refuse.', $e->responseBody());
        }
    }

    /**
     * @return iterable<string, array{int, class-string}>
     */
    public static function errorStatuses(): iterable
    {
        yield '400' => [400, ValidationException::class];
        yield '401' => [401, AuthenticationException::class];
        yield '403' => [403, AuthenticationException::class];
        yield '404' => [404, NotFoundException::class];
        yield '413' => [413, PayloadTooLargeException::class];
        yield '415' => [415, UnsupportedMediaTypeException::class];
        yield '422' => [422, ValidationException::class];
        yield '429' => [429, QuotaExceededException::class];
        yield '500' => [500, ServerException::class];
        yield '418' => [418, ApiException::class];
    }

    public function testErrorWithoutJsonBodyFallsBackToStatusMessage(): void
    {
        $transport = (new FakeTransport())->queue(new HttpResponse(502, [], '<html>Bad gateway</html>'));

        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Erreur HTTP 502.');
        Client::create('pk_a', null, 'https://a.test', transport: $transport)->dev()->uuid();
    }

    public function testDownloadStaysOnConfiguredHostAndKeepsQuery(): void
    {
        $transport = (new FakeTransport())->queue(new HttpResponse(200, [], '%PDF-1'));
        $client = Client::create('pk_a', 'sk_b', 'https://a.test', transport: $transport);

        $body = $client->download('http://a.test/api/v1/pdf/download/x.pdf/abc?t=1');

        self::assertSame('%PDF-1', $body);
        self::assertSame('https://a.test/api/v1/pdf/download/x.pdf/abc?t=1', $transport->last()->url);
        self::assertSame('sk_b', $transport->last()->headers['X-Api-Secret']);
    }

    public function testDownloadAcceptsRelativePath(): void
    {
        $transport = (new FakeTransport())->queue(new HttpResponse(200, [], 'ok'));
        Client::create('pk_a', null, 'https://a.test', transport: $transport)->download('/api/v1/pdf/download/x.pdf/abc');

        self::assertSame('https://a.test/api/v1/pdf/download/x.pdf/abc', $transport->last()->url);
    }

    #[DataProvider('foreignUrls')]
    public function testDownloadRefusesForeignHosts(string $url): void
    {
        $transport = new FakeTransport();
        $client = Client::create('pk_a', 'sk_b', 'https://a.test', transport: $transport);

        try {
            $client->download($url);
            self::fail('Exception attendue.');
        } catch (ItToolsException) {
            self::assertSame([], $transport->requests, 'Aucune requete (donc aucune cle) ne doit partir.');
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function foreignUrls(): iterable
    {
        yield 'autre domaine' => ['https://evil.test/api/v1/pdf/download/x/abc'];
        yield 'sous-domaine' => ['https://a.test.evil.test/x'];
        yield 'autre port' => ['https://a.test:9999/x'];
        yield 'userinfo' => ['https://a.test@evil.test/x'];
        yield 'sans chemin absolu' => ['x/y'];
    }
}
