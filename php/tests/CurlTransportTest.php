<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Tests;

use PHPUnit\Framework\TestCase;
use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Exception\ApiException;
use Plateformweb\ItTools\Exception\TransportException;
use Plateformweb\ItTools\File;

/**
 * Valide le transport cURL contre un vrai serveur HTTP (serveur integre de PHP).
 */
final class CurlTransportTest extends TestCase
{
    private static mixed $process = null;
    private static int $port = 0;

    public static function setUpBeforeClass(): void
    {
        if (!\function_exists('proc_open')) {
            self::markTestSkipped('proc_open indisponible.');
        }

        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if (false === $socket) {
            self::markTestSkipped('Aucun port libre : ' . $error);
        }
        self::$port = (int) substr((string) stream_socket_get_name($socket, false), (int) strrpos((string) stream_socket_get_name($socket, false), ':') + 1);
        fclose($socket);

        $command = [\PHP_BINARY, '-d', 'xdebug.mode=off', '-S', '127.0.0.1:' . self::$port, __DIR__ . '/Support/echo-server.php'];
        $null = \DIRECTORY_SEPARATOR === '\\' ? 'NUL' : '/dev/null';
        self::$process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $null, 'w'], 2 => ['file', $null, 'w']], $pipes);

        for ($i = 0; $i < 50; ++$i) {
            $connection = @fsockopen('127.0.0.1', self::$port);
            if (false !== $connection) {
                fclose($connection);

                return;
            }
            usleep(100_000);
        }

        self::markTestSkipped('Serveur de test non demarre.');
    }

    public static function tearDownAfterClass(): void
    {
        if (\is_resource(self::$process)) {
            proc_terminate(self::$process);
            proc_close(self::$process);
        }
    }

    private function client(float $timeout = 10.0): Client
    {
        return Client::create('pk_test', 'sk_test', 'http://127.0.0.1:' . self::$port, $timeout);
    }

    public function testJsonRequestCarriesAuthHeadersAndBody(): void
    {
        $echo = $this->client()->requestJson('POST', '/echo', ['a' => 'é', 'n' => 1], ['q' => 'x y']);

        self::assertSame('POST', $echo['method']);
        self::assertSame('/echo?q=x%20y', $echo['uri']);
        self::assertSame('pk_test', $echo['headers']['x-api-key']);
        self::assertSame('sk_test', $echo['headers']['x-api-secret']);
        self::assertSame('application/json', $echo['headers']['content-type']);
        self::assertStringStartsWith('plateformweb-it-tools-php/', $echo['headers']['user-agent']);
        self::assertSame('{"a":"é","n":1}', $echo['body']);
    }

    public function testMultipartSendsFieldsAndEveryFile(): void
    {
        $response = $this->client()->send(
            'POST',
            '/echo',
            fields: ['mode' => 'ranges', 'ids' => ['1', '2']],
            files: [
                'pdfs' => [
                    File::fromContents('%PDF-a', 'a.pdf', 'application/pdf'),
                    File::fromContents('%PDF-b', 'b.pdf', 'application/pdf'),
                ],
                'logo' => File::fromContents('PNGDATA', 'logo.png', 'image/png'),
            ],
        );
        $echo = $response->json();

        self::assertStringStartsWith('multipart/form-data; boundary=', $echo['headers']['content-type']);
        self::assertSame(['mode' => 'ranges', 'ids' => ['1', '2']], $echo['post']);

        $byName = [];
        foreach ($echo['files'] as $file) {
            $byName[$file['field'] . ':' . $file['index']] = $file;
        }
        self::assertSame('a.pdf', $byName['pdfs:0']['name']);
        self::assertSame('%PDF-b', $byName['pdfs:1']['content']);
        self::assertSame('image/png', $byName['logo:0']['type']);
        self::assertSame('PNGDATA', $byName['logo:0']['content']);
    }

    public function testFileFromPathIsUploadedWithItsName(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'itt');
        file_put_contents($path, "contenu disque\n");
        try {
            $echo = $this->client()->send('POST', '/echo', files: ['file' => File::fromPath($path, 'rapport.txt', 'text/plain')])->json();
        } finally {
            @unlink($path);
        }

        self::assertSame('rapport.txt', $echo['files'][0]['name']);
        self::assertSame("contenu disque\n", $echo['files'][0]['content']);
    }

    public function testErrorStatusBecomesApiExceptionWithApiMessage(): void
    {
        try {
            $this->client()->requestJson('GET', '/status/418');
            self::fail('Exception attendue.');
        } catch (ApiException $e) {
            self::assertSame(418, $e->statusCode());
            self::assertSame('Je suis une theiere.', $e->getMessage());
        }
    }

    public function testRedirectsAreNotFollowedSoCredentialsNeverLeave(): void
    {
        try {
            $this->client()->send('GET', '/redirect');
            self::fail('Exception attendue.');
        } catch (ApiException $e) {
            self::assertSame(302, $e->statusCode());
        }
    }

    public function testUnreachableHostRaisesTransportException(): void
    {
        $this->expectException(TransportException::class);
        Client::create('pk_a', null, 'http://127.0.0.1:1', 2.0)->requestJson('GET', '/x');
    }

    public function testTimeoutRaisesTransportException(): void
    {
        $this->expectException(TransportException::class);
        $this->client(0.3)->requestJson('GET', '/slow');
    }
}
