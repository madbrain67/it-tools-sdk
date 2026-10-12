import { ItToolsError } from './errors.js';
import type { HttpClient } from './http.js';

/**
 * Resultat d'un traitement qui produit un fichier : l'API repond par un lien de telechargement
 * a usage unique, recupere ici avec les memes cles API.
 */
export class ProcessedFile {
  constructor(
    private readonly http: HttpClient,
    /** Reponse JSON complete de l'API. */
    readonly data: Record<string, unknown>,
  ) {}

  get hasDownload(): boolean {
    return typeof this.data.download_url === 'string' && this.data.download_url !== '';
  }

  get downloadUrl(): string {
    if (!this.hasDownload) {
      throw new ItToolsError("La reponse de l'API ne contient pas de lien de telechargement.");
    }
    return this.data.download_url as string;
  }

  /** Telecharge le fichier. Le lien etant a usage unique, l'appel ne doit etre fait qu'une fois. */
  content(): Promise<Uint8Array> {
    return this.http.download(this.downloadUrl);
  }

  /** Telecharge le fichier et l'ecrit sur le disque (Node.js). Renvoie le nombre d'octets ecrits. */
  async save(path: string): Promise<number> {
    const bytes = await this.content();
    const { writeFile } = await import('node:fs/promises');
    await writeFile(path, bytes);
    return bytes.byteLength;
  }
}
