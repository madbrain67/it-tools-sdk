<?php

declare(strict_types=1);

namespace Plateformweb\ItTools;

use Plateformweb\ItTools\Exception\ItToolsException;

/**
 * Client partage par les fonctions procedurales `it_tools_*`.
 */
final class SharedClient
{
    private static ?Client $client = null;

    public static function set(Client $client): Client
    {
        return self::$client = $client;
    }

    public static function reset(): void
    {
        self::$client = null;
    }

    public static function get(): Client
    {
        return self::$client ??= self::fromEnvironment();
    }

    private static function fromEnvironment(): Client
    {
        $key = getenv('IT_TOOLS_API_KEY');
        if (false === $key || '' === $key) {
            throw new ItToolsException('Client non configure : appelez it_tools_configure() ou definissez IT_TOOLS_API_KEY.');
        }

        $secret = getenv('IT_TOOLS_API_SECRET');
        $base = getenv('IT_TOOLS_BASE_URI');
        $verify = getenv('IT_TOOLS_VERIFY_PEER');

        return Client::create(
            $key,
            false === $secret ? null : $secret,
            false === $base || '' === $base ? Config::DEFAULT_BASE_URI : $base,
            verifyPeer: false === $verify || '' === $verify || filter_var($verify, \FILTER_VALIDATE_BOOL),
        );
    }
}
