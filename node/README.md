# @plateformweb/it-tools

SDK JavaScript / TypeScript de l'API IT-Tools. Aucune dependance : repose sur `fetch`, `FormData` et `Blob` natifs (Node.js 18.17+, navigateurs recents). Modules ES uniquement.

```bash
npm install @plateformweb/it-tools
```

## Utilisation

```ts
import { ItTools } from '@plateformweb/it-tools';

const itTools = new ItTools({
  apiKey: process.env.IT_TOOLS_API_KEY!,
  apiSecret: process.env.IT_TOOLS_API_SECRET, // cle privee, telle qu'affichee
});

// Outils developpeur : la valeur calculee est sous `result`
const { result } = await itTools.dev.slugify('Ete a Paris !');          // 'ete-a-paris'
const uuids = await itTools.dev.uuid({ count: 3, version: 'v7' });
await itTools.dev.dateAdd('2024-01-31', 7, { unit: 'days' });

// PDF : chemin disque, Blob / File, octets, ou { data, filename }
const merged = await itTools.pdf.merge(['a.pdf', 'b.pdf']);
await merged.save('fusion.pdf');                                          // Node.js
const bytes = await (await itTools.pdf.compress('rapport.pdf', { level: 'high' })).content();

// CERFA
const { available } = await itTools.cerfa.catalog();
await (await itTools.cerfa.generate(available[0]!.cerfaId, { f1: 'Dupont' })).save('cerfa.pdf');

// Kit d'images (asynchrone)
const job = await itTools.imageKit.create('photo.jpg', { presets: ['desktop', 'mobile'], formats: ['webp'] });
const done = await itTools.imageKit.waitUntilDone(job.jobId);
const zip = await itTools.imageKit.download(done.download_url!);
```

Groupes : `dev`, `pdf`, `cerfa`, `qrCode`, `booklet`, `imageKit`, `tools`. Les traitements qui produisent un fichier renvoient un `ProcessedFile` (`hasDownload`, `downloadUrl`, `content()`, `save(chemin)`) ; le lien est a usage unique.

## Options

| Option | Defaut | Role |
|---|---|---|
| `apiKey` | requis | Cle publique `pk_...` |
| `apiSecret` | aucune | Cle privee `sk_...`. A omettre cote navigateur |
| `baseUrl` | `https://it-tools.plateformweb.com` | Racine de l'API |
| `timeoutMs` | `30000` | Delai maximal par requete |
| `fetch` | `fetch` global | Implementation personnalisee (tests, proxy) |

Un certificat auto-signe en developpement local se gere cote Node (`NODE_TLS_REJECT_UNAUTHORIZED=0`, jamais en production) ou via un `fetch` avec agent dedie.

## Navigateur

En mode navigateur, n'envoyez que la **cle publique** : elle doit etre restreinte aux domaines autorises dans votre espace compte. Les `ProcessedFile.save()` et les chemins de fichier sont reserves a Node.js ; dans un navigateur, utilisez `content()` et des `File` / `Blob`.

## Erreurs

Toutes etendent `ItToolsError`.

| Classe | Cas |
|---|---|
| `TransportError` | reseau, TLS, delai depasse |
| `AuthenticationError` | 401, 403 |
| `NotFoundError` | 404 |
| `ValidationError` | 400, 422 |
| `PayloadTooLargeError` | 413 |
| `UnsupportedMediaTypeError` | 415 |
| `QuotaExceededError` | 429 |
| `ServerError` | 5xx |

`ApiError` (parente des erreurs HTTP) expose `status` et `body`. Un statut 3xx est aussi une erreur : les redirections ne sont jamais suivies, et un `download_url` vers un autre hote que `baseUrl` est refuse avant tout envoi des cles.

```ts
import { QuotaExceededError, ValidationError } from '@plateformweb/it-tools';

try {
  await itTools.dev.jsonFormat('{bad');
} catch (error) {
  if (error instanceof ValidationError) console.error(error.message); // message de l'API
  else if (error instanceof QuotaExceededError) { /* reessayer plus tard */ }
  else throw error;
}
```

## Tests

```bash
npm install
npm test             # compile puis lance les tests unitaires (Node.js 20+)
npm run test:live    # API reelle (quota consomme) : IT_TOOLS_API_KEY / IT_TOOLS_API_SECRET / IT_TOOLS_BASE_URI
```
