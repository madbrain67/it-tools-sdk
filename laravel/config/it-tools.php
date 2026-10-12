<?php

declare(strict_types=1);

return [
    // Cle publique (pk_...).
    'api_key' => env('IT_TOOLS_API_KEY'),

    // Cle privee (sk_...), a renseigner telle qu'affichee. Laisser vide en mode navigateur.
    'api_secret' => env('IT_TOOLS_API_SECRET'),

    // Racine de l'API.
    'base_uri' => env('IT_TOOLS_BASE_URI', 'https://it-tools.plateformweb.com'),

    // Delai maximal d'une requete, en secondes.
    'timeout' => (float) env('IT_TOOLS_TIMEOUT', 30),

    // Verification du certificat TLS : a desactiver uniquement en developpement local.
    'verify_peer' => (bool) env('IT_TOOLS_VERIFY_PEER', true),
];
