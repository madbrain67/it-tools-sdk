<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Api;

use Plateformweb\ItTools\File;

/**
 * QR codes (`/api/v1/tools/qrcode/*`).
 */
final class QrCodeApi extends AbstractApi
{
    private const BASE = '/api/v1/tools/qrcode';

    /**
     * @return array<array-key, mixed> logos predefinis disponibles
     */
    public function logos(): array
    {
        return $this->client->requestJson('GET', self::BASE . '/logos');
    }

    /**
     * Les champs `$honeypot`, `$formElapsedMs` et `$altchaPayload` ne concernent que la creation
     * anonyme d'un QR code de type `url` ; ils sont inutiles avec des cles API.
     *
     * @param string|null $type   url (defaut), text, email, phone, sms, wifi, vcard ou geo
     * @param string|null $format svg (defaut) ou png
     *
     * @return array<array-key, mixed> le QR code est un data URI base64
     */
    public function generate(
        string $content,
        ?string $type = null,
        ?string $format = null,
        ?int $size = null,
        ?string $honeypot = null,
        ?int $formElapsedMs = null,
        ?string $altchaPayload = null,
    ): array {
        return $this->client->requestJson('POST', self::BASE . '/generate', self::clean([
            'content' => $content,
            'type' => $type,
            'format' => $format,
            'size' => $size,
            'honeypot' => $honeypot,
            'formElapsedMs' => $formElapsedMs,
            'altchaPayload' => $altchaPayload,
        ]));
    }

    /**
     * Convertit une image en logo SVG utilisable dans un QR code.
     *
     * @param string|null $shape circle (defaut) ou square
     *
     * @return array<array-key, mixed>
     */
    public function iconToSvg(File|string $file, ?string $bgColor = null, ?string $shape = null, ?bool $transparent = null): array
    {
        return $this->postMultipart(
            self::BASE . '/icon-to-svg',
            self::multipartFields(['bgColor' => $bgColor, 'shape' => $shape, 'transparent' => $transparent]),
            ['file' => File::from($file)],
        );
    }
}
