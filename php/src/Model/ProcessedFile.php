<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Model;

use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\Exception\ItToolsException;

/**
 * Resultat d'un traitement qui produit un fichier : l'API repond par un lien de
 * telechargement a usage unique, recupere ici avec les memes cles API.
 */
final class ProcessedFile
{
    /**
     * @param array<array-key, mixed> $data reponse JSON complete de l'API
     */
    public function __construct(
        private readonly Client $client,
        private readonly array $data,
    ) {
    }

    /**
     * @return array<array-key, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function hasDownload(): bool
    {
        return \is_string($this->data['download_url'] ?? null) && '' !== $this->data['download_url'];
    }

    public function downloadUrl(): string
    {
        if (!$this->hasDownload()) {
            throw new ItToolsException('La reponse de l\'API ne contient pas de lien de telechargement.');
        }

        return $this->data['download_url'];
    }

    /**
     * Telecharge le fichier. Le lien etant a usage unique, l'appel ne doit etre fait qu'une fois.
     */
    public function content(): string
    {
        return $this->client->download($this->downloadUrl());
    }

    /**
     * @return int nombre d'octets ecrits
     */
    public function save(string $path): int
    {
        $bytes = file_put_contents($path, $this->content(), \LOCK_EX);
        if (false === $bytes) {
            throw new ItToolsException(sprintf('Ecriture impossible : "%s".', $path));
        }

        return $bytes;
    }
}
