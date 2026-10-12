<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Transport;

use Plateformweb\ItTools\File;

final class HttpRequest
{
    /**
     * @param array<string, string>                     $headers
     * @param string|null                               $body    corps brut (JSON), exclusif avec $fields/$files
     * @param array<string, scalar|list<scalar>>        $fields  champs multipart
     * @param array<string, File|list<File>>            $files   fichiers multipart
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly array $headers = [],
        public readonly ?string $body = null,
        public readonly array $fields = [],
        public readonly array $files = [],
        public readonly float $timeout = 30.0,
        public readonly bool $verifyPeer = true,
    ) {
    }

    public function isMultipart(): bool
    {
        return [] !== $this->fields || [] !== $this->files;
    }
}
