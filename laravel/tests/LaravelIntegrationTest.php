<?php

declare(strict_types=1);

namespace Plateformweb\ItToolsLaravel\Tests;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;
use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Exception\ItToolsException;
use Plateformweb\ItTools\Exception\NotFoundException;
use Plateformweb\ItTools\Exception\TransportException;
use Plateformweb\ItTools\File;
use Plateformweb\ItToolsLaravel\Facades\ItTools;
use Plateformweb\ItToolsLaravel\ItToolsServiceProvider;

final class LaravelIntegrationTest extends TestCase
{
    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [ItToolsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('it-tools.api_key', 'pk_test');
        $app['config']->set('it-tools.api_secret', 'sk_test');
        $app['config']->set('it-tools.base_uri', 'https://api.test');
    }

    public function testProviderBuildsTheClientFromConfigAndAliasesIt(): void
    {
        $client = $this->app->make(Client::class);

        self::assertSame($client, $this->app->make('it-tools'));
        self::assertSame('pk_test', $client->config()->apiKey);
        self::assertSame('https://api.test', $client->config()->baseUri);
        self::assertTrue($client->config()->verifyPeer);
    }

    public function testFacadeSendsJsonWithCredentialsThroughLaravelHttp(): void
    {
        Http::fake(['api.test/*' => Http::response(['type' => 'success', 'result' => 'ete-a-paris'], 200)]);

        $result = ItTools::dev()->slugify('Été à Paris');

        self::assertSame('ete-a-paris', $result['result']);
        Http::assertSent(static fn (Request $request): bool => 'POST' === $request->method()
            && 'https://api.test/api/v1/tools/dev/slugify' === $request->url()
            && 'pk_test' === $request->header('X-Api-Key')[0]
            && 'sk_test' === $request->header('X-Api-Secret')[0]
            && ['text' => 'Été à Paris'] === $request->data());
    }

    public function testGetRequestSendsNoBody(): void
    {
        Http::fake(['api.test/*' => Http::response(['type' => 'success'], 200)]);

        ItTools::dev()->fakeDataFields();

        Http::assertSent(static fn (Request $request): bool => 'GET' === $request->method() && '' === $request->body());
    }

    public function testMultipartCarriesFilesAndFields(): void
    {
        Http::fake(['api.test/*' => Http::response(['download_url' => '/api/v1/pdf/download/a.pdf/tok'], 200)]);

        $file = ItTools::pdf()->compress(File::fromContents('%PDF-data', 'in.pdf', 'application/pdf'), 'high');

        self::assertTrue($file->hasDownload());
        Http::assertSent(static function (Request $request): bool {
            $names = array_column($request->data(), 'name');

            return 'https://api.test/api/v1/pdf/compress' === $request->url()
                && $request->hasFile('pdf', '%PDF-data', 'in.pdf')
                && \in_array('level', $names, true);
        });
    }

    public function testMultipleFilesUseIndexedFieldNames(): void
    {
        Http::fake(['api.test/*' => Http::response(['download_url' => '/x/y'], 200)]);

        ItTools::pdf()->merge([File::fromContents('a', 'a.pdf'), File::fromContents('b', 'b.pdf')]);

        Http::assertSent(static fn (Request $request): bool => $request->hasFile('pdfs[0]', 'a', 'a.pdf') && $request->hasFile('pdfs[1]', 'b', 'b.pdf'));
    }

    public function testHttpErrorsAreMappedToTypedExceptions(): void
    {
        Http::fake(['api.test/*' => Http::response(['message' => 'CERFA non disponible.'], 404)]);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('CERFA non disponible.');
        ItTools::cerfa()->fields('zzz');
    }

    public function testRedirectsAreRejected(): void
    {
        Http::fake(['api.test/*' => Http::response('', 302, ['Location' => 'https://evil.test/'])]);

        $this->expectException(ItToolsException::class);
        ItTools::dev()->uuid();
    }

    public function testConnectionFailuresBecomeTransportExceptions(): void
    {
        Http::fake(static function (): never {
            throw new ConnectionException('cURL error 28: timeout');
        });

        $this->expectException(TransportException::class);
        ItTools::dev()->uuid();
    }

    public function testMissingApiKeyFailsWithAClearMessage(): void
    {
        $this->app['config']->set('it-tools.api_key', null);

        $this->expectException(ItToolsException::class);
        $this->expectExceptionMessage('IT_TOOLS_API_KEY');
        $this->app->make(Client::class);
    }
}
