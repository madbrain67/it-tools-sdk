# Changelog

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) ; les versions suivent [SemVer](https://semver.org/lang/fr/).

## [1.0.0] - non publiee

Premiere version, alignee sur l'API IT-Tools 5.2.0.

### Ajoute

- Couverture complete de l'API externe `/api/v1/*` : PDF (14 operations), CERFA, Office vers PDF, PDF vers Word, conversion d'images, montants en lettres, QR codes, booklets, kits d'images et les 24 routes d'outils developpeur.
- PHP : cœur sans dependance (`ext-curl`), API objet et API procedurale (`it_tools_*`), transport interchangeable.
- Bundle Symfony : configuration YAML, client autowirable, transport Symfony HttpClient, commande `it-tools:ping`.
- Paquet Laravel : ServiceProvider, Facade `ItTools`, transport sur le client HTTP de Laravel.
- Node.js / navigateur : SDK TypeScript sur `fetch`, sans dependance.
- Protection des cles : redirections jamais suivies, telechargement restreint a l'hote configure.
