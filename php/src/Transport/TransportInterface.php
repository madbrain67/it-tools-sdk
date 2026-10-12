<?php

declare(strict_types=1);

namespace Plateformweb\ItTools\Transport;

use Plateformweb\ItTools\Exception\TransportException;

interface TransportInterface
{
    /**
     * Execute la requete sans lever d'exception sur un statut HTTP d'erreur.
     *
     * @throws TransportException si la requete n'a pas pu aboutir (reseau, TLS, delai depasse)
     */
    public function send(HttpRequest $request): HttpResponse;
}
