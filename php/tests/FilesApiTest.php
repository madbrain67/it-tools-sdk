<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Exception\ItToolsException;
use Plateformweb\ItTools\File;
use Plateformweb\ItTools\Tests\Support\FakeTransport;
use Plateformweb\ItTools\Transport\HttpResponse;

final class FilesApiTest extends TestCase
{
    private FakeTransport $transport;
    private Client $client;

    protected function setUp(): void
    {
        $this->transport = new FakeTransport();
        $this->client = Client::create('pk_a', 'sk_b', 'https://a.test', transport: $this->transport);
    }

    public function testFileFromMissingPathIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        File::fromPath(__DIR__ . '/inexistant.pdf');
    }

    public function testMergeSendsEveryPdfUnderTheSameFieldName(): void
    {
        $this->transport->queue(FakeTransport::json(['download_url' => 'https://a.test/api/v1/pdf/download/m.pdf/tok']));

        $result = $this->client->pdf()->merge([
            File::fromContents('%PDF-a', 'a.pdf', 'application/pdf'),
            File::fromContents('%PDF-b', 'b.pdf', 'application/pdf'),
        ]);

        $request = $this->transport->last();
        self::assertSame('https://a.test/api/v1/pdf/merge', $request->url);
        self::assertNull($request->body);
        self::assertCount(2, $request->files['pdfs']);
        self::assertSame('b.pdf', $request->files['pdfs'][1]->filename);
        self::assertArrayNotHasKey('Content-Type', $request->headers, 'cURL doit fixer le Content-Type multipart et sa frontiere.');
        self::assertTrue($result->hasDownload());
    }

    public function testBooleansAndNumbersBecomeMultipartStrings(): void
    {
        $this->client->pdf()->protect(File::fromContents('x', 'a.pdf'), 'secret', allowPrint: true, allowCopy: false);

        self::assertSame(
            ['userPassword' => 'secret', 'allowPrint' => 'true', 'allowCopy' => 'false'],
            $this->transport->last()->fields,
        );
    }

    public function testOptionalExtraFileIsAttached(): void
    {
        $this->client->pdf()->addWatermark(File::fromContents('x', 'a.pdf'), 'image', watermarkImage: File::fromContents('png', 'w.png'));

        self::assertSame(['pdf', 'watermarkImage'], array_keys($this->transport->last()->files));
    }

    public function testInfoReturnsDecodedMetadata(): void
    {
        $this->transport->queue(FakeTransport::json(['info' => ['pages' => 3]]));

        self::assertSame(['info' => ['pages' => 3]], $this->client->pdf()->info(File::fromContents('x', 'a.pdf')));
    }

    public function testProcessedFileDownloadsWithCredentialsAndSaves(): void
    {
        $this->transport->queue(
            FakeTransport::json(['download_url' => 'https://a.test/api/v1/pdf/download/m.pdf/tok']),
            new HttpResponse(200, ['content-type' => 'application/pdf'], '%PDF-final'),
        );

        $target = tempnam(sys_get_temp_dir(), 'itt');
        try {
            $bytes = $this->client->pdf()->repair(File::fromContents('x', 'a.pdf'))->save($target);

            self::assertSame(10, $bytes);
            self::assertSame('%PDF-final', file_get_contents($target));
            self::assertSame('GET', $this->transport->last()->method);
            self::assertSame('sk_b', $this->transport->last()->headers['X-Api-Secret']);
        } finally {
            @unlink($target);
        }
    }

    public function testProcessedFileWithoutDownloadUrlFailsClearly(): void
    {
        $this->transport->queue(FakeTransport::json(['info' => 'x']));
        $result = $this->client->pdf()->repair(File::fromContents('x', 'a.pdf'));

        self::assertFalse($result->hasDownload());
        $this->expectException(ItToolsException::class);
        $result->content();
    }

    public function testCerfaGenerateSendsEmptyDataAsObject(): void
    {
        $this->client->cerfa()->generate('10103-14', []);

        self::assertSame('https://a.test/api/v1/cerfa/10103-14/generate', $this->transport->last()->url);
        self::assertSame('{"data":{}}', $this->transport->last()->body);
    }

    public function testPathSegmentsAreEncoded(): void
    {
        $this->client->cerfa()->fields('../x?y');

        self::assertSame('https://a.test/api/v1/cerfa/..%2Fx%3Fy/fields', $this->transport->last()->url);
    }

    public function testBookletEncodesSectionsAndFiles(): void
    {
        $this->client->booklet()->create(
            'Titre',
            sections: [['title' => 'A', 'text' => 'é']],
            logoImage: File::fromContents('png', 'l.png'),
            qrLogoKey: 'github',
        );

        $request = $this->transport->last();
        self::assertSame('Titre', $request->fields['title']);
        self::assertSame('[{"title":"A","text":"é"}]', $request->fields['sections']);
        self::assertSame('github', $request->fields['qrLogoKey']);
        self::assertSame(['logoImage'], array_keys($request->files));
    }

    public function testBookletBinaryAssetsUseQueryString(): void
    {
        $this->transport->queue(new HttpResponse(200, [], '<svg/>'));

        self::assertSame('<svg/>', $this->client->booklet()->qrCode('mon-slug', 'svg'));
        self::assertSame('https://a.test/api/v1/tools/booklet/mon-slug/qrcode?format=svg', $this->transport->last()->url);
    }

    public function testImageKitJoinsListsAndPollsUntilDone(): void
    {
        $this->client->imageKit()->create(File::fromContents('img', 'a.png'), ['desktop', 'mobile'], ['webp'], watermarkOpacity: 0.5);
        self::assertSame('desktop,mobile', $this->transport->last()->fields['presets']);
        self::assertSame('webp', $this->transport->last()->fields['formats']);
        self::assertSame('0.5', $this->transport->last()->fields['watermarkOpacity']);

        $this->transport->queue(
            FakeTransport::json(['status' => 'processing']),
            FakeTransport::json(['status' => 'done', 'download_url' => 'https://a.test/api/v1/tools/image-kit/download/k.zip/tok']),
        );
        $final = $this->client->imageKit()->waitUntilDone(12, 'owner', timeout: 5.0, interval: 0.0);

        self::assertSame('done', $final['status']);
        self::assertSame('https://a.test/api/v1/tools/image-kit/12/status?ownerToken=owner', $this->transport->last()->url);
    }

    public function testImageKitFailedJobThrowsWithApiMessage(): void
    {
        $this->transport->queue(FakeTransport::json(['status' => 'failed', 'message' => 'Image illisible.']));

        $this->expectException(ItToolsException::class);
        $this->expectExceptionMessage('Image illisible.');
        $this->client->imageKit()->waitUntilDone(1, interval: 0.0);
    }

    public function testImageKitTimesOut(): void
    {
        $this->transport->queue(FakeTransport::json(['status' => 'pending']));

        $this->expectException(ItToolsException::class);
        $this->client->imageKit()->waitUntilDone(1, timeout: 0.0, interval: 0.0);
    }

    public function testToolsConvertImageReturnsBinaryBody(): void
    {
        $this->transport->queue(new HttpResponse(200, ['content-type' => 'image/webp'], 'RIFFwebp'));

        $bytes = $this->client->tools()->convertImage(File::fromContents('png', 'a.png'), 'webp', 80);

        self::assertSame('RIFFwebp', $bytes);
        self::assertSame(['format' => 'webp', 'quality' => '80'], $this->transport->last()->fields);
    }

    public function testNumberToWordsAndImageFormats(): void
    {
        $this->client->tools()->numberToWords(1234.5, 'EUR');
        self::assertSame(['amount' => '1234.5', 'currency' => 'EUR'], $this->transport->lastJson());

        $this->transport->queue(FakeTransport::json(['webp', 'jpg']));
        self::assertSame(['webp', 'jpg'], $this->client->tools()->imageFormats());
    }

    public function testQrCodeGenerateSendsOnlyGivenFields(): void
    {
        $this->client->qrCode()->generate('https://x.test', format: 'png', size: 200);

        self::assertSame(['content' => 'https://x.test', 'format' => 'png', 'size' => 200], $this->transport->lastJson());
    }
}
