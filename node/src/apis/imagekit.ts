import { ItToolsError } from '../errors.js';
import type { FileInput } from '../files.js';
import type { HttpClient } from '../http.js';

export interface ImageKitStatus {
  status: 'pending' | 'processing' | 'done' | 'failed';
  download_url?: string | null;
  message?: string | null;
  [key: string]: unknown;
}

const BASE = '/api/v1/tools/image-kit';

/** Kits d'images (`/api/v1/tools/image-kit/*`) : traitement asynchrone, a interroger par `jobId`. */
export class ImageKitApi {
  constructor(private readonly http: HttpClient) {}

  /**
   * `presets` : desktop, mobile, avatar. `formats` : avif, webp (les deux par defaut).
   * `watermarkAngle` : -90 a 90. `watermarkRepeat` : 1 a 12. `watermarkOpacity` : 0.1 a 1.0.
   */
  create(
    image: FileInput,
    options: {
      presets: string | string[];
      formats?: string | string[];
      watermarkText?: string;
      watermarkColor?: string;
      watermarkAngle?: number;
      watermarkRepeat?: number;
      watermarkOpacity?: number;
    },
  ): Promise<{ jobId: number; [key: string]: unknown }> {
    const { presets, formats, ...rest } = options;
    return this.http.multipart(
      `${BASE}/create`,
      { ...rest, presets: Array.isArray(presets) ? presets.join(',') : presets, formats: Array.isArray(formats) ? formats.join(',') : formats },
      { image },
    );
  }

  status(jobId: number | string, options: { ownerToken?: string } = {}): Promise<ImageKitStatus> {
    return this.http.json('GET', `${BASE}/${encodeURIComponent(String(jobId))}/status`, { query: { ownerToken: options.ownerToken } });
  }

  /** Interroge le job jusqu'a son terme et renvoie le statut final (`done`). */
  async waitUntilDone(jobId: number | string, options: { ownerToken?: string; timeoutMs?: number; intervalMs?: number } = {}): Promise<ImageKitStatus> {
    const timeoutMs = options.timeoutMs ?? 120_000;
    const intervalMs = options.intervalMs ?? 2_000;
    const deadline = Date.now() + timeoutMs;

    do {
      const status = await this.status(jobId, { ownerToken: options.ownerToken });
      if (status.status === 'done') return status;
      if (status.status === 'failed') {
        throw new ItToolsError(typeof status.message === 'string' && status.message !== '' ? status.message : "Le traitement du kit d'images a echoue.");
      }
      await new Promise((resolve) => setTimeout(resolve, intervalMs));
    } while (Date.now() < deadline);

    throw new ItToolsError(`Kit d'images ${jobId} non termine apres ${timeoutMs} ms.`);
  }

  /** Archive ZIP du kit. */
  download(downloadUrl: string): Promise<Uint8Array> {
    return this.http.download(downloadUrl);
  }
}
