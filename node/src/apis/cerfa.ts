import type { HttpClient } from '../http.js';
import { ProcessedFile } from '../processed-file.js';

export interface CerfaCatalogEntry {
  cerfaId: string;
  intitule: string;
  categories: string[];
  updatedAt: string;
}

export interface CerfaField {
  name: string;
  type: string;
  altName: string;
  value?: unknown;
  options?: string[];
  [key: string]: unknown;
}

const seg = encodeURIComponent;

/** Formulaires CERFA remplissables (`/api/v1/cerfa`). */
export class CerfaApi {
  constructor(private readonly http: HttpClient) {}

  /** Catalogue des CERFA disponibles. */
  catalog(): Promise<{ available: CerfaCatalogEntry[]; [key: string]: unknown }> {
    return this.http.json('GET', '/api/v1/cerfa');
  }

  /** Champs AcroForm : `name` (cle a utiliser), `altName` (libelle), `type`, `options`. */
  fields(cerfaId: string): Promise<{ success: boolean; fields: CerfaField[]; [key: string]: unknown }> {
    return this.http.json('GET', `/api/v1/cerfa/${seg(cerfaId)}/fields`);
  }

  /** Gabarit pret a remplir : `data` indexe par nom de champ (`f1`, `f2`...). */
  template(cerfaId: string): Promise<{ success: boolean; cerfaId: string; data: Record<string, unknown> }> {
    return this.http.json('GET', `/api/v1/cerfa/${seg(cerfaId)}/template`);
  }

  /** Les cles inconnues de `data` sont ignorees par l'API. */
  async generate(cerfaId: string, data: Record<string, string | number | boolean | null>): Promise<ProcessedFile> {
    const response = await this.http.json('POST', `/api/v1/cerfa/${seg(cerfaId)}/generate`, { json: { data } });
    return new ProcessedFile(this.http, response);
  }
}
