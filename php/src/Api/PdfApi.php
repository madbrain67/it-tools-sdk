<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Api;

use Plateformweb\ItTools\File;
use Plateformweb\ItTools\Model\ProcessedFile;

/**
 * Outils PDF (`/api/v1/pdf/*`). Les fichiers se passent en chemin ou en {@see File}.
 * Hors {@see info()}, chaque methode renvoie un {@see ProcessedFile} a telecharger.
 */
final class PdfApi extends AbstractApi
{
    private const BASE = '/api/v1/pdf';

    /**
     * @param list<File|string> $pdfs
     */
    public function merge(array $pdfs): ProcessedFile
    {
        return $this->processMultipart(self::BASE . '/merge', [], ['pdfs' => array_map(File::from(...), array_values($pdfs))]);
    }

    /**
     * @param string|null $mode   `all` (defaut) ou `ranges`
     * @param string|null $ranges ex. `1-3,5`
     */
    public function split(File|string $pdf, ?string $mode = null, ?string $ranges = null): ProcessedFile
    {
        return $this->run('split', $pdf, ['mode' => $mode, 'ranges' => $ranges]);
    }

    /**
     * @param string $pages ex. `2,4-6`
     */
    public function deletePages(File|string $pdf, string $pages): ProcessedFile
    {
        return $this->run('delete-pages', $pdf, ['pages' => $pages]);
    }

    public function repair(File|string $pdf): ProcessedFile
    {
        return $this->run('repair', $pdf, []);
    }

    /**
     * @param string|null $level `low`, `medium` (defaut) ou `high`
     */
    public function compress(File|string $pdf, ?string $level = null): ProcessedFile
    {
        return $this->run('compress', $pdf, ['level' => $level]);
    }

    /**
     * @param int|null    $dpi    72, 96, 150 (defaut) ou 300
     * @param string|null $format `png` (defaut) ou `jpg`
     */
    public function toImages(File|string $pdf, ?int $dpi = null, ?string $format = null): ProcessedFile
    {
        return $this->run('to-images', $pdf, ['dpi' => $dpi, 'format' => $format]);
    }

    /**
     * @param int|string|null $rotation   90 (defaut), 180 ou 270
     * @param string|null     $pageTarget all (defaut), odd, even ou specific
     * @param string|null     $pages      requis si `$pageTarget` vaut `specific`
     */
    public function rotate(File|string $pdf, int|string|null $rotation = null, ?string $pageTarget = null, ?string $pages = null): ProcessedFile
    {
        return $this->run('rotate', $pdf, ['rotation' => $rotation, 'pageTarget' => $pageTarget, 'pages' => $pages]);
    }

    public function protect(
        File|string $pdf,
        string $userPassword,
        ?string $ownerPassword = null,
        ?bool $allowPrint = null,
        ?bool $allowCopy = null,
    ): ProcessedFile {
        return $this->run('protect', $pdf, [
            'userPassword' => $userPassword,
            'ownerPassword' => $ownerPassword,
            'allowPrint' => $allowPrint,
            'allowCopy' => $allowCopy,
        ]);
    }

    public function unlock(File|string $pdf, ?string $password = null): ProcessedFile
    {
        return $this->run('unlock', $pdf, ['password' => $password]);
    }

    /**
     * @param string|null $action `flatten` (defaut) ou `extract`
     * @param string|null $pages  pages a extraire (action `extract`)
     */
    public function edit(File|string $pdf, ?string $action = null, ?string $pages = null): ProcessedFile
    {
        return $this->run('edit', $pdf, ['action' => $action, 'pages' => $pages]);
    }

    /**
     * @param string|null $type `text` (defaut) ou `image`
     */
    public function addWatermark(
        File|string $pdf,
        ?string $type = null,
        ?string $text = null,
        ?int $fontSize = null,
        ?int $opacity = null,
        File|string|null $watermarkImage = null,
    ): ProcessedFile {
        return $this->run(
            'add-watermark',
            $pdf,
            ['type' => $type, 'text' => $text, 'fontSize' => $fontSize, 'opacity' => $opacity],
            null === $watermarkImage ? [] : ['watermarkImage' => File::from($watermarkImage)],
        );
    }

    /**
     * @param string|null $position bottom-center (defaut), bottom-left, bottom-right, top-center, top-left, top-right
     */
    public function addPageNumbers(File|string $pdf, ?string $position = null, ?int $startFrom = null, ?int $fontSize = null): ProcessedFile
    {
        return $this->run('add-page-numbers', $pdf, ['position' => $position, 'startFrom' => $startFrom, 'fontSize' => $fontSize]);
    }

    /**
     * @param string|null $signatureType draw (defaut), upload ou text
     */
    public function sign(
        File|string $pdf,
        ?string $signatureType = null,
        File|string|null $signatureImage = null,
        ?string $signatureText = null,
        ?int $page = null,
    ): ProcessedFile {
        return $this->run(
            'sign',
            $pdf,
            ['signatureType' => $signatureType, 'signatureText' => $signatureText, 'page' => $page],
            null === $signatureImage ? [] : ['signatureImage' => File::from($signatureImage)],
        );
    }

    /**
     * @return array<array-key, mixed> metadonnees du PDF, sous la cle `info`
     */
    public function info(File|string $pdf): array
    {
        return $this->postMultipart(self::BASE . '/info', [], ['pdf' => File::from($pdf)]);
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, File>  $extraFiles
     */
    private function run(string $operation, File|string $pdf, array $fields, array $extraFiles = []): ProcessedFile
    {
        return $this->processMultipart(
            sprintf('%s/%s', self::BASE, $operation),
            self::multipartFields($fields),
            ['pdf' => File::from($pdf), ...$extraFiles],
        );
    }
}
