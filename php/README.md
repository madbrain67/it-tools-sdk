# plateformweb/it-tools-php

SDK PHP de l'API IT-Tools. Aucune dependance hors `ext-curl` et `ext-json` ; PHP 8.1 ou plus. Utilisable en objet ou en procedural.

```bash
composer require plateformweb/it-tools-php
```

## Utilisation procedurale

```php
<?php
require 'vendor/autoload.php';

it_tools_configure('pk_...', 'sk_...');

$slug  = it_tools_slugify('Ete a Paris !')['result'];            // ete-a-paris
$uuids = it_tools_uuid(count: 3, version: 'v7')['results'];
$hash  = it_tools_hash('abc', ['sha256'])['result']['sha256'];

it_tools_pdf_merge(['a.pdf', 'b.pdf'])->save('fusion.pdf');
it_tools_cerfa_generate('10103-14', ['f1' => 'Dupont'])->save('cerfa.pdf');
```

Sans `it_tools_configure()`, la configuration est lue dans l'environnement : `IT_TOOLS_API_KEY`, `IT_TOOLS_API_SECRET`, `IT_TOOLS_BASE_URI`, `IT_TOOLS_VERIFY_PEER`.

Fonctions disponibles : `it_tools_json_format`, `_json_minify`, `_json_yaml`, `_json_to_csv`, `_jwt_decode`, `_ipv4_subnet`, `_chmod`, `_url_parse`, `_slugify`, `_case`, `_text_statistics`, `_html_entities`, `_base64`, `_hash`, `_uuid`, `_password`, `_token`, `_timestamp`, `_date_add`, `_date_diff`, `_fake_data_fields`, `_fake_data`, `_lorem`, `_color`, `_pdf_merge`, `_pdf_split`, `_pdf_delete_pages`, `_pdf_repair`, `_pdf_compress`, `_pdf_to_images`, `_pdf_rotate`, `_pdf_protect`, `_pdf_unlock`, `_pdf_edit`, `_pdf_watermark`, `_pdf_page_numbers`, `_pdf_sign`, `_pdf_info`, `_cerfa_catalog`, `_cerfa_fields`, `_cerfa_template`, `_cerfa_generate`, `_qrcode`, `_number_to_words`, `_image_convert`, `_office_to_pdf`, `_pdf_to_word` (toutes prefixees `it_tools_`). Les booklets et kits d'images passent par l'API objet.

## Utilisation objet

```php
use Plateformweb\ItTools\Client;
use Plateformweb\ItTools\File;

$client = Client::create(apiKey: 'pk_...', apiSecret: 'sk_...');

$client->dev()->base64('Bonjour')['result'];                      // Qm9uam91cg==
$client->dev()->dateAdd('2024-01-31', 7, 'days')['result']['date'];

$pdf = File::fromPath('rapport.pdf');                              // ou File::fromContents($octets, 'rapport.pdf', 'application/pdf')
$client->pdf()->compress($pdf, 'high')->save('rapport-light.pdf');
$client->pdf()->info($pdf);                                        // tableau, cle "info"

$qr = $client->qrCode()->generate('https://example.com', format: 'png', size: 300);
```

Groupes : `dev()`, `pdf()`, `cerfa()`, `qrCode()`, `booklet()`, `imageKit()`, `tools()`. Partout ou un fichier est attendu, un chemin (`string`) ou un `File` convient.

Les outils developpeur renvoient la reponse decodee ; la valeur calculee est sous la cle `result`. Les traitements qui produisent un fichier renvoient un `ProcessedFile` (`hasDownload()`, `downloadUrl()`, `content()`, `save($chemin)`) : le lien est a usage unique, ne le consommer qu'une fois.

### Kit d'images (asynchrone)

```php
$job   = $client->imageKit()->create('photo.jpg', ['desktop', 'mobile'], ['webp']);
$final = $client->imageKit()->waitUntilDone($job['jobId'], timeout: 120.0);
file_put_contents('kit.zip', $client->imageKit()->download($final['download_url']));
```

## Configuration

| Option | Defaut | Role |
|---|---|---|
| `apiKey` | requis | Cle publique `pk_...` |
| `apiSecret` | `null` | Cle privee `sk_...`, telle qu'affichee |
| `baseUri` | `https://it-tools.plateformweb.com` | Racine de l'API |
| `timeout` | `30.0` | Delai maximal par requete, en secondes |
| `verifyPeer` | `true` | Verification TLS ; a desactiver uniquement en developpement local |

## Erreurs

Toutes etendent `Plateformweb\ItTools\Exception\ItToolsException`.

| Exception | Cas |
|---|---|
| `TransportException` | reseau, TLS, delai depasse |
| `AuthenticationException` | 401, 403 |
| `NotFoundException` | 404 |
| `ValidationException` | 400, 422 |
| `PayloadTooLargeException` | 413 |
| `UnsupportedMediaTypeException` | 415 |
| `QuotaExceededException` | 429 (quota mensuel ou limite de debit) |
| `ServerException` | 5xx |

`ApiException` (parente de toutes les erreurs HTTP) expose `statusCode()` et `responseBody()`. Un statut 3xx est aussi une erreur : les redirections ne sont jamais suivies.

```php
try {
    $client->pdf()->merge(['a.pdf', 'b.pdf'])->save('fusion.pdf');
} catch (QuotaExceededException $e) {
    // reessayer plus tard
} catch (ValidationException $e) {
    echo $e->getMessage();   // message de l'API
}
```

## Transport personnalise

`Client` accepte tout `Plateformweb\ItTools\Transport\TransportInterface` (une methode `send(HttpRequest): HttpResponse`) : proxy, journalisation, mocks. Le transport par defaut est cURL ; les paquets Symfony et Laravel en fournissent un adosse a leur client HTTP.

## Securite

- La cle privee ne doit jamais etre exposee cote client.
- Le telechargement d'un `download_url` n'est possible que sur l'hote configure : un lien vers un autre serveur est refuse avant tout envoi des cles.
- Aucune redirection n'est suivie.

## Tests

```bash
composer install
composer test        # unitaires + cURL contre un serveur HTTP local
composer test:live   # API reelle (quota consomme) : definir IT_TOOLS_API_KEY / IT_TOOLS_API_SECRET / IT_TOOLS_BASE_URI
```
