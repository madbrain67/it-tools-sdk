<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Tests\Support;

use LogicException;
use Plateformweb\ItTools\Transport\HttpRequest;
use Plateformweb\ItTools\Transport\HttpResponse;
use Plateformweb\ItTools\Transport\TransportInterface;

final class FakeTransport implements TransportInterface
{
    /** @var list<HttpRequest> */
    public array $requests = [];

    /** @var list<HttpResponse> */
    private array $queue = [];

    public static function json(array $data = ['type' => 'success'], int $status = 200): HttpResponse
    {
        return new HttpResponse($status, ['Content-Type' => 'application/json'], json_encode($data, \JSON_THROW_ON_ERROR));
    }

    public function queue(HttpResponse ...$responses): self
    {
        array_push($this->queue, ...$responses);

        return $this;
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->requests[] = $request;

        return array_shift($this->queue) ?? self::json();
    }

    public function last(): HttpRequest
    {
        return $this->requests[array_key_last($this->requests)] ?? throw new LogicException('Aucune requete envoyee.');
    }

    /**
     * @return array<array-key, mixed>
     */
    public function lastJson(): array
    {
        return json_decode((string) $this->last()->body, true, 512, \JSON_THROW_ON_ERROR);
    }
}
