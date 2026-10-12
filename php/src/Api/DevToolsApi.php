<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Api;

/**
 * Outils developpeur (`/api/v1/tools/dev/*`). Chaque methode renvoie la reponse JSON
 * decodee ; la valeur calculee se trouve sous la cle `result`.
 */
final class DevToolsApi extends AbstractApi
{
    private const BASE = '/api/v1/tools/dev';

    /**
     * @param string|array<array-key, mixed> $json chaine JSON, ou structure a encoder
     * @param int|null                       $indent 2 ou 4
     *
     * @return array<array-key, mixed>
     */
    public function jsonFormat(string|array $json, ?int $indent = null): array
    {
        return $this->post('/json/format', ['json' => $json, 'indent' => $indent]);
    }

    /**
     * @param string|array<array-key, mixed> $json
     *
     * @return array<array-key, mixed>
     */
    public function jsonMinify(string|array $json): array
    {
        return $this->post('/json/minify', ['json' => $json]);
    }

    /**
     * @param string|null $direction `json-to-yaml` (defaut) ou `yaml-to-json`
     *
     * @return array<array-key, mixed>
     */
    public function jsonYaml(string $input, ?string $direction = null, ?int $indent = null): array
    {
        return $this->post('/json-yaml/convert', ['input' => $input, 'direction' => $direction, 'indent' => $indent]);
    }

    /**
     * @param string|array<array-key, mixed> $json      tableau d'objets
     * @param string|null                    $separator `,` `;` `|` ou `tab`
     *
     * @return array<array-key, mixed>
     */
    public function jsonToCsv(string|array $json, ?string $separator = null, ?bool $headers = null, ?bool $neutralizeFormulas = null): array
    {
        return $this->post('/json-to-csv/convert', [
            'json' => $json,
            'separator' => $separator,
            'headers' => $headers,
            'neutralizeFormulas' => $neutralizeFormulas,
        ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function jwtDecode(string $token): array
    {
        return $this->post('/jwt/decode', ['token' => $token]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function ipv4Subnet(string $cidr): array
    {
        return $this->post('/ipv4-subnet/calculate', ['cidr' => $cidr]);
    }

    /**
     * Fournir un seul de `$octal`, `$symbolic` ou `$permissions`.
     *
     * @param array<string, array<string, bool>>|null $permissions ex. ['owner' => ['r' => true, 'w' => true, 'x' => true], ...]
     *
     * @return array<array-key, mixed>
     */
    public function chmod(?string $octal = null, ?string $symbolic = null, ?array $permissions = null): array
    {
        return $this->post('/chmod/calculate', ['octal' => $octal, 'symbolic' => $symbolic, 'permissions' => $permissions]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function urlParse(string $url): array
    {
        return $this->post('/url/parse', ['url' => $url]);
    }

    /**
     * @param string|null $separator `-` `_` ou `.`
     *
     * @return array<array-key, mixed>
     */
    public function slugify(string $text, ?string $separator = null, ?bool $lowercase = null): array
    {
        return $this->post('/slugify', ['text' => $text, 'separator' => $separator, 'lowercase' => $lowercase]);
    }

    /**
     * @param string|null $case lower, upper, title, camel, pascal, snake, kebab, screaming, dot, constant, sentence, alternating
     *
     * @return array<array-key, mixed>
     */
    public function convertCase(string $text, ?string $case = null): array
    {
        return $this->post('/case/convert', ['text' => $text, 'case' => $case]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function textStatistics(string $text): array
    {
        return $this->post('/text-statistics', ['text' => $text]);
    }

    /**
     * @param string|null $mode `encode` (defaut) ou `decode`
     *
     * @return array<array-key, mixed>
     */
    public function htmlEntities(string $text, ?string $mode = null, ?bool $encodeAll = null): array
    {
        return $this->post('/html-entities/convert', ['text' => $text, 'mode' => $mode, 'encodeAll' => $encodeAll]);
    }

    /**
     * @param string|null $mode `encode` (defaut) ou `decode`
     *
     * @return array<array-key, mixed>
     */
    public function base64(string $text, ?string $mode = null, ?bool $urlSafe = null): array
    {
        return $this->post('/base64/convert', ['text' => $text, 'mode' => $mode, 'urlSafe' => $urlSafe]);
    }

    /**
     * @param list<string>|null $algorithms sha1, sha256, sha384, sha512 (tous par defaut)
     *
     * @return array<array-key, mixed>
     */
    public function hash(string $text, ?array $algorithms = null): array
    {
        return $this->post('/hash', ['text' => $text, 'algorithms' => $algorithms]);
    }

    /**
     * @param string|null $version `v4` (defaut) ou `v7`
     *
     * @return array<array-key, mixed>
     */
    public function uuid(?int $count = null, ?string $version = null, ?bool $uppercase = null, ?bool $withoutDashes = null): array
    {
        return $this->post('/uuid', ['count' => $count, 'version' => $version, 'uppercase' => $uppercase, 'withoutDashes' => $withoutDashes]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function password(
        ?int $count = null,
        ?int $length = null,
        ?bool $lowercase = null,
        ?bool $uppercase = null,
        ?bool $numbers = null,
        ?bool $symbols = null,
        ?bool $excludeSimilar = null,
    ): array {
        return $this->post('/password', [
            'count' => $count,
            'length' => $length,
            'lowercase' => $lowercase,
            'uppercase' => $uppercase,
            'numbers' => $numbers,
            'symbols' => $symbols,
            'excludeSimilar' => $excludeSimilar,
        ]);
    }

    /**
     * @param string|null $format hex, base64, alphanumeric, numeric, upper, lower ou custom
     * @param string|null $custom jeu de caracteres (format `custom`)
     *
     * @return array<array-key, mixed>
     */
    public function token(?int $count = null, ?int $length = null, ?string $format = null, ?string $custom = null): array
    {
        return $this->post('/token', ['count' => $count, 'length' => $length, 'format' => $format, 'custom' => $custom]);
    }

    /**
     * @param int|float|string|null $value    timestamp Unix ou date ISO 8601 (maintenant si omis)
     * @param string|null           $timezone ex. `Europe/Paris`
     *
     * @return array<array-key, mixed>
     */
    public function timestamp(int|float|string|null $value = null, ?string $timezone = null): array
    {
        return $this->post('/timestamp/convert', ['value' => $value, 'timezone' => $timezone]);
    }

    /**
     * @param string|null $unit days (defaut), weeks, months ou years
     *
     * @return array<array-key, mixed>
     */
    public function dateAdd(string $date, int $value, ?string $unit = null): array
    {
        return $this->post('/date/add', ['date' => $date, 'value' => $value, 'unit' => $unit]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function dateDiff(string $from, string $to): array
    {
        return $this->post('/date/diff', ['from' => $from, 'to' => $to]);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function fakeDataFields(): array
    {
        return $this->client->requestJson('GET', self::BASE . '/fake-data/fields');
    }

    /**
     * @param list<string> $fields cles issues de {@see fakeDataFields()}
     * @param string|null  $locale fr (defaut), en, de, es, it, nl, pl, pt_br, ru, ja, zh_cn, ar
     *
     * @return array<array-key, mixed>
     */
    public function fakeData(array $fields, ?int $count = null, ?string $locale = null, ?int $seed = null): array
    {
        return $this->post('/fake-data', ['fields' => $fields, 'count' => $count, 'locale' => $locale, 'seed' => $seed]);
    }

    /**
     * @param string|null $mode   paragraphs (defaut), sentences ou words
     * @param string|null $format plain (defaut) ou html
     *
     * @return array<array-key, mixed>
     */
    public function lorem(
        ?string $mode = null,
        ?int $count = null,
        ?int $minWords = null,
        ?int $maxWords = null,
        ?bool $startWithLorem = null,
        ?string $format = null,
        ?int $seed = null,
    ): array {
        return $this->post('/lorem', [
            'mode' => $mode,
            'count' => $count,
            'minWords' => $minWords,
            'maxWords' => $maxWords,
            'startWithLorem' => $startWithLorem,
            'format' => $format,
            'seed' => $seed,
        ]);
    }

    /**
     * @param string|array{r: int, g: int, b: int} $color `#RGB`, `#RRGGBB`, `rgb(r, g, b)` ou objet {r, g, b}
     *
     * @return array<array-key, mixed>
     */
    public function color(string|array $color): array
    {
        return $this->post('/color/convert', ['color' => $color]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<array-key, mixed>
     */
    private function post(string $path, array $payload): array
    {
        return $this->client->requestJson('POST', self::BASE . $path, self::clean($payload));
    }
}
