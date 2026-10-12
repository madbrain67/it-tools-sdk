<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Transport;

use JsonException;
use Plateformweb\ItTools\Exception\ItToolsException;

final class HttpResponse
{
    /** @var array<string, string> */
    public readonly array $headers;

    /**
     * @param array<string, string> $headers noms d'en-tetes, insensibles a la casse
     */
    public function __construct(
        public readonly int $status,
        array $headers,
        public readonly string $body,
    ) {
        $this->headers = array_change_key_case($headers, \CASE_LOWER);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function isSuccessful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function isJson(): bool
    {
        return str_contains(strtolower($this->header('content-type') ?? ''), 'json');
    }

    /**
     * @return array<array-key, mixed>
     */
    public function json(): array
    {
        try {
            $decoded = json_decode($this->body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ItToolsException('Reponse JSON invalide : ' . $e->getMessage(), 0, $e);
        }

        if (!\is_array($decoded)) {
            throw new ItToolsException('Reponse JSON inattendue : objet ou tableau attendu.');
        }

        return $decoded;
    }
}
