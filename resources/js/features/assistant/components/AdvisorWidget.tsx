import { useCallback, useEffect, useState } from 'react';
import clsx from 'clsx';
import { Sparkles, X } from 'lucide-react';
import { apiGet } from '@/lib/api';
import { AdvisorPanel } from './AdvisorPanel';
import type { AssistantPageProps } from '../types';

interface Props {
    customer: AssistantPageProps['customer'];
    recentPurchases: AssistantPageProps['recentPurchases'];
    settings: AssistantPageProps['settings'];
}

const SEEN_KEY = 'bmh.advisor.seen';

/**
 * El asesor dentro de la Zona de Clientes.
 *
 * Es un lanzador flotante + un drawer. No hay página aparte ni link en el menú:
 * el cliente está mirando el catálogo y el asesor está ahí, en la misma
 * pantalla, sin perder lo que estaba haciendo.
 *
 * Además de la burbuja, cualquier elemento del sitio con el atributo
 * `data-bmh-advisor` abre el panel. Así se puede enganchar un ítem del header
 * desde Blade sin tocar el bundle.
 */
export function AdvisorWidget({ customer, recentPurchases, settings }: Props) {
    const [open, setOpen] = useState(false);
    const [hinted, setHinted] = useState(false);
    const [purchases, setPurchases] = useState(recentPurchases);

    // El "Probalo" aparece una sola vez por navegador, no en cada visita.
    useEffect(() => {
        try {
            if (window.localStorage.getItem(SEEN_KEY) === null) {
                const timer = window.setTimeout(() => setHinted(true), 1400);

                return () => window.clearTimeout(timer);
            }
        } catch {
            // localStorage bloqueado (modo privado): sin pista, sin drama.
        }

        return undefined;
    }, []);

    const openPanel = useCallback(() => {
        setOpen(true);
        setHinted(false);

        try {
            window.localStorage.setItem(SEEN_KEY, '1');
        } catch {
            /* noop */
        }

        // El historial se pide recién al abrir: no tiene sentido pagar la
        // consulta en cada página del catálogo por si acaso.
        if (purchases.length === 0) {
            void apiGet<{ recentPurchases: AssistantPageProps['recentPurchases'] }>('/api/assistant/status')
                .then((payload) => setPurchases(payload.recentPurchases))
                .catch(() => undefined);
        }
    }, [purchases.length]);

    // Enganche para el header: <a data-bmh-advisor>Asesor IA</a>
    useEffect(() => {
        const handler = (event: Event) => {
            const target = event.target as HTMLElement | null;

            if (target?.closest('[data-bmh-advisor]')) {
                event.preventDefault();
                openPanel();
            }
        };

        document.addEventListener('click', handler);

        return () => document.removeEventListener('click', handler);
    }, [openPanel]);

    // Escape cierra.
    useEffect(() => {
        if (! open) {
            return undefined;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
            }
        };

        document.addEventListener('keydown', onKey);

        return () => document.removeEventListener('keydown', onKey);
    }, [open]);

    return (
        <>
            {/*
                Lanzador. Ocupa la esquina inferior derecha, que antes tenía el
                botón de WhatsApp. Es la acción principal de la Zona de Clientes,
                así que va con etiqueta de dos líneas en vez de un ícono suelto:
                un círculo azul no le dice a nadie qué hace.
            */}
            {! open && (
                <div className="fixed bottom-5 right-5 z-[2147483000] flex flex-col items-end gap-2.5 sm:bottom-6 sm:right-6">
                    {hinted && (
                        <div className="bmh-panel-in flex max-w-[17rem] items-start gap-2 rounded-bubble border border-edge-subtle bg-surface-raised px-3.5 py-2.5 shadow-overlay">
                            <p className="text-caption text-ink-secondary">
                                ¿Buscás una pieza? Mandame una foto o un código y te la ubico.
                            </p>
                            <button
                                type="button"
                                onClick={() => setHinted(false)}
                                aria-label="Cerrar sugerencia"
                                className="-mr-1 -mt-0.5 shrink-0 text-ink-tertiary hover:text-ink-primary"
                            >
                                <X aria-hidden className="h-3.5 w-3.5" />
                            </button>
                        </div>
                    )}

                    <button
                        type="button"
                        onClick={openPanel}
                        aria-label="Abrir el Asesor Técnico BMH"
                        className={clsx(
                            'group flex items-center gap-3 rounded-full bg-brand-500 py-3.5 pl-4 pr-6 text-left text-white shadow-overlay transition-all duration-200 ease-bmh hover:bg-brand-600 hover:shadow-lifted active:scale-[0.98]',
                            ! hinted && 'bmh-launcher-pulse',
                        )}
                    >
                        <span
                            aria-hidden
                            className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-inset ring-white/25"
                        >
                            <Sparkles className="h-6 w-6" strokeWidth={2} />
                        </span>

                        <span className="flex flex-col leading-tight">
                            <span className="text-[1.0625rem] font-bold tracking-tight">Asesor IA</span>
                            <span className="text-[0.8125rem] font-medium text-white/85">
                                Encontrá tu pieza
                            </span>
                        </span>
                    </button>
                </div>
            )}

            {open && (
                <>
                    {/* Fondo: sólo en mobile, para no tapar el catálogo en desktop. */}
                    <div
                        role="presentation"
                        onClick={() => setOpen(false)}
                        className="fixed inset-0 z-[2147483000] bg-surface-inverse/40 lg:hidden"
                    />

                    <section
                        role="dialog"
                        aria-modal="false"
                        aria-label="Asesor Técnico BMH"
                        className={clsx(
                            'bmh-panel-in fixed z-[2147483001] overflow-hidden border border-edge-subtle bg-surface-base shadow-overlay',
                            // Mobile: hoja casi completa. Desktop: panel anclado.
                            'inset-x-0 bottom-0 top-[12vh] rounded-t-bubble',
                            'sm:inset-auto sm:bottom-6 sm:right-6 sm:top-auto sm:h-[min(46rem,88vh)] sm:w-[26rem] sm:rounded-bubble',
                            'lg:w-[46rem]',
                        )}
                    >
                        <AdvisorPanel
                            customer={customer}
                            recentPurchases={purchases}
                            settings={settings}
                            onClose={() => setOpen(false)}
                            variant="panel"
                        />
                    </section>
                </>
            )}
        </>
    );
}
