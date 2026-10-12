<?php

declare(strict_types=1);

namespace Plateformweb\ItToolsLaravel\Transport;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Plateformweb\ItTools\Exception\TransportException;
use Plateformweb\ItTools\File;
use Plateformweb\ItTools\Transport\HttpRequest;
use Plateformweb\ItTools\Transport\HttpResponse;
use Plateformweb\ItTools\Transport\TransportInterface;

/**
 * Transport du SDK base sur le client HTTP de Laravel : `Http::fake()` et `Http::assertSent()` s'appliquent.
 */
final class LaravelHttpTransport implements TransportInterface
{
    public function __construct(private readonly Factory $http)
    {
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $pending = $this->http
            ->withHeaders($request->headers)
            ->withoutRedirecting()
            ->withOptions([
                'timeout' => $request->timeout,
                'verify' => $request->verifyPeer,
                'http_errors' => false,
            ]);

        try {
            if ($request->isMultipart()) {
                $response = $this->sendMultipart($pending, $request);
            } elseif (null !== $request->body) {
                $response = $pending->withBody($request->body, $request->headers['Content-Type'] ?? 'application/json')
                    ->send($request->method, $request->url);
            } else {
                $response = $pending->send($request->method, $request->url);
            }
        } catch (ConnectionException $e) {
            throw new TransportException(sprintf('Requete %s %s impossible : %s', $request->method, $request->url, $e->getMessage()), 0, $e);
        }

        $headers = [];
        foreach ($response->headers() as $name => $values) {
            $headers[(string) $name] = implode(', ', (array) $values);
        }

        return new HttpResponse($response->status(), $headers, $response->body());
    }

    private function sendMultipart(PendingRequest $pending, HttpRequest $request): Response
    {
        $pending = $pending->asMultipart();
        $parts = [];

        foreach ($request->fields as $name => $value) {
            if (\is_array($value)) {
                foreach (array_values($value) as $i => $item) {
                    $parts[] = ['name' => sprintf('%s[%d]', $name, $i), 'contents' => (string) $item];
                }

                continue;
            }

            $parts[] = ['name' => $name, 'contents' => (string) $value];
        }

        foreach ($request->files as $name => $file) {
            foreach (\is_array($file) ? array_values($file) : [$file] as $i => $item) {
                $pending = $this->attach($pending, \is_array($file) ? sprintf('%s[%d]', $name, $i) : $name, $item);
            }
        }

        return $pending->send($request->method, $request->url, ['multipart' => $parts]);
    }

    private function attach(PendingRequest $pending, string $name, File $file): PendingRequest
    {
        return $pending->attach($name, $file->contents(), $file->filename, ['Content-Type' => $file->mimeType]);
    }
}
