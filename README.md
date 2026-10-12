# IT-Tools SDK

Clients officiels de l'API IT-Tools : outils PDF, formulaires CERFA, conversion de documents, QR codes, mini-sites booklet, kits d'images et outils developpeur (JSON, YAML, CSV, JWT, hash, UUID, dates, couleurs...).

Un meme depot, un paquet par environnement. Chaque paquet est autonome et se publie sur son registre.

| Dossier | Paquet | Pour qui |
|---|---|---|
| [php/](php/) | `plateformweb/it-tools-php` (Packagist) | Tout projet PHP 8.1+, en objet ou en **procedural** (aucune dependance hors `ext-curl`) |
| [symfony/](symfony/) | `plateformweb/it-tools-symfony-bundle` (Packagist) | Symfony 6.4 / 7 : client autowirable, configuration YAML, commande `it-tools:ping` |
| [laravel/](laravel/) | `plateformweb/it-tools-laravel` (Packagist) | Laravel 10 / 11 / 12 : ServiceProvider, Facade `ItTools`, compatible `Http::fake()` |
| [node/](node/) | `@plateformweb/it-tools` (npm) | Node.js 18+ et navigateurs : TypeScript, `fetch` natif, aucune dependance |
| [openapi/](openapi/) | `external.json` | Specification OpenAPI de l'API externe, pour generer un client dans un autre langage (Python, Go, Java...) ou importer l'API dans Zapier, Make, n8n |

## Authentification

Les cles se creent dans l'espace compte IT-Tools.

- **Mode serveur** : cle publique (`pk_...`) **et** cle privee (`sk_...`). La cle privee s'envoie telle qu'affichee. Elle ne doit jamais atteindre un navigateur.
- **Mode navigateur** : cle publique seule, restreinte aux domaines autorises sur la cle (JavaScript uniquement).

Chaque traitement est decompte du quota mensuel du forfait. Les telechargements par `download_url` ne le sont pas.

## Demarrage rapide

```php
// PHP procedural
it_tools_configure('pk_...', 'sk_...');
echo it_tools_slugify('Ete a Paris !')['result'];          // ete-a-paris
it_tools_pdf_merge(['a.pdf', 'b.pdf'])->save('fusion.pdf');
```

```php
// PHP objet / Symfony / Laravel (ItTools::pdf()->merge(...))
$client = Plateformweb\ItTools\Client::create('pk_...', 'sk_...');
$client->cerfa()->generate('10103-14', ['f1' => 'Dupont'])->save('cerfa.pdf');
```

```ts
// Node.js / navigateur
import { ItTools } from '@plateformweb/it-tools';

const itTools = new ItTools({ apiKey: 'pk_...', apiSecret: 'sk_...' });
const { result } = await itTools.dev.uuid({ count: 3, version: 'v7' });
await (await itTools.pdf.compress('rapport.pdf', { level: 'high' })).save('rapport-light.pdf');
```

## Comportement commun

- **Traitements qui produisent un fichier** : l'API repond par un lien `download_url` a usage unique ; le SDK le telecharge pour vous (`->save()`, `->content()`) avec les memes cles.
- **Erreurs typees** : 401/403 authentification, 404 introuvable, 400/422 validation, 413 fichier trop gros, 415 type non pris en charge, 429 quota ou debit, 5xx serveur, plus une erreur de transport (reseau, TLS, delai).
- **Cles protegees** : les redirections ne sont jamais suivies, et un lien de telechargement vers un autre hote que celui configure est refuse avant tout envoi. Un `download_url` malveillant ne peut donc pas exfiltrer les cles API.
- **Aucun secret journalise** : les SDK ne loggent rien.

## Developpement

```bash
# PHP (cœur)
cd php && composer install && composer test

# Symfony / Laravel (utilisent le cœur local via un depot "path")
cd symfony && composer install && vendor/bin/phpunit
cd laravel && composer install && vendor/bin/phpunit

# Node
cd node && npm install && npm test
```

Les tests `live` appellent l'API reelle et consomment du quota. Ils sont exclus par defaut et se lancent avec `IT_TOOLS_API_KEY`, `IT_TOOLS_API_SECRET` et, hors production, `IT_TOOLS_BASE_URI` :

```bash
cd php && composer test:live
cd node && npm run test:live      # NODE_TLS_REJECT_UNAUTHORIZED=0 pour un certificat auto-signe local
```

## Publication

Chaque dossier est publiable independamment (son `composer.json` ou `package.json` est a sa racine). Pour Packagist, le depot monorepo est decoupe en depots en lecture seule (un par paquet PHP) ; voir [CHANGELOG.md](CHANGELOG.md) pour la version courante. Les versions des quatre paquets suivent la version de l'API (semver).

## Licence

MIT.
