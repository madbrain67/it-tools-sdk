<?php

declare(strict_types=1);

namespace Plateformweb\ItTools;

use JsonException;
use Plateformweb\ItTools\Api\BookletApi;
use Plateformweb\ItTools\Api\CerfaApi;
use Plateformweb\ItTools\Api\DevToolsApi;
use Plateformweb\ItTools\Api\ImageKitApi;
use Plateformweb\ItTools\Api\PdfApi;
use Plateformweb\ItTools\Api\QrCodeApi;
use Plateformweb\ItTools\Api\ToolsApi;
use Plateformweb\ItTools\Exception\ApiException;
use Plateformweb\ItTools\Exception\ItToolsException;
use Plateformweb\ItTools\Transport\CurlTransport;
use Plateformweb\ItTools\Transport\HttpRequest;
use Plateformweb\ItTools\Transport\HttpResponse;
use Plateformweb\ItTools\Transport\TransportInterface;

final class Client
{
    public const VERSION = '1.0.0';

    private readonly TransportInterface $transport;

    private ?DevToolsApi $dev = null;
    private ?PdfApi $pdf = null;
    private ?CerfaApi $cerfa = null;
    private ?QrCodeApi $qrCode = null;
    private ?BookletApi $booklet = null;
    private ?ImageKitApi $imageKit = null;
    private ?ToolsApi $tools = null;

    public function __construct(
        private readonly Config $config,
        ?TransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport();
    }

    public static function create(
        string $apiKey,
        ?string $apiSecret = null,
        string $baseUri = Config::DEFAULT_BASE_URI,
        float $timeout = 30.0,
        bool $verifyPeer = true,
        ?TransportInterface $transport = null,
    ): self {
        return new self(new Config($apiKey, $apiSecret, $baseUri, $timeout, $verifyPeer), $transport);
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function dev(): DevToolsApi
    {
        return $this->dev ??= new DevToolsApi($this);
    }

    public function pdf(): PdfApi
    {
        return $this->pdf ??= new PdfApi($this);
    }

    public function cerfa(): CerfaApi
    {
        return $this->cerfa ??= new CerfaApi($this);
    }

    public function qrCode(): QrCodeApi
    {
        return $this->qrCode ??= new QrCodeApi($this);
    }

    public function booklet(): BookletApi
    {
        return $this->booklet ??= new BookletApi($this);
    }

    public function imageKit(): ImageKitApi
    {
        return $this->imageKit ??= new ImageKitApi($this);
    }

    public function tools(): ToolsApi
    {
        return $this->tools ??= new ToolsApi($this);
    }

    /**
     * @param array<string, scalar|null> $query
     * @param array<array-key, mixed>|null $json
     *
     * @return array<array-key, mixed>
     */
    public function requestJson(string $method, string $path, ?array $json = null, array $query = []): array
    {
        return $this->send($method, $path, $json, [], [], $query)->json();
    }

    /**
     * @param array<array-key, mixed>|null $json
     * @param array<string, scalar|list<scalar>>   $fields
     * @param array<string, File|list<File>>       $files
     * @param array<string, scalar|null>           $query
     */
    public function send(
        string $method,
        string $path,
        ?array $json = null,
        array $fields = [],
        array $files = [],
        array $query = [],
    ): HttpResponse {
        $headers = [
            'Accept' => 'application/json, */*',
            'X-Api-Key' => $this->config->apiKey,
            'User-Agent' => 'plateformweb-it-tools-php/' . self::VERSION,
        ];
        if (null !== $this->config->apiSecret) {
            $headers['X-Api-Secret'] = $this->config->apiSecret;
        }

        $body = null;
        if (null !== $json) {
            try {
                $body = [] === $json ? '{}' : json_encode($json, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
            } catch (JsonException $e) {
                throw new ItToolsException('Corps JSON non encodable : ' . $e->getMessage(), 0, $e);
            }
            $headers['Content-Type'] = 'application/json';
        }

        $url = $this->config->baseUri . $path;
        $query = array_filter($query, static fn (mixed $v): bool => null !== $v);
        if ([] !== $query) {
            $url .= '?' . http_build_query($query, '', '&', \PHP_QUERY_RFC3986);
        }

        $response = $this->transport->send(new HttpRequest(
            method: $method,
            url: $url,
            headers: $headers,
            body: $body,
            fields: $fields,
            files: $files,
            timeout: $this->config->timeout,
            verifyPeer: $this->config->verifyPeer,
        ));

        // Les redirections ne sont jamais suivies (les cles API ne doivent pas quitter l'hote configure) : un 3xx est une anomalie.
        if ($response->status >= 300) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }

    /**
     * Telecharge un lien `download_url`. Seul l'hote configure est joignable : les cles API
     * ne sont jamais envoyees a un autre serveur, quel que soit le lien recu.
     */
    public function download(string $url): string
    {
        $target = parse_url($url);
        $base = parse_url($this->config->baseUri);
        if (false === $target || false === $base) {
            throw new ItToolsException('Lien de telechargement invalide.');
        }

        if (isset($target['host'])) {
            $sameHost = strtolower($target['host']) === strtolower((string) $base['host']);
            $basePort = $base['port'] ?? ('http' === ($base['scheme'] ?? 'https') ? 80 : 443);
            $samePort = !isset($target['port']) || $target['port'] === $basePort;
            if (!$sameHost || !$samePort) {
                throw new ItToolsException('Lien de telechargement refuse : hote different de celui de l\'API.');
            }
        }

        $path = ($target['path'] ?? '/') . (isset($target['query']) ? '?' . $target['query'] : '');
        if (!str_starts_with($path, '/')) {
            throw new ItToolsException('Lien de telechargement invalide.');
        }

        return $this->send('GET', $path)->body;
    }
}
