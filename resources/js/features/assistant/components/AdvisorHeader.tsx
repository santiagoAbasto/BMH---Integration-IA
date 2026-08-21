import clsx from 'clsx';
import { PanelRight, UserRound } from 'lucide-react';
import type { AdvisorStatus } from '../types';

interface Props {
    status: AdvisorStatus;
    providerMode: 'MOCK' | 'LIVE';
    debug: boolean;
    onRequestHuman: () => void;
    onToggleContext: () => void;
}

/**
 * Header del chat.
 *
 * El subestado sólo aparece cuando hay una operación real en curso. Nada de
 * "escribiendo…" decorativo: si dice "Analizando imagen", hay una imagen
 * analizándose.
 */
export function AdvisorHeader({ status, providerMode, debug, onRequestHuman, onToggleContext }: Props) {
    const subline = status.kind === 'idle' ? 'Listo para ayudarte' : status.label;
    const busy = status.kind !== 'idle' && status.kind !== 'error';

    return (
        <header className="flex items-center gap-3 border-b border-edge-subtle bg-surface-raised px-4 py-3">
            <div
                aria-hidden
                className="flex h-9 w-9 shrink-0 items-center justify-center rounded-card bg-brand-500 text-[0.8125rem] font-bold tracking-tight text-white"
            >
                BMH
            </div>

            <div className="min-w-0 flex-1">
                <h1 className="truncate text-subtitle text-ink-primary">Asesor Técnico</h1>
                <p
                    aria-live="polite"
                    className={clsx(
                        'flex items-center gap-1.5 truncate text-caption transition-colors duration-150 ease-bmh',
                        status.kind === 'error' ? 'text-state-danger' : 'text-ink-tertiary',
                    )}
                >
                    {busy && (
                        <span aria-hidden className="flex gap-0.5">
                            {[0, 1, 2].map((dot) => (
                                <span
                                    key={dot}
                                    className="h-1 w-1 animate-pulse rounded-full bg-brand-500"
                                    style={{ animationDelay: `${dot * 150}ms` }}
                                />
                            ))}
                        </span>
                    )}
                    {subline}
                </p>
            </div>

            {debug && (
                <span
                    className="hidden rounded-full bg-surface-sunken px-2 py-0.5 text-micro uppercase text-ink-tertiary ring-1 ring-edge-subtle sm:inline-flex"
                    title="Proveedor de IA activo"
                >
                    AI: {providerMode}
                </span>
            )}

            <button
                type="button"
                onClick={onRequestHuman}
                className="inline-flex min-h-[2.25rem] items-center gap-1.5 rounded-card border border-edge px-2.5 text-caption text-ink-secondary transition-colors duration-150 ease-bmh hover:border-brand-400 hover:text-brand-700"
            >
                <UserRound aria-hidden className="h-4 w-4" strokeWidth={1.75} />
                <span className="hidden sm:inline">Hablar con un asesor</span>
            </button>

            <button
                type="button"
                onClick={onToggleContext}
                aria-label="Mostrar u ocultar el contexto técnico"
                className="inline-flex h-9 w-9 items-center justify-center rounded-card text-ink-secondary hover:bg-surface-sunken xl:hidden"
            >
                <PanelRight aria-hidden className="h-5 w-5" strokeWidth={1.75} />
            </button>
        </header>
    );
}
