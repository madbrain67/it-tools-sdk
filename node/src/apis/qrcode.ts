import type { FileInput } from '../files.js';
import { clean, type HttpClient } from '../http.js';

/** QR codes (`/api/v1/tools/qrcode/*`). */
export class QrCodeApi {
  constructor(private readonly http: HttpClient) {}

  /** Logos predefinis disponibles. */
  logos(): Promise<unknown> {
    return this.http.json('GET', '/api/v1/tools/qrcode/logos');
  }

  /** Le QR code est renvoye en data URI base64. `honeypot`, `formElapsedMs` et `altchaPayload` ne servent qu'a la creation anonyme. */
  generate(
    content: string,
    options: {
      type?: 'url' | 'text' | 'email' | 'phone' | 'sms' | 'wifi' | 'vcard' | 'geo';
      format?: 'svg' | 'png';
      size?: number;
      honeypot?: string;
      formElapsedMs?: number;
      altchaPayload?: string;
    } = {},
  ): Promise<Record<string, unknown>> {
    return this.http.json('POST', '/api/v1/tools/qrcode/generate', { json: clean({ content, ...options }) });
  }

  /** Convertit une image en logo SVG utilisable dans un QR code. */
  iconToSvg(file: FileInput, options: { bgColor?: string; shape?: 'circle' | 'square'; transparent?: boolean } = {}): Promise<Record<string, unknown>> {
    return this.http.multipart('/api/v1/tools/qrcode/icon-to-svg', options, { file });
  }
}
