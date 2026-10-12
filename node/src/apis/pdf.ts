import type { FileInput } from '../files.js';
import type { HttpClient, MultipartValue } from '../http.js';
import { ProcessedFile } from '../processed-file.js';

const BASE = '/api/v1/pdf';

/**
 * Outils PDF (`/api/v1/pdf/*`). Hors `info()`, chaque methode renvoie un `ProcessedFile` a telecharger.
 */
export class PdfApi {
  constructor(private readonly http: HttpClient) {}

  private async run(operation: string, pdf: FileInput, fields: Record<string, MultipartValue> = {}, extraFiles: Record<string, FileInput> = {}): Promise<ProcessedFile> {
    const data = await this.http.multipart(`${BASE}/${operation}`, fields, { pdf, ...extraFiles });
    return new ProcessedFile(this.http, data);
  }

  async merge(pdfs: FileInput[]): Promise<ProcessedFile> {
    return new ProcessedFile(this.http, await this.http.multipart(`${BASE}/merge`, {}, { pdfs }));
  }

  /** `ranges` : ex. `1-3,5` (mode `ranges`). */
  split(pdf: FileInput, options: { mode?: 'all' | 'ranges'; ranges?: string } = {}) {
    return this.run('split', pdf, options);
  }

  /** `pages` : ex. `2,4-6`. */
  deletePages(pdf: FileInput, pages: string) {
    return this.run('delete-pages', pdf, { pages });
  }

  repair(pdf: FileInput) {
    return this.run('repair', pdf);
  }

  compress(pdf: FileInput, options: { level?: 'low' | 'medium' | 'high' } = {}) {
    return this.run('compress', pdf, options);
  }

  toImages(pdf: FileInput, options: { dpi?: 72 | 96 | 150 | 300; format?: 'png' | 'jpg' } = {}) {
    return this.run('to-images', pdf, options);
  }

  rotate(pdf: FileInput, options: { rotation?: 90 | 180 | 270; pageTarget?: 'all' | 'odd' | 'even' | 'specific'; pages?: string } = {}) {
    return this.run('rotate', pdf, options);
  }

  protect(pdf: FileInput, options: { userPassword: string; ownerPassword?: string; allowPrint?: boolean; allowCopy?: boolean }) {
    return this.run('protect', pdf, options);
  }

  unlock(pdf: FileInput, options: { password?: string } = {}) {
    return this.run('unlock', pdf, options);
  }

  edit(pdf: FileInput, options: { action?: 'flatten' | 'extract'; pages?: string } = {}) {
    return this.run('edit', pdf, options);
  }

  addWatermark(pdf: FileInput, options: { type?: 'text' | 'image'; text?: string; fontSize?: number; opacity?: number; watermarkImage?: FileInput } = {}) {
    const { watermarkImage, ...fields } = options;
    return this.run('add-watermark', pdf, fields, watermarkImage === undefined ? {} : { watermarkImage });
  }

  addPageNumbers(
    pdf: FileInput,
    options: { position?: 'bottom-center' | 'bottom-left' | 'bottom-right' | 'top-center' | 'top-left' | 'top-right'; startFrom?: number; fontSize?: number } = {},
  ) {
    return this.run('add-page-numbers', pdf, options);
  }

  sign(pdf: FileInput, options: { signatureType?: 'draw' | 'upload' | 'text'; signatureImage?: FileInput; signatureText?: string; page?: number } = {}) {
    const { signatureImage, ...fields } = options;
    return this.run('sign', pdf, fields, signatureImage === undefined ? {} : { signatureImage });
  }

  /** Metadonnees du PDF, sous la cle `info`. */
  info(pdf: FileInput): Promise<{ info: Record<string, unknown> } & Record<string, unknown>> {
    return this.http.multipart(`${BASE}/info`, {}, { pdf });
  }
}
