import { BookletApi } from './apis/booklet.js';
import { CerfaApi } from './apis/cerfa.js';
import { DevToolsApi } from './apis/dev.js';
import { ImageKitApi } from './apis/imagekit.js';
import { PdfApi } from './apis/pdf.js';
import { QrCodeApi } from './apis/qrcode.js';
import { ToolsApi } from './apis/tools.js';
import { HttpClient, type ItToolsOptions } from './http.js';

/**
 * Client de l'API IT-Tools.
 *
 * ```ts
 * const itTools = new ItTools({ apiKey: 'pk_...', apiSecret: 'sk_...' });
 * const { result } = await itTools.dev.slugify('Ete a Paris');
 * await (await itTools.pdf.merge(['a.pdf', 'b.pdf'])).save('merged.pdf');
 * ```
 */
export class ItTools {
  readonly dev: DevToolsApi;
  readonly pdf: PdfApi;
  readonly cerfa: CerfaApi;
  readonly qrCode: QrCodeApi;
  readonly booklet: BookletApi;
  readonly imageKit: ImageKitApi;
  readonly tools: ToolsApi;
  readonly baseUrl: string;

  constructor(options: ItToolsOptions) {
    const http = new HttpClient(options);
    this.baseUrl = http.baseUrl;
    this.dev = new DevToolsApi(http);
    this.pdf = new PdfApi(http);
    this.cerfa = new CerfaApi(http);
    this.qrCode = new QrCodeApi(http);
    this.booklet = new BookletApi(http);
    this.imageKit = new ImageKitApi(http);
    this.tools = new ToolsApi(http);
  }
}

export { DEFAULT_BASE_URL, VERSION, type ItToolsOptions } from './http.js';
export { ProcessedFile } from './processed-file.js';
export type { FileInput } from './files.js';
export type { DevResponse, Json } from './apis/dev.js';
export type { CerfaCatalogEntry, CerfaField } from './apis/cerfa.js';
export type { BookletOptions } from './apis/booklet.js';
export type { ImageKitStatus } from './apis/imagekit.js';
export type { Currency } from './apis/tools.js';
export {
  ApiError,
  AuthenticationError,
  ItToolsError,
  NotFoundError,
  PayloadTooLargeError,
  QuotaExceededError,
  ServerError,
  TransportError,
  UnsupportedMediaTypeError,
  ValidationError,
} from './errors.js';
