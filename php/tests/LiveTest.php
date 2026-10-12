<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Config;
use Plateformweb\ItTools\Exception\AuthenticationException;
use Plateformweb\ItTools\Exception\ValidationException;
use Plateformweb\ItTools\File;

/**
 * Appelle l'API reelle (consomme du quota). Lancer avec `composer test:live` apres avoir defini
 * IT_TOOLS_API_KEY, IT_TOOLS_API_SECRET et, hors production, IT_TOOLS_BASE_URI / IT_TOOLS_VERIFY_PEER.
 */
#[Group('live')]
final class LiveTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $key = getenv('IT_TOOLS_API_KEY');
        if (false === $key || '' === $key) {
            self::markTestSkipped('IT_TOOLS_API_KEY non defini.');
        }

        $secret = getenv('IT_TOOLS_API_SECRET');
        $base = getenv('IT_TOOLS_BASE_URI');
        $verify = getenv('IT_TOOLS_VERIFY_PEER');
        $this->client = Client::create(
            $key,
            false === $secret ? null : $secret,
            false === $base || '' === $base ? Config::DEFAULT_BASE_URI : $base,
            verifyPeer: false === $verify || '' === $verify || filter_var($verify, \FILTER_VALIDATE_BOOL),
        );
    }

    public function testEveryDeveloperToolAnswersSuccessfully(): void
    {
        $dev = $this->client->dev();
        $calls = [
            'json/format' => fn () => $dev->jsonFormat('{"name":"Jean","age":30}', 4),
            'json/minify' => fn () => $dev->jsonMinify('{ "a": 1, "b": [1, 2] }'),
            'json-yaml' => fn () => $dev->jsonYaml('{"a":[1,2]}'),
            'json-to-csv' => fn () => $dev->jsonToCsv('[{"nom":"Alice","age":30},{"nom":"Bob","age":25}]', ';'),
            'jwt/decode' => fn () => $dev->jwtDecode('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.c2lnbmF0dXJl'),
            'ipv4-subnet' => fn () => $dev->ipv4Subnet('192.168.1.10/24'),
            'chmod' => fn () => $dev->chmod(octal: '755'),
            'url/parse' => fn () => $dev->urlParse('https://example.com:8080/path?foo=bar#section'),
            'slugify' => fn () => $dev->slugify('Été à Paris !'),
            'case/convert' => fn () => $dev->convertCase('hello world', 'camel'),
            'text-statistics' => fn () => $dev->textStatistics('Bonjour le monde.'),
            'html-entities' => fn () => $dev->htmlEntities('<a href="x">é</a>'),
            'base64' => fn () => $dev->base64('Bonjour'),
            'hash' => fn () => $dev->hash('abc', ['sha1', 'sha256']),
            'uuid' => fn () => $dev->uuid(2, 'v7'),
            'password' => fn () => $dev->password(2, 20, symbols: true),
            'token' => fn () => $dev->token(2, 16, 'hex'),
            'timestamp' => fn () => $dev->timestamp(1700000000, 'Europe/Paris'),
            'date/add' => fn () => $dev->dateAdd('2024-01-31', 7, 'days'),
            'date/diff' => fn () => $dev->dateDiff('2024-01-31', '2025-03-01'),
            'fake-data/fields' => fn () => $dev->fakeDataFields(),
            'fake-data' => fn () => $dev->fakeData(['prenom', 'nom', 'email'], 3, 'fr', 42),
            'lorem' => fn () => $dev->lorem('sentences', 2),
            'color' => fn () => $dev->color('#006EDC'),
        ];

        foreach ($calls as $tool => $call) {
            $response = $call();
            self::assertSame('success', $response['type'] ?? null, $tool);
        }

        self::assertSame('ete-a-paris', $dev->slugify('Été à Paris !')['result']);
        self::assertSame('Qm9uam91cg==', $dev->base64('Bonjour')['result']);
        self::assertSame('2024-02-07', $dev->dateAdd('2024-01-31', 7)['result']['date']);
    }

    public function testInvalidInputRaisesValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->client->dev()->jsonFormat('{bad');
    }

    public function testWrongSecretRaisesAuthenticationException(): void
    {
        $config = $this->client->config();
        $client = Client::create($config->apiKey, 'sk_' . str_repeat('0', 64), $config->baseUri, verifyPeer: $config->verifyPeer);

        $this->expectException(AuthenticationException::class);
        $client->dev()->uuid();
    }

    public function testCerfaGenerationReturnsAPdf(): void
    {
        $catalog = $this->client->cerfa()->catalog();
        self::assertNotEmpty($catalog['available'] ?? []);

        $cerfaId = $catalog['available'][0]['cerfaId'];
        self::assertNotEmpty($this->client->cerfa()->fields($cerfaId)['fields'] ?? []);
        self::assertArrayHasKey('data', $this->client->cerfa()->template($cerfaId));

        self::assertStringStartsWith('%PDF-', $this->client->cerfa()->generate($cerfaId, [])->content());
    }

    public function testPdfOperationsRoundTrip(): void
    {
        $source = File::fromContents(self::minimalPdf(), 'source.pdf', 'application/pdf');

        self::assertStringStartsWith('%PDF-', $this->client->pdf()->compress($source, 'low')->content());
        self::assertStringStartsWith('%PDF-', $this->client->pdf()->merge([$source, $source])->content());
        self::assertStringStartsWith('%PDF-', $this->client->pdf()->rotate($source, 90)->content());
    }

    /**
     * Isole de testPdfOperationsRoundTrip : /pdf/info repond 422 sur tout PDF dans l'environnement local, alors que les autres operations PDF fonctionnent.
     */
    public function testPdfInfo(): void
    {
        $info = $this->client->pdf()->info(File::fromContents(self::minimalPdf(), 'source.pdf', 'application/pdf'));

        self::assertArrayHasKey('info', $info);
    }

    /**
     * PDF d'une page vierge, avec table xref calculee.
     */
    private static function minimalPdf(): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>',
        ];

        $pdf = "%PDF-1.4
";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = \strlen($pdf);
            $pdf .= sprintf("%d 0 obj
%s
endobj
", $i + 1, $object);
        }

        $xref = \strlen($pdf);
        $pdf .= sprintf("xref
0 %d
0000000000 65535 f 
", \count($objects) + 1);
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n 
", $offset);
        }

        return $pdf . sprintf("trailer
<< /Size %d /Root 1 0 R >>
startxref
%d
%%%%EOF
", \count($objects) + 1, $xref);
    }

    public function testToolsEndpoints(): void
    {
        $tools = $this->client->tools();

        self::assertNotEmpty($tools->currencies());
        self::assertContains('webp', $tools->imageFormats());
        self::assertStringContainsString('euros', $tools->numberToWords('1234.56', 'EUR')['result']);
    }
}
