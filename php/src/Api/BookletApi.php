<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Api;

use JsonException;
use Plateformweb\ItTools\Exception\ItToolsException;
use Plateformweb\ItTools\File;

/**
 * Mini-sites booklet (`/api/v1/tools/booklet/*`).
 */
final class BookletApi extends AbstractApi
{
    private const BASE = '/api/v1/tools/booklet';

    /**
     * @param string|list<array<string, mixed>>|null $sections  liste de sections, ou sa forme JSON
     * @param string|null                            $fontFamily inter, poppins, roboto, montserrat, playfair-display ou lora
     * @param string|null                            $expiresAt  date `AAAA-MM-JJ`
     * @param string|null                            $qrMotifStyle square, dot ou round
     * @param string|null                            $qrEyeStyle square ou circle
     * @param string|null                            $qrLogoKey  cle d'un logo predefini, exclusive avec `$qrLogo`
     *
     * @return array<array-key, mixed>
     */
    public function create(
        string $title,
        ?string $subtitle = null,
        ?string $coverColor = null,
        ?string $headerColor = null,
        ?string $footerColor = null,
        ?string $fontFamily = null,
        File|string|null $coverImage = null,
        File|string|null $logoImage = null,
        string|array|null $sections = null,
        ?string $expiresAt = null,
        ?string $qrForeground = null,
        ?string $qrBackground = null,
        ?string $qrMotifStyle = null,
        ?string $qrEyeStyle = null,
        ?string $qrLogoKey = null,
        File|string|null $qrLogo = null,
    ): array {
        $files = [];
        foreach (['coverImage' => $coverImage, 'logoImage' => $logoImage, 'qrLogo' => $qrLogo] as $name => $file) {
            if (null !== $file) {
                $files[$name] = File::from($file);
            }
        }

        return $this->postMultipart(self::BASE . '/create', self::multipartFields([
            'title' => $title,
            'subtitle' => $subtitle,
            'coverColor' => $coverColor,
            'headerColor' => $headerColor,
            'footerColor' => $footerColor,
            'fontFamily' => $fontFamily,
            'sections' => \is_array($sections) ? $this->encodeSections($sections) : $sections,
            'expiresAt' => $expiresAt,
            'qrForeground' => $qrForeground,
            'qrBackground' => $qrBackground,
            'qrMotifStyle' => $qrMotifStyle,
            'qrEyeStyle' => $qrEyeStyle,
            'qrLogoKey' => $qrLogoKey,
        ]), $files);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function get(string $slug): array
    {
        return $this->client->requestJson('GET', sprintf('%s/%s', self::BASE, self::segment($slug)));
    }

    /**
     * @return string image de couverture (binaire)
     */
    public function cover(string $slug): string
    {
        return $this->client->send('GET', sprintf('%s/%s/cover', self::BASE, self::segment($slug)))->body;
    }

    /**
     * @return string logo (binaire)
     */
    public function logo(string $slug): string
    {
        return $this->client->send('GET', sprintf('%s/%s/logo', self::BASE, self::segment($slug)))->body;
    }

    /**
     * @param string|null $format svg (defaut) ou png
     *
     * @return string QR code (binaire ou SVG)
     */
    public function qrCode(string $slug, ?string $format = null): string
    {
        return $this->client->send(
            'GET',
            sprintf('%s/%s/qrcode', self::BASE, self::segment($slug)),
            query: ['format' => $format],
        )->body;
    }

    /**
     * @param list<array<string, mixed>> $sections
     */
    private function encodeSections(array $sections): string
    {
        try {
            return json_encode($sections, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            throw new ItToolsException('Sections non encodables en JSON : ' . $e->getMessage(), 0, $e);
        }
    }
}
