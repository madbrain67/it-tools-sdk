<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Exception;

use Plateformweb\ItTools\Transport\HttpResponse;

/**
 * L'API a repondu avec un statut HTTP d'erreur.
 */
class ApiException extends ItToolsException
{
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly string $responseBody = '',
    ) {
        parent::__construct($message, $statusCode);
    }

    public static function fromResponse(HttpResponse $response): self
    {
        $message = null;
        $decoded = json_decode($response->body, true);
        if (\is_array($decoded) && \is_string($decoded['message'] ?? null) && '' !== $decoded['message']) {
            $message = $decoded['message'];
        }

        $message ??= sprintf('Erreur HTTP %d.', $response->status);
        $class = match (true) {
            401 === $response->status, 403 === $response->status => AuthenticationException::class,
            404 === $response->status => NotFoundException::class,
            400 === $response->status, 422 === $response->status => ValidationException::class,
            413 === $response->status => PayloadTooLargeException::class,
            415 === $response->status => UnsupportedMediaTypeException::class,
            429 === $response->status => QuotaExceededException::class,
            $response->status >= 500 => ServerException::class,
            default => self::class,
        };

        return new $class($message, $response->status, $response->body);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function responseBody(): string
    {
        return $this->responseBody;
    }
}
