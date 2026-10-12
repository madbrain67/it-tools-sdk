export class ItToolsError extends Error {
  constructor(message: string, options?: { cause?: unknown }) {
    super(message, options);
    this.name = new.target.name;
  }
}

/** La requete n'a pas abouti : reseau, TLS ou delai depasse. Aucune reponse HTTP recue. */
export class TransportError extends ItToolsError {}

/** L'API a repondu avec un statut HTTP d'erreur. */
export class ApiError extends ItToolsError {
  constructor(
    message: string,
    readonly status: number,
    readonly body: string = '',
  ) {
    super(message);
  }

  static fromResponse(status: number, body: string): ApiError {
    let message = `Erreur HTTP ${status}.`;
    try {
      const decoded: unknown = JSON.parse(body);
      if (typeof decoded === 'object' && decoded !== null) {
        const candidate = (decoded as { message?: unknown }).message;
        if (typeof candidate === 'string' && candidate !== '') {
          message = candidate;
        }
      }
    } catch {
      // corps non JSON : on conserve le message par defaut
    }

    if (status === 401 || status === 403) return new AuthenticationError(message, status, body);
    if (status === 404) return new NotFoundError(message, status, body);
    if (status === 400 || status === 422) return new ValidationError(message, status, body);
    if (status === 413) return new PayloadTooLargeError(message, status, body);
    if (status === 415) return new UnsupportedMediaTypeError(message, status, body);
    if (status === 429) return new QuotaExceededError(message, status, body);
    if (status >= 500) return new ServerError(message, status, body);
    return new ApiError(message, status, body);
  }
}

/** Cles API absentes, invalides ou revoquees (401, 403). */
export class AuthenticationError extends ApiError {}
/** Ressource introuvable (404). */
export class NotFoundError extends ApiError {}
/** Parametre invalide ou document non traitable (400, 422). */
export class ValidationError extends ApiError {}
/** Fichier trop volumineux (413). */
export class PayloadTooLargeError extends ApiError {}
/** Type de fichier non pris en charge (415). */
export class UnsupportedMediaTypeError extends ApiError {}
/** Quota mensuel atteint ou limite de debit depassee (429). */
export class QuotaExceededError extends ApiError {}
/** Erreur cote serveur IT-Tools (5xx). */
export class ServerError extends ApiError {}
