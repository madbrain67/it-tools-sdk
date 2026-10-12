import { ItToolsError } from './errors.js';

type BinaryData = Blob | Uint8Array | ArrayBuffer;

/**
 * Fichier a envoyer : Blob / File, octets en memoire, objet nomme, ou chemin disque (Node uniquement).
 */
export type FileInput = BinaryData | string | { data: BinaryData; filename: string; type?: string };

export interface ResolvedFile {
  blob: Blob;
  filename: string;
}

const MIME_BY_EXTENSION: Record<string, string> = {
  pdf: 'application/pdf',
  png: 'image/png',
  jpg: 'image/jpeg',
  jpeg: 'image/jpeg',
  gif: 'image/gif',
  webp: 'image/webp',
  avif: 'image/avif',
  bmp: 'image/bmp',
  svg: 'image/svg+xml',
  doc: 'application/msword',
  docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  xls: 'application/vnd.ms-excel',
  xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  ppt: 'application/vnd.ms-powerpoint',
  pptx: 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
  odt: 'application/vnd.oasis.opendocument.text',
  txt: 'text/plain',
};

function mimeFor(filename: string): string {
  const extension = filename.includes('.') ? filename.split('.').pop()!.toLowerCase() : '';
  return MIME_BY_EXTENSION[extension] ?? 'application/octet-stream';
}

function toBlob(data: BinaryData, type: string): Blob {
  if (data instanceof Blob) {
    return data.type === '' && type !== '' ? new Blob([data], { type }) : data;
  }
  return new Blob([data as BlobPart], { type });
}

export async function resolveFile(input: FileInput, defaultName = 'file'): Promise<ResolvedFile> {
  if (typeof input === 'string') {
    let fs: typeof import('node:fs/promises');
    let path: typeof import('node:path');
    try {
      [fs, path] = await Promise.all([import('node:fs/promises'), import('node:path')]);
    } catch (cause) {
      throw new ItToolsError('Les chemins de fichier ne sont pris en charge que sous Node.js : fournissez un Blob ou des octets.', { cause });
    }

    const filename = path.basename(input);
    let bytes: Uint8Array;
    try {
      bytes = await fs.readFile(input);
    } catch (cause) {
      throw new ItToolsError(`Fichier introuvable ou illisible : "${input}".`, { cause });
    }
    return { blob: toBlob(bytes, mimeFor(filename)), filename };
  }

  if (input instanceof Blob) {
    const named = (input as Blob & { name?: unknown }).name;
    const filename = typeof named === 'string' && named !== '' ? named : defaultName;
    return { blob: toBlob(input, input.type || mimeFor(filename)), filename };
  }

  if (input instanceof Uint8Array || input instanceof ArrayBuffer) {
    return { blob: toBlob(input, mimeFor(defaultName)), filename: defaultName };
  }

  if (typeof input === 'object' && input !== null && typeof input.filename === 'string' && input.filename !== '') {
    return { blob: toBlob(input.data, input.type ?? mimeFor(input.filename)), filename: input.filename };
  }

  throw new ItToolsError('Fichier invalide : fournissez un chemin, un Blob, des octets ou { data, filename }.');
}
