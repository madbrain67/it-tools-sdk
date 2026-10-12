import { ApiError, ItToolsError, TransportError } from './errors.js';
import { resolveFile, type FileInput } from './files.js';

export const DEFAULT_BASE_URL = 'https://it-tools.plateformweb.com';
export const VERSION = '1.0.0';

export interface ItToolsOptions {
  /** Cle publique (pk_...). */
  apiKey: string;
  /** Cle privee (sk_...), a envoyer telle qu'affichee. A omettre cote navigateur. */
  apiSecret?: string;
  /** Racine de l'API. */
  baseUrl?: string;
  /** Delai maximal d'une requete, en millisecondes (defaut 30000). */
  timeoutMs?: number;
  /** Implementation de fetch a utiliser (tests, proxy, agent TLS personnalise). */
  fetch?: typeof fetch;
}

export interface HttpResult {
  status: number;
  headers: Headers;
  body: Uint8Array;
}

export type MultipartValue = string | number | boolean | undefined | null | ReadonlyArray<string | number>;
export type Query = Record<string, string | number | undefined | null>;

interface RequestInitLike {
  json?: unknown;
  form?: FormData;
  query?: Query;
}

export class HttpClient {
  readonly baseUrl: string;
  private readonly apiKey: string;
  private readonly apiSecret: string | undefined;
  private readonly timeoutMs: number;
  private readonly fetchImpl: typeof fetch;

  constructor(options: ItToolsOptions) {
    const apiKey = options.apiKey?.trim();
    if (!apiKey) {
      throw new ItToolsError('La cle API ne peut pas etre vide.');
    }

    let url: URL;
    try {
      url = new URL((options.baseUrl ?? DEFAULT_BASE_URL).trim());
    } catch (cause) {
      throw new ItToolsError(`URL de base invalide : "${options.baseUrl}".`, { cause });
    }
    if (url.protocol !== 'https:' && url.protocol !== 'http:') {
      throw new ItToolsError(`URL de base invalide : "${options.baseUrl}".`);
    }

    const timeoutMs = options.timeoutMs ?? 30_000;
    if (!(timeoutMs > 0)) {
      throw new ItToolsError('Le delai maximal doit etre strictement positif.');
    }

    this.apiKey = apiKey;
    this.apiSecret = options.apiSecret?.trim() ? options.apiSecret.trim() : undefined;
    this.baseUrl = url.origin;
    this.timeoutMs = timeoutMs;
    this.fetchImpl = options.fetch ?? ((input, init) => fetch(input, init));
  }

  async request(method: string, path: string, init: RequestInitLike = {}): Promise<HttpResult> {
    const headers = new Headers({ Accept: 'application/json, */*', 'X-Api-Key': this.apiKey });
    if (this.apiSecret !== undefined) {
      headers.set('X-Api-Secret', this.apiSecret);
    }

    let body: string | FormData | undefined;
    if (init.form !== undefined) {
      body = init.form;
    } else if (init.json !== undefined) {
      body = JSON.stringify(init.json);
      headers.set('Content-Type', 'application/json');
    }

    const url = new URL(this.baseUrl + path);
    for (const [name, value] of Object.entries(init.query ?? {})) {
      if (value !== undefined && value !== null) {
        url.searchParams.set(name, String(value));
      }
    }

    let response: Response;
    let bytes: Uint8Array;
    try {
      response = await this.fetchImpl(url, {
        method,
        headers,
        body,
        redirect: 'manual',
        signal: AbortSignal.timeout(this.timeoutMs),
      });
      bytes = new Uint8Array(await response.arrayBuffer());
    } catch (cause) {
      const reason = cause instanceof Error ? cause.message : String(cause);
      throw new TransportError(`Requete ${method} ${url.href} impossible : ${reason}`, { cause });
    }

    // Les redirections ne sont jamais suivies (les cles API ne doivent pas quitter l'hote configure) : un 3xx est une anomalie.
    if (response.status === 0 || response.status >= 300) {
      throw ApiError.fromResponse(response.status, new TextDecoder().decode(bytes));
    }

    return { status: response.status, headers: response.headers, body: bytes };
  }

  async json<T = Record<string, unknown>>(method: string, path: string, init: RequestInitLike = {}): Promise<T> {
    const result = await this.request(method, path, init);
    try {
      return JSON.parse(new TextDecoder().decode(result.body)) as T;
    } catch (cause) {
      throw new ItToolsError('Reponse JSON invalide.', { cause });
    }
  }

  async multipart<T = Record<string, unknown>>(path: string, fields: Record<string, MultipartValue>, files: Record<string, FileInput | FileInput[]>): Promise<T> {
    const result = await this.request('POST', path, { form: await buildForm(fields, files) });
    try {
      return JSON.parse(new TextDecoder().decode(result.body)) as T;
    } catch (cause) {
      throw new ItToolsError('Reponse JSON invalide.', { cause });
    }
  }

  async multipartBinary(path: string, fields: Record<string, MultipartValue>, files: Record<string, FileInput | FileInput[]>): Promise<Uint8Array> {
    return (await this.request('POST', path, { form: await buildForm(fields, files) })).body;
  }

  /**
   * Telecharge un lien `download_url`. Seul l'hote configure est joignable : les cles API ne sont
   * jamais envoyees a un autre serveur, quel que soit le lien recu.
   */
  async download(link: string): Promise<Uint8Array> {
    let target: URL;
    try {
      target = new URL(link, this.baseUrl);
    } catch (cause) {
      throw new ItToolsError('Lien de telechargement invalide.', { cause });
    }

    const base = new URL(this.baseUrl);
    const explicitPort = /^[a-z][a-z0-9+.-]*:\/\/[^/?#]*:\d+/i.test(link);
    if (target.hostname !== base.hostname || target.username !== '' || (explicitPort && target.port !== base.port)) {
      throw new ItToolsError("Lien de telechargement refuse : hote different de celui de l'API.");
    }

    return (await this.request('GET', target.pathname + target.search)).body;
  }
}

async function buildForm(fields: Record<string, MultipartValue>, files: Record<string, FileInput | FileInput[]>): Promise<FormData> {
  const form = new FormData();

  for (const [name, value] of Object.entries(fields)) {
    if (value === undefined || value === null) continue;
    if (Array.isArray(value)) {
      value.forEach((item, i) => form.append(`${name}[${i}]`, String(item)));
    } else {
      form.append(name, String(value));
    }
  }

  for (const [name, input] of Object.entries(files)) {
    if (Array.isArray(input)) {
      for (const [i, item] of input.entries()) {
        const file = await resolveFile(item, `${name}-${i}`);
        form.append(`${name}[${i}]`, file.blob, file.filename);
      }
    } else {
      const file = await resolveFile(input as FileInput, name);
      form.append(name, file.blob, file.filename);
    }
  }

  return form;
}

export function clean<T extends Record<string, unknown>>(values: T): Partial<T> {
  return Object.fromEntries(Object.entries(values).filter(([, v]) => v !== undefined && v !== null)) as Partial<T>;
}
