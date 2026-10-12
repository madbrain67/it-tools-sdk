<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Api;

use Plateformweb\ItTools\Model\ProcessedFile;

/**
 * Formulaires CERFA remplissables (`/api/v1/cerfa`).
 */
final class CerfaApi extends AbstractApi
{
    /**
     * Catalogue des CERFA disponibles, sous la cle `available` (`cerfaId`, `intitule`, `categories`).
     *
     * @return array<array-key, mixed>
     */
    public function catalog(): array
    {
        return $this->client->requestJson('GET', '/api/v1/cerfa');
    }

    /**
     * Champs AcroForm : `name` (cle a utiliser), `altName` (libelle), `type`, `options`.
     *
     * @return array<array-key, mixed>
     */
    public function fields(string $cerfaId): array
    {
        return $this->client->requestJson('GET', sprintf('/api/v1/cerfa/%s/fields', self::segment($cerfaId)));
    }

    /**
     * Gabarit pret a remplir : le corps `data` indexe par nom de champ (`f1`, `f2`...).
     *
     * @return array<array-key, mixed>
     */
    public function template(string $cerfaId): array
    {
        return $this->client->requestJson('GET', sprintf('/api/v1/cerfa/%s/template', self::segment($cerfaId)));
    }

    /**
     * @param array<string, scalar|null> $data valeurs indexees par nom de champ ; les cles inconnues sont ignorees par l'API
     */
    public function generate(string $cerfaId, array $data): ProcessedFile
    {
        $response = $this->client->requestJson(
            'POST',
            sprintf('/api/v1/cerfa/%s/generate', self::segment($cerfaId)),
            ['data' => [] === $data ? new \stdClass() : $data],
        );

        return new ProcessedFile($this->client, $response);
    }
}
