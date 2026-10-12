<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Transport;

use CURLFile;
use CURLStringFile;
use Plateformweb\ItTools\Exception\TransportException;
use Plateformweb\ItTools\File;

final class CurlTransport implements TransportInterface
{
    public function send(HttpRequest $request): HttpResponse
    {
        $handle = curl_init();
        if (false === $handle) {
            throw new TransportException('Initialisation de cURL impossible.');
        }

        $responseHeaders = [];
        $headers = $request->headers;

        $options = [
            \CURLOPT_URL => $request->url,
            \CURLOPT_CUSTOMREQUEST => $request->method,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_FOLLOWLOCATION => false,
            \CURLOPT_PROTOCOLS => \CURLPROTO_HTTP | \CURLPROTO_HTTPS,
            \CURLOPT_TIMEOUT_MS => (int) round($request->timeout * 1000),
            \CURLOPT_CONNECTTIMEOUT_MS => (int) round(min($request->timeout, 10.0) * 1000),
            \CURLOPT_SSL_VERIFYPEER => $request->verifyPeer,
            \CURLOPT_SSL_VERIFYHOST => $request->verifyPeer ? 2 : 0,
            \CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$responseHeaders): int {
                $parts = explode(':', $line, 2);
                if (2 === \count($parts)) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return \strlen($line);
            },
        ];

        if ($request->isMultipart()) {
            $options[\CURLOPT_POSTFIELDS] = $this->multipartFields($request);
        } elseif (null !== $request->body) {
            $options[\CURLOPT_POSTFIELDS] = $request->body;
        }

        $options[\CURLOPT_HTTPHEADER] = array_map(
            static fn (string $name, string $value): string => $name . ': ' . $value,
            array_keys($headers),
            array_values($headers),
        );

        curl_setopt_array($handle, $options);
        $body = curl_exec($handle);

        if (false === $body) {
            $message = curl_error($handle);
            $code = curl_errno($handle);
            curl_close($handle);

            throw new TransportException(sprintf('Requete %s %s impossible : %s', $request->method, $request->url, $message), $code);
        }

        $status = (int) curl_getinfo($handle, \CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return new HttpResponse($status, $responseHeaders, (string) $body);
    }

    /**
     * @return array<string, scalar|CURLFile|CURLStringFile>
     */
    private function multipartFields(HttpRequest $request): array
    {
        $flat = [];

        foreach ($request->fields as $name => $value) {
            if (\is_array($value)) {
                foreach (array_values($value) as $i => $item) {
                    $flat[sprintf('%s[%d]', $name, $i)] = $item;
                }

                continue;
            }

            $flat[$name] = $value;
        }

        foreach ($request->files as $name => $file) {
            if (\is_array($file)) {
                foreach (array_values($file) as $i => $item) {
                    $flat[sprintf('%s[%d]', $name, $i)] = $this->toCurlFile($item);
                }

                continue;
            }

            $flat[$name] = $this->toCurlFile($file);
        }

        return $flat;
    }

    private function toCurlFile(File $file): CURLFile|CURLStringFile
    {
        $path = $file->path();
        if (null !== $path) {
            return new CURLFile($path, $file->mimeType, $file->filename);
        }

        return new CURLStringFile($file->contents(), $file->filename, $file->mimeType);
    }
}
