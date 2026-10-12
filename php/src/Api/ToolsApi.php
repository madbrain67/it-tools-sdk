<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Api;

use Plateformweb\ItTools\File;
use Plateformweb\ItTools\Model\ProcessedFile;

/**
 * Outils divers (`/api/v1/tools/*`) : montants en lettres, images, documents Office.
 */
final class ToolsApi extends AbstractApi
{
    private const BASE = '/api/v1/tools';

    /**
     * @return array<array-key, mixed> devises : `code`, `symbol`, `singular`, `plural`, `locale`, `fractionDigits`, `label`
     */
    public function currencies(): array
    {
        return $this->client->requestJson('GET', self::BASE . '/number-to-words/currencies');
    }

    /**
     * @param string|null $currency code de devise, voir {@see currencies()}
     *
     * @return array<array-key, mixed> le texte est sous la cle `result`
     */
    public function numberToWords(int|float|string $amount, ?string $currency = null): array
    {
        return $this->client->requestJson('POST', self::BASE . '/number-to-words/convert', self::clean([
            'amount' => (string) $amount,
            'currency' => $currency,
        ]));
    }

    /**
     * @return list<string> formats d'image pris en charge
     */
    public function imageFormats(): array
    {
        /** @var list<string> $formats */
        $formats = array_values($this->client->requestJson('GET', self::BASE . '/image/formats'));

        return $formats;
    }

    /**
     * @param string|null $format  webp (defaut), avif, jpg, png, gif ou bmp
     * @param int|null    $quality qualite (defaut 85)
     * @param int|null    $maxWidth largeur maximale en pixels
     *
     * @return string image convertie (binaire)
     */
    public function convertImage(File|string $file, ?string $format = null, ?int $quality = null, ?int $maxWidth = null): string
    {
        return $this->client->send(
            'POST',
            self::BASE . '/image/convert',
            fields: self::multipartFields(['format' => $format, 'quality' => $quality, 'maxWidth' => $maxWidth]),
            files: ['file' => File::from($file)],
        )->body;
    }

    public function officeToPdf(File|string $file): ProcessedFile
    {
        return $this->processMultipart(self::BASE . '/office-to-pdf', [], ['file' => File::from($file)]);
    }

    public function pdfToWord(File|string $file): ProcessedFile
    {
        return $this->processMultipart(self::BASE . '/pdf-to-word', [], ['file' => File::from($file)]);
    }
}
