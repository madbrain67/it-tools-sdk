<?php

declare(strict_types=1);

namespace Plateformweb\ItTools;

use InvalidArgumentException;

final class Config
{
    public const DEFAULT_BASE_URI = 'https://it-tools.plateformweb.com';

    public readonly string $apiKey;
    public readonly ?string $apiSecret;
    public readonly string $baseUri;
    public readonly float $timeout;
    public readonly bool $verifyPeer;

    /**
     * @param string      $apiKey     cle publique (pk_...)
     * @param string|null $apiSecret  cle privee (sk_...), a envoyer telle qu'affichee ; omise en mode navigateur
     * @param string      $baseUri    racine de l'API, sans chemin
     * @param float       $timeout    delai maximal d'une requete, en secondes
     * @param bool        $verifyPeer verification du certificat TLS (a desactiver uniquement en developpement local)
     */
    public function __construct(
        string $apiKey,
        ?string $apiSecret = null,
        string $baseUri = self::DEFAULT_BASE_URI,
        float $timeout = 30.0,
        bool $verifyPeer = true,
    ) {
        $apiKey = trim($apiKey);
        if ('' === $apiKey) {
            throw new InvalidArgumentException('La cle API ne peut pas etre vide.');
        }

        $parts = parse_url(trim($baseUri));
        if (false === $parts || !\in_array($parts['scheme'] ?? '', ['http', 'https'], true) || '' === ($parts['host'] ?? '')) {
            throw new InvalidArgumentException(sprintf('URL de base invalide : "%s".', $baseUri));
        }

        if ($timeout <= 0) {
            throw new InvalidArgumentException('Le delai maximal doit etre strictement positif.');
        }

        $secret = null !== $apiSecret ? trim($apiSecret) : null;

        $this->apiKey = $apiKey;
        $this->apiSecret = '' === $secret ? null : $secret;
        $this->baseUri = sprintf('%s://%s%s', $parts['scheme'], $parts['host'], isset($parts['port']) ? ':' . $parts['port'] : '');
        $this->timeout = $timeout;
        $this->verifyPeer = $verifyPeer;
    }
}
