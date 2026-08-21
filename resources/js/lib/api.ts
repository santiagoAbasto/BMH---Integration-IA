/**
 * Cliente HTTP del asesor.
 *
 * El CSRF sale del meta tag que pone Blade. Las rutas son las mismas del grupo
 * `web`, así que la sesión viaja en la cookie: no hay tokens en el frontend.
 */

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

export class ApiError extends Error {
    constructor(
        message: string,
        public readonly status: number,
        public readonly code?: string,
    ) {
        super(message);
        this.name = 'ApiError';
    }
}

async function parse<T>(response: Response): Promise<T> {
    if (response.status === 204) {
        return undefined as T;
    }

    const text = await response.text();
    let body: unknown = null;

    try {
        body = text ? JSON.parse(text) : null;
    } catch {
        // Una respuesta HTML donde esperábamos JSON casi siempre es la sesión
        // vencida y una redirección al login.
        throw new ApiError('Tu sesión expiró. Volvé a iniciar sesión.', response.status, 'session_expired');
    }

    if (!response.ok) {
        const payload = body as { message?: string; code?: string } | null;
        throw new ApiError(
            payload?.message ?? 'No pudimos completar la operación.',
            response.status,
            payload?.code,
        );
    }

    return body as T;
}

export async function apiGet<T>(url: string): Promise<T> {
    const response = await fetch(url, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
    });

    return parse<T>(response);
}

export async function apiPost<T>(url: string, body?: unknown): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    return parse<T>(response);
}

export async function apiUpload<T>(url: string, formData: FormData): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: formData,
    });

    return parse<T>(response);
}

/**
 * POST con respuesta Server-Sent Events.
 *
 * `EventSource` no sirve acá porque no soporta POST ni headers, así que se lee
 * el stream a mano.
 */
export async function apiStream(
    url: string,
    body: unknown,
    handlers: {
        onEvent: (event: string, data: unknown) => void;
        signal?: AbortSignal;
    },
): Promise<void> {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'text/event-stream',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
        signal: handlers.signal,
    });

    if (!response.ok || response.body === null) {
        throw new ApiError('No pudimos conectarnos con el asesor.', response.status);
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';

    for (;;) {
        const { done, value } = await reader.read();

        if (done) {
            break;
        }

        buffer += decoder.decode(value, { stream: true });

        // Los eventos SSE se separan por línea en blanco.
        const frames = buffer.split('\n\n');
        buffer = frames.pop() ?? '';

        for (const frame of frames) {
            let eventName = 'message';
            const dataLines: string[] = [];

            for (const line of frame.split('\n')) {
                if (line.startsWith('event:')) {
                    eventName = line.slice(6).trim();
                } else if (line.startsWith('data:')) {
                    dataLines.push(line.slice(5).trim());
                }
            }

            if (dataLines.length === 0) {
                continue;
            }

            try {
                handlers.onEvent(eventName, JSON.parse(dataLines.join('\n')));
            } catch {
                // Un frame corrupto no puede tumbar el stream entero.
            }
        }
    }
}

/** Formato de moneda argentino: $ 39.321,50 */
export function formatArs(value: number): string {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    }).format(value);
}
