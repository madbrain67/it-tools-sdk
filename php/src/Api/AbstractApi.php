<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Api;

use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\File;
use Plateformweb\ItTools\Model\ProcessedFile;

abstract class AbstractApi
{
    public function __construct(protected readonly Client $client)
    {
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    protected static function clean(array $values): array
    {
        return array_filter($values, static fn (mixed $v): bool => null !== $v);
    }

    /**
     * Les champs multipart sont des chaines : les booleens deviennent "true"/"false".
     *
     * @param array<string, mixed> $values
     *
     * @return array<string, scalar|list<scalar>>
     */
    protected static function multipartFields(array $values): array
    {
        $fields = [];
        foreach (self::clean($values) as $name => $value) {
            $fields[$name] = match (true) {
                \is_bool($value) => $value ? 'true' : 'false',
                \is_array($value) => array_map(static fn (mixed $v): string => (string) $v, array_values($value)),
                default => (string) $value,
            };
        }

        return $fields;
    }

    /**
     * @param array<string, scalar|list<scalar>> $fields
     * @param array<string, File|list<File>>     $files
     *
     * @return array<array-key, mixed>
     */
    protected function postMultipart(string $path, array $fields, array $files): array
    {
        return $this->client->send('POST', $path, null, $fields, $files)->json();
    }

    /**
     * @param array<string, scalar|list<scalar>> $fields
     * @param array<string, File|list<File>>     $files
     */
    protected function processMultipart(string $path, array $fields, array $files): ProcessedFile
    {
        return new ProcessedFile($this->client, $this->postMultipart($path, $fields, $files));
    }

    protected static function segment(string $value): string
    {
        return rawurlencode($value);
    }
}
