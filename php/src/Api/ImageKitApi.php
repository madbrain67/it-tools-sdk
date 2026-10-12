<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Api;

use Plateformweb\ItTools\Exception\ItToolsException;
use Plateformweb\ItTools\File;

/**
 * Kits d'images (`/api/v1/tools/image-kit/*`) : traitement asynchrone, a interroger par `jobId`.
 */
final class ImageKitApi extends AbstractApi
{
    private const BASE = '/api/v1/tools/image-kit';

    /**
     * @param string|list<string> $presets desktop, mobile, avatar
     * @param string|list<string>|null $formats avif, webp (les deux par defaut)
     * @param int|null                 $watermarkAngle   -90 a 90, diagonale automatique si omis
     * @param int|null                 $watermarkRepeat  1 a 12
     * @param float|null               $watermarkOpacity 0.1 a 1.0
     *
     * @return array<array-key, mixed> contient `jobId`
     */
    public function create(
        File|string $image,
        string|array $presets,
        string|array|null $formats = null,
        ?string $watermarkText = null,
        ?string $watermarkColor = null,
        ?int $watermarkAngle = null,
        ?int $watermarkRepeat = null,
        ?float $watermarkOpacity = null,
    ): array {
        return $this->postMultipart(self::BASE . '/create', self::multipartFields([
            'presets' => \is_array($presets) ? implode(',', $presets) : $presets,
            'formats' => \is_array($formats) ? implode(',', $formats) : $formats,
            'watermarkText' => $watermarkText,
            'watermarkColor' => $watermarkColor,
            'watermarkAngle' => $watermarkAngle,
            'watermarkRepeat' => $watermarkRepeat,
            'watermarkOpacity' => $watermarkOpacity,
        ]), ['image' => File::from($image)]);
    }

    /**
     * @return array<array-key, mixed> `status` (pending, processing, done, failed), `download_url`, `message`
     */
    public function status(int|string $jobId, ?string $ownerToken = null): array
    {
        return $this->client->requestJson(
            'GET',
            sprintf('%s/%s/status', self::BASE, self::segment((string) $jobId)),
            query: ['ownerToken' => $ownerToken],
        );
    }

    /**
     * Interroge le job jusqu'a son terme.
     *
     * @param float $timeout  duree maximale d'attente, en secondes
     * @param float $interval pause entre deux interrogations, en secondes
     *
     * @return array<array-key, mixed> statut final (`done`)
     */
    public function waitUntilDone(int|string $jobId, ?string $ownerToken = null, float $timeout = 120.0, float $interval = 2.0): array
    {
        $deadline = microtime(true) + $timeout;

        do {
            $status = $this->status($jobId, $ownerToken);
            $state = $status['status'] ?? null;

            if ('done' === $state) {
                return $status;
            }

            if ('failed' === $state) {
                throw new ItToolsException(\is_string($status['message'] ?? null) ? $status['message'] : 'Le traitement du kit d\'images a echoue.');
            }

            usleep((int) round($interval * 1_000_000));
        } while (microtime(true) < $deadline);

        throw new ItToolsException(sprintf('Kit d\'images %s non termine apres %s s.', $jobId, $timeout));
    }

    /**
     * @return string archive ZIP du kit
     */
    public function download(string $downloadUrl): string
    {
        return $this->client->download($downloadUrl);
    }
}
