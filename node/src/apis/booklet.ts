import type { FileInput } from '../files.js';
import type { HttpClient } from '../http.js';

export interface BookletOptions {
  subtitle?: string;
  coverColor?: string;
  headerColor?: string;
  footerColor?: string;
  fontFamily?: 'inter' | 'poppins' | 'roboto' | 'montserrat' | 'playfair-display' | 'lora';
  coverImage?: FileInput;
  logoImage?: FileInput;
  /** Liste de sections, ou sa forme JSON. */
  sections?: string | Array<Record<string, unknown>>;
  /** Date `AAAA-MM-JJ`. */
  expiresAt?: string;
  qrForeground?: string;
  qrBackground?: string;
  qrMotifStyle?: 'square' | 'dot' | 'round';
  qrEyeStyle?: 'square' | 'circle';
  /** Cle d'un logo predefini, exclusive avec `qrLogo`. */
  qrLogoKey?: string;
  qrLogo?: FileInput;
}

const seg = encodeURIComponent;
const BASE = '/api/v1/tools/booklet';

/** Mini-sites booklet (`/api/v1/tools/booklet/*`). */
export class BookletApi {
  constructor(private readonly http: HttpClient) {}

  create(title: string, options: BookletOptions = {}): Promise<Record<string, unknown>> {
    const { coverImage, logoImage, qrLogo, sections, ...fields } = options;
    const files: Record<string, FileInput> = {};
    if (coverImage !== undefined) files.coverImage = coverImage;
    if (logoImage !== undefined) files.logoImage = logoImage;
    if (qrLogo !== undefined) files.qrLogo = qrLogo;

    return this.http.multipart(`${BASE}/create`, { title, ...fields, sections: Array.isArray(sections) ? JSON.stringify(sections) : sections }, files);
  }

  get(slug: string): Promise<Record<string, unknown>> {
    return this.http.json('GET', `${BASE}/${seg(slug)}`);
  }

  /** Image de couverture (binaire). */
  async cover(slug: string): Promise<Uint8Array> {
    return (await this.http.request('GET', `${BASE}/${seg(slug)}/cover`)).body;
  }

  /** Logo (binaire). */
  async logo(slug: string): Promise<Uint8Array> {
    return (await this.http.request('GET', `${BASE}/${seg(slug)}/logo`)).body;
  }

  /** QR code du booklet (SVG ou PNG selon `format`, `svg` par defaut). */
  async qrCode(slug: string, options: { format?: 'svg' | 'png' } = {}): Promise<Uint8Array> {
    return (await this.http.request('GET', `${BASE}/${seg(slug)}/qrcode`, { query: { format: options.format } })).body;
  }
}
