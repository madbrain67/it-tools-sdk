<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Tests;

use PHPUnit\Framework\TestCase;
use Plateformweb\ItTools\Exception\ItToolsException;
use Plateformweb\ItTools\File;
use Plateformweb\ItTools\SharedClient;
use Plateformweb\ItTools\Tests\Support\FakeTransport;

final class FunctionsTest extends TestCase
{
    private FakeTransport $transport;

    protected function setUp(): void
    {
        SharedClient::reset();
        $this->transport = new FakeTransport();
        it_tools_configure('pk_a', 'sk_b', 'https://a.test', transport: $this->transport);
    }

    protected function tearDown(): void
    {
        SharedClient::reset();
        putenv('IT_TOOLS_API_KEY');
        putenv('IT_TOOLS_API_SECRET');
        putenv('IT_TOOLS_BASE_URI');
        putenv('IT_TOOLS_VERIFY_PEER');
    }

    public function testProceduralFunctionsDelegateToTheSharedClient(): void
    {
        it_tools_uuid(count: 2, version: 'v7');

        self::assertSame('https://a.test/api/v1/tools/dev/uuid', $this->transport->last()->url);
        self::assertSame(['count' => 2, 'version' => 'v7'], $this->transport->lastJson());
    }

    public function testPdfFunctionReturnsProcessedFile(): void
    {
        $this->transport->queue(FakeTransport::json(['download_url' => '/api/v1/pdf/download/a.pdf/tok']));

        $file = it_tools_pdf_compress(File::fromContents('x', 'a.pdf'), 'high');

        self::assertSame('/api/v1/pdf/download/a.pdf/tok', $file->downloadUrl());
        self::assertSame(['level' => 'high'], $this->transport->last()->fields);
    }

    public function testClientIsBuiltFromEnvironmentWhenNotConfigured(): void
    {
        SharedClient::reset();
        putenv('IT_TOOLS_API_KEY=pk_env');
        putenv('IT_TOOLS_API_SECRET=sk_env');
        putenv('IT_TOOLS_BASE_URI=https://env.test');
        putenv('IT_TOOLS_VERIFY_PEER=0');

        $config = it_tools_client()->config();

        self::assertSame('pk_env', $config->apiKey);
        self::assertSame('sk_env', $config->apiSecret);
        self::assertSame('https://env.test', $config->baseUri);
        self::assertFalse($config->verifyPeer);
    }

    public function testMissingConfigurationFailsWithAClearMessage(): void
    {
        SharedClient::reset();
        putenv('IT_TOOLS_API_KEY');

        $this->expectException(ItToolsException::class);
        $this->expectExceptionMessage('it_tools_configure()');
        it_tools_client();
    }
}
