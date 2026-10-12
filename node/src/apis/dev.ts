import { clean, type HttpClient } from '../http.js';

/** Reponse des outils developpeur : la valeur calculee est sous `result`. */
export interface DevResponse<T = unknown> {
  type: string;
  result: T;
  [key: string]: unknown;
}

export type Json = string | Record<string, unknown> | unknown[];

const BASE = '/api/v1/tools/dev';

/** Outils developpeur (`/api/v1/tools/dev/*`). */
export class DevToolsApi {
  constructor(private readonly http: HttpClient) {}

  private post<T = unknown>(path: string, payload: Record<string, unknown>): Promise<DevResponse<T>> {
    return this.http.json<DevResponse<T>>('POST', BASE + path, { json: clean(payload) });
  }

  jsonFormat(json: Json, options: { indent?: 2 | 4 } = {}) {
    return this.post<string>('/json/format', { json, ...options });
  }

  jsonMinify(json: Json) {
    return this.post<string>('/json/minify', { json });
  }

  jsonYaml(input: string, options: { direction?: 'json-to-yaml' | 'yaml-to-json'; indent?: 2 | 4 } = {}) {
    return this.post<string>('/json-yaml/convert', { input, ...options });
  }

  jsonToCsv(json: Json, options: { separator?: ',' | ';' | '|' | 'tab'; headers?: boolean; neutralizeFormulas?: boolean } = {}) {
    return this.post<string>('/json-to-csv/convert', { json, ...options });
  }

  jwtDecode(token: string) {
    return this.post<{ header: Record<string, unknown>; payload: Record<string, unknown> } & Record<string, unknown>>('/jwt/decode', { token });
  }

  ipv4Subnet(cidr: string) {
    return this.post<Record<string, unknown>>('/ipv4-subnet/calculate', { cidr });
  }

  /** Fournir un seul de `octal`, `symbolic` ou `permissions`. */
  chmod(options: { octal?: string; symbolic?: string; permissions?: Record<'owner' | 'group' | 'other', { r: boolean; w: boolean; x: boolean }> }) {
    return this.post<Record<string, unknown>>('/chmod/calculate', options);
  }

  urlParse(url: string) {
    return this.post<Record<string, unknown>>('/url/parse', { url });
  }

  slugify(text: string, options: { separator?: '-' | '_' | '.'; lowercase?: boolean } = {}) {
    return this.post<string>('/slugify', { text, ...options });
  }

  convertCase(
    text: string,
    kind: 'lower' | 'upper' | 'title' | 'camel' | 'pascal' | 'snake' | 'kebab' | 'screaming' | 'dot' | 'constant' | 'sentence' | 'alternating',
  ) {
    return this.post<string>('/case/convert', { text, case: kind });
  }

  textStatistics(text: string) {
    return this.post<Record<string, number>>('/text-statistics', { text });
  }

  htmlEntities(text: string, options: { mode?: 'encode' | 'decode'; encodeAll?: boolean } = {}) {
    return this.post<string>('/html-entities/convert', { text, ...options });
  }

  base64(text: string, options: { mode?: 'encode' | 'decode'; urlSafe?: boolean } = {}) {
    return this.post<string>('/base64/convert', { text, ...options });
  }

  hash(text: string, options: { algorithms?: Array<'sha1' | 'sha256' | 'sha384' | 'sha512'> } = {}) {
    return this.post<Record<string, string>>('/hash', { text, ...options });
  }

  uuid(options: { count?: number; version?: 'v4' | 'v7'; uppercase?: boolean; withoutDashes?: boolean } = {}) {
    return this.post<string>('/uuid', options);
  }

  password(
    options: { count?: number; length?: number; lowercase?: boolean; uppercase?: boolean; numbers?: boolean; symbols?: boolean; excludeSimilar?: boolean } = {},
  ) {
    return this.post<string>('/password', options);
  }

  token(options: { count?: number; length?: number; format?: 'hex' | 'base64' | 'alphanumeric' | 'numeric' | 'upper' | 'lower' | 'custom'; custom?: string } = {}) {
    return this.post<string>('/token', options);
  }

  /** `value` : timestamp Unix ou date ISO 8601 (maintenant si omis). */
  timestamp(options: { value?: number | string; timezone?: string } = {}) {
    return this.post<Record<string, unknown>>('/timestamp/convert', options);
  }

  dateAdd(date: string, value: number, options: { unit?: 'days' | 'weeks' | 'months' | 'years' } = {}) {
    return this.post<Record<string, unknown>>('/date/add', { date, value, ...options });
  }

  dateDiff(from: string, to: string) {
    return this.post<Record<string, unknown>>('/date/diff', { from, to });
  }

  fakeDataFields() {
    return this.http.json<DevResponse<Record<string, unknown>>>('GET', BASE + '/fake-data/fields');
  }

  fakeData(fields: string[], options: { count?: number; locale?: 'fr' | 'en' | 'de' | 'es' | 'it' | 'nl' | 'pl' | 'pt_br' | 'ru' | 'ja' | 'zh_cn' | 'ar'; seed?: number } = {}) {
    return this.post<Array<Record<string, unknown>>>('/fake-data', { fields, ...options });
  }

  lorem(options: { mode?: 'paragraphs' | 'sentences' | 'words'; count?: number; minWords?: number; maxWords?: number; startWithLorem?: boolean; format?: 'plain' | 'html'; seed?: number } = {}) {
    return this.post<string>('/lorem', options);
  }

  /** `color` : `#RGB`, `#RRGGBB`, `rgb(r, g, b)` ou objet `{ r, g, b }`. */
  color(color: string | { r: number; g: number; b: number }) {
    return this.post<Record<string, unknown>>('/color/convert', { color });
  }
}
