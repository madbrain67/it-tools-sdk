import type { FileInput } from '../files.js';
import type { HttpClient } from '../http.js';
import { ProcessedFile } from '../processed-file.js';

export interface Currency {
  code: string;
  symbol: string;
  singular: string;
  plural: string;
  locale: string;
  fractionDigits: number;
  label: string;
}

const BASE = '/api/v1/tools';

/** Outils divers (`/api/v1/tools/*`) : montants en lettres, images, documents Office. */
export class ToolsApi {
  constructor(private readonly http: HttpClient) {}

  currencies(): Promise<Currency[]> {
    return this.http.json('GET', `${BASE}/number-to-words/currencies`);
  }

  /** Le texte est sous la cle `result`. `currency` : code de devise, voir `currencies()`. */
  numberToWords(amount: number | string, options: { currency?: string } = {}): Promise<{ type: string; result: string; [key: string]: unknown }> {
    return this.http.json('POST', `${BASE}/number-to-words/convert`, { json: { amount: String(amount), ...(options.currency === undefined ? {} : { currency: options.currency }) } });
  }

  imageFormats(): Promise<string[]> {
    return this.http.json('GET', `${BASE}/image/formats`);
  }

  /** Image convertie (binaire). `format` : webp (defaut), avif, jpg, png, gif, bmp. `quality` defaut 85. */
  convertImage(file: FileInput, options: { format?: 'webp' | 'avif' | 'jpg' | 'png' | 'gif' | 'bmp'; quality?: number; maxWidth?: number } = {}): Promise<Uint8Array> {
    return this.http.multipartBinary(`${BASE}/image/convert`, options, { file });
  }

  async officeToPdf(file: FileInput): Promise<ProcessedFile> {
    return new ProcessedFile(this.http, await this.http.multipart(`${BASE}/office-to-pdf`, {}, { file }));
  }

  async pdfToWord(file: FileInput): Promise<ProcessedFile> {
    return new ProcessedFile(this.http, await this.http.multipart(`${BASE}/pdf-to-word`, {}, { file }));
  }
}
