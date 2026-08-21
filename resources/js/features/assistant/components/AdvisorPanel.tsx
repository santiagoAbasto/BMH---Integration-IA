import { useCallback, useEffect, useRef, useState } from 'react';
import clsx from 'clsx';
import { Camera, History, Search, Sparkles, Tag, X } from 'lucide-react';
import { Composer } from './Composer';
import { ContextPanel } from './ContextPanel';
import { MessageBubble } from './MessageBubble';
import { useConversation } from '../hooks/useConversation';
import type { AssistantPageProps } from '../types';

const MAX_IMAGES = 3;

interface Props {
    customer: AssistantPageProps['customer'];
    recentPurchases: AssistantPageProps['recentPurchases'];
    settings: AssistantPageProps['settings'];
    /** El widget flotante lo muestra; la página completa no. */
    onClose?: () => void;
    /** `panel` = drawer flotante · `page` = pantalla completa. */
    variant?: 'panel' | 'page';
    conversationId?: number | null;
}

/**
 * El asesor en sí.
 *
 * Se usa igual en el drawer flotante de la Zona de Clientes y en la pantalla
 * completa. Todo lo que cambia entre los dos es el chrome de alrededor, no la
 * conversación.
 */
export function AdvisorPanel({
    customer,
    recentPurchases,
    settings,
    onClose,
    variant = 'panel',
    conversationId = null,
}: Props) {
    const {
        messages,
        status,
        context,
        error,
        send,
        load,
        sendFeedback,
        requestHuman,
        dismissError,
    } = useConversation({ conversationId });

    const [contextOpen, setContextOpen] = useState(false);
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (conversationId !== null) {
            void load(conversationId);
        }
    }, [conversationId, load]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, [messages]);

    const busy = status.kind !== 'idle' && status.kind !== 'error';
    const empty = messages.length === 0 || (messages.length === 1 && messages[0]?.role === 'assistant');

    const handleSend = useCallback((text: string, files: File[]) => void send(text, files), [send]);

    const handleConfirmProduct = useCallback(
        (messageId: number | string, productId: number) => {
            void sendFeedback(messageId, productId, true);
            void send('Sí, es ese.', []);
        },
        [send, sendFeedback],
    );

    return (
        <div className="flex h-full min-h-0 flex-col bg-surface-base">
            {/* Header */}
            <header className="flex shrink-0 items-center gap-2.5 border-b border-edge-subtle bg-surface-raised px-3.5 py-2.5">
                <span
                    aria-hidden
                    className="flex h-8 w-8 shrink-0 items-center justify-center rounded-card bg-brand-500 text-white"
                >
                    <Sparkles className="h-4 w-4" strokeWidth={2} />
                </span>

                <div className="min-w-0 flex-1">
                    <p className="truncate text-[0.9375rem] font-semibold leading-tight text-ink-primary">
                        Asesor Técnico BMH
                    </p>
                    <p
                        aria-live="polite"
                        className={clsx(
                            'flex items-center gap-1.5 truncate text-caption',
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
                        {status.kind === 'idle' ? 'Listo para ayudarte' : status.label}
                    </p>
                </div>

                {settings.features.debug && (
                    <span className="hidden rounded-full bg-surface-sunken px-2 py-0.5 text-micro uppercase text-ink-tertiary ring-1 ring-edge-subtle sm:inline-flex">
                        {settings.provider.mode}
                    </span>
                )}

                <button
                    type="button"
                    onClick={() => setContextOpen((open) => !open)}
                    className={clsx(
                        'rounded-card px-2 py-1 text-caption transition-colors duration-150 ease-bmh',
                        contextOpen ? 'bg-brand-50 text-brand-700' : 'text-ink-secondary hover:bg-surface-sunken',
                    )}
                >
                    Detalle
                </button>

                {onClose !== undefined && (
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Cerrar el asesor"
                        className="flex h-8 w-8 shrink-0 items-center justify-center rounded-card text-ink-secondary hover:bg-surface-sunken"
                    >
                        <X aria-hidden className="h-4.5 w-4.5" strokeWidth={1.75} />
                    </button>
                )}
            </header>

            {error !== null && (
                <div role="alert" className="flex shrink-0 items-start gap-2 border-b border-red-200 bg-red-50 px-3.5 py-2">
                    <p className="flex-1 text-caption text-state-danger">{error}</p>
                    <button type="button" onClick={dismissError} aria-label="Cerrar aviso" className="text-state-danger">
                        <X aria-hidden className="h-4 w-4" />
                    </button>
                </div>
            )}

            <div className="flex min-h-0 flex-1">
                <div aria-live="polite" className="bmh-scroll min-h-0 flex-1 space-y-4 overflow-y-auto px-3.5 py-4">
                    {empty ? (
                        <EmptyState
                            name={customer.name}
                            visionEnabled={settings.features.vision}
                            hasHistory={recentPurchases.length > 0}
                            greeting={messages[0]?.content ?? null}
                            onPick={(text) => handleSend(text, [])}
                        />
                    ) : (
                        messages.map((message) => (
                            <MessageBubble
                                key={message.id}
                                message={message}
                                showDebug={settings.features.debug}
                                onConfirmProduct={handleConfirmProduct}
                                onFeedback={(id, productId, ok) => void sendFeedback(id, productId, ok)}
                                onQuickReply={(text) => handleSend(text, [])}
                            />
                        ))
                    )}
                    <div ref={bottomRef} />
                </div>

                {/* Contexto técnico: fijo en pantalla completa, desplegable en el drawer. */}
                <div
                    className={clsx(
                        'w-[280px] shrink-0 overflow-hidden',
                        variant === 'page' ? 'hidden xl:block' : contextOpen ? 'hidden lg:block' : 'hidden',
                    )}
                >
                    <ContextPanel
                        context={context}
                        customer={customer}
                        recentPurchases={recentPurchases}
                        canAssertStock={settings.canAssertStock}
                    />
                </div>
            </div>

            {/* En pantallas chicas el detalle va abajo, no al costado. */}
            {contextOpen && (
                <div className="max-h-[42%] shrink-0 overflow-hidden border-t border-edge-subtle lg:hidden">
                    <ContextPanel
                        context={context}
                        customer={customer}
                        recentPurchases={recentPurchases}
                        canAssertStock={settings.canAssertStock}
                    />
                </div>
            )}

            <div className="shrink-0 border-t border-edge-subtle bg-surface-base px-3 pb-3 pt-2.5">
                <Composer
                    disabled={busy || !customer.enabled}
                    visionEnabled={settings.features.vision}
                    maxImages={MAX_IMAGES}
                    onSend={handleSend}
                />

                {customer.enabled ? (
                    <button
                        type="button"
                        onClick={() => void requestHuman()}
                        className="mt-2 text-caption text-ink-tertiary underline-offset-2 hover:text-brand-700 hover:underline"
                    >
                        Prefiero hablar con un asesor
                    </button>
                ) : (
                    <p className="mt-2 text-caption text-state-warning">
                        Tu cuenta todavía no está habilitada para ver precios.
                    </p>
                )}
            </div>
        </div>
    );
}

interface EmptyStateProps {
    name: string;
    visionEnabled: boolean;
    hasHistory: boolean;
    greeting: string | null;
    onPick: (text: string) => void;
}

function EmptyState({ name, visionEnabled, hasHistory, greeting, onPick }: EmptyStateProps) {
    const actions = [
        visionEnabled && { icon: Camera, label: 'Mandar una foto', hint: 'La analizo y busco la pieza', text: '' },
        { icon: Tag, label: 'Buscar por código', hint: 'Aunque sea parcial', text: 'Necesito buscar por código' },
        { icon: Search, label: 'Describir una pieza', hint: 'Contame qué es y para qué', text: 'Necesito un rotor' },
        hasHistory && {
            icon: History,
            label: 'Repetir una compra',
            hint: 'Lo que llevaste antes',
            text: 'Mostrame lo que compré antes',
        },
    ].filter(Boolean) as { icon: typeof Camera; label: string; hint: string; text: string }[];

    return (
        <div className="py-2">
            <h2 className="text-title text-ink-primary">Hola, {name}.</h2>
            <p className="mt-1 text-body text-ink-secondary">
                {greeting ?? '¿Qué pieza necesitás? Escribime, pegá un código o mandame una foto.'}
            </p>

            <ul className="mt-4 grid gap-2 sm:grid-cols-2">
                {actions.map((action) => (
                    <li key={action.label}>
                        <button
                            type="button"
                            disabled={action.text === ''}
                            onClick={() => onPick(action.text)}
                            className={clsx(
                                'flex w-full items-start gap-2.5 rounded-card border border-edge-subtle bg-surface-raised p-2.5 text-left shadow-raised transition-all duration-150 ease-bmh',
                                action.text === '' ? 'cursor-default opacity-90' : 'hover:border-brand-400 hover:shadow-lifted',
                            )}
                        >
                            <action.icon aria-hidden className="mt-0.5 h-4.5 w-4.5 shrink-0 text-brand-500" strokeWidth={1.75} />
                            <span className="min-w-0">
                                <span className="block text-caption font-semibold text-ink-primary">{action.label}</span>
                                <span className="block text-caption text-ink-tertiary">{action.hint}</span>
                            </span>
                        </button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
