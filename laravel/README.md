# plateformweb/it-tools-laravel

Integration Laravel 10 / 11 / 12 de l'API IT-Tools : ServiceProvider (decouverte automatique), Facade `ItTools` et transport sur le client HTTP de Laravel. `Http::fake()` et `Http::assertSent()` fonctionnent dans vos tests.

```bash
composer require plateformweb/it-tools-laravel
php artisan vendor:publish --tag=it-tools-config   # optionnel
```

## Configuration

```dotenv
IT_TOOLS_API_KEY=pk_...
IT_TOOLS_API_SECRET=sk_...
# IT_TOOLS_BASE_URI=https://it-tools.plateformweb.com
# IT_TOOLS_TIMEOUT=30
# IT_TOOLS_VERIFY_PEER=true   # false uniquement en developpement local
```

La cle privee s'envoie telle qu'affichee. Ne jamais la committer.

## Utilisation

```php
use Plateformweb\ItToolsLaravel\Facades\ItTools;
use Plateformweb\ItTools\Client;

$slug = ItTools::dev()->slugify('Ete a Paris !')['result'];

ItTools::pdf()->merge([storage_path('a.pdf'), storage_path('b.pdf')])
    ->save(storage_path('fusion.pdf'));

// ou par injection
public function __construct(private readonly Client $itTools) {}
```

L'API complete est decrite dans le [README du SDK PHP](../php/README.md).

## Tests de votre application

```php
use Illuminate\Support\Facades\Http;

Http::fake(['it-tools.plateformweb.com/*' => Http::response(['type' => 'success', 'result' => 'ete-a-paris'])]);

expect(ItTools::dev()->slugify('Ete a Paris')['result'])->toBe('ete-a-paris');
```

## Tests du paquet

```bash
composer install
vendor/bin/phpunit
```
