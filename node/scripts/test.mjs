// Lance `node --test` sur les fichiers *.test.mjs d'un dossier : le glob de `node --test`
// n'existe qu'a partir de Node 21, ce lanceur fonctionne des Node 18.
import { spawnSync } from 'node:child_process';
import { readdirSync } from 'node:fs';
import { join, resolve } from 'node:path';

const directory = resolve(process.argv[2] ?? 'tests');
const files = readdirSync(directory)
  .filter((name) => name.endsWith('.test.mjs'))
  .sort()
  .map((name) => join(directory, name));

if (files.length === 0) {
  console.error(`Aucun fichier *.test.mjs dans ${directory}`);
  process.exit(1);
}

const result = spawnSync(process.execPath, ['--test', ...files], { stdio: 'inherit' });
process.exit(result.status ?? 1);
