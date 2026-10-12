<?php

declare(strict_types=1);

namespace Plateformweb\ItTools;

use InvalidArgumentException;

/**
 * Fichier a envoyer a l'API, depuis le disque ou depuis une chaine en memoire.
 */
final class File
{
    private function __construct(
        public readonly string $filename,
        public readonly string $mimeType,
        private readonly ?string $path,
        private readonly ?string $contents,
    ) {
    }

    public static function fromPath(string $path, ?string $filename = null, ?string $mimeType = null): self
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException(sprintf('Fichier introuvable ou illisible : "%s".', $path));
        }

        $detected = $mimeType;
        if (null === $detected && \function_exists('mime_content_type')) {
            $mime = mime_content_type($path);
            $detected = false === $mime ? null : $mime;
        }

        return new self($filename ?? basename($path), $detected ?? 'application/octet-stream', $path, null);
    }

    public static function fromContents(string $contents, string $filename, string $mimeType = 'application/octet-stream'): self
    {
        if ('' === $filename) {
            throw new InvalidArgumentException('Le nom de fichier ne peut pas etre vide.');
        }

        return new self($filename, $mimeType, null, $contents);
    }

    public static function from(self|string $file): self
    {
        return $file instanceof self ? $file : self::fromPath($file);
    }

    public function path(): ?string
    {
        return $this->path;
    }

    public function contents(): string
    {
        if (null !== $this->contents) {
            return $this->contents;
        }

        $data = file_get_contents((string) $this->path);
        if (false === $data) {
            throw new InvalidArgumentException(sprintf('Lecture impossible : "%s".', $this->path));
        }

        return $data;
    }
}
