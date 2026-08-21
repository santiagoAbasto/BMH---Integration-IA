import { useCallback, useEffect, useRef, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import clsx from 'clsx';
import { Camera, History, MessageSquarePlus, Search, Tag, X } from 'lucide-react';
import { AdvisorHeader } from '@/features/assistant/components/AdvisorHeader';
import { Composer } from '@/features/assistant/components/Composer';
import { ContextPanel } from '@/features/assistant/components/ContextPanel';
import { MessageBubble } from '@/features/assistant/components/MessageBubble';
import { useConversation } from '@/features/assistant/hooks/useConversation';
import type { AssistantPageProps } from '@/features/assistant/types';

const MAX_IMAGES = 3;

export default function AssistantIndex({
    customer,
    conversations,
    activeConversation,
    recentPurchases,
    settings,
}: AssistantPageProps) {
    const {
        conversationId,
        messages,
        status,
        context,
        error,
        send,
        load,
        reset,
        sendFeedback,
        requestHuman,
        dismissError,
    } = useConversation({ conversationId: activeConversation });

    const [contextOpen, setContextOpen] = useState(false);
    const bottomRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (activeConversation !== null) {
            void load(activeConversation);
        }
    }, [activeConversation, load]);

    useEffect(() => {
        bottomRef.current?.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, [messages]);

    const busy = status.kind !== 'idle' && status.kind !== 'error';
    const empty = messages.length === 0 || (messages.length === 1 && messages[0]?.role === 'assistant');

    const handleSend = useCallback(
        (text: string, files: File[]) => {
            void send(text, files);
        },
        [send],
    );

    const handleConfirmProduct = useCallback(
        (messageId: number | string, productId: number) => {
            void sendFeedback(messageId, productId, true);
            void send('Sí, es ese. ¿Cuánto sale?', []);
        },
        [send, sendFeedback],
    );

    return (
        <>
            <Head title="Asesor Técnico" />

            <div className="flex h-dvh flex-col bg-surface-base">
                <div className="grid min-h-0 flex-1 grid-cols-1 xl:grid-cols-[260px_minmax(0,1fr)_320px]">
                    {/* Conversaciones */}
                    <nav
                        aria-label="Tus consultas"
                        className="bmh-scroll hidden overflow-y-auto border-r border-edge-subtle bg-surface-raised p-3 xl:block"
                    >
                        <button
                            type="button"
                            onClick={() => {
                                reset();
                                router.visit('/asesor', { preserveState: false });
                            }}
                            className="mb-3 inline-flex w-full min-h-[2.5rem] items-center justify-center gap-1.5 rounded-card bg-brand-500 px-3 text-caption font-semibold text-white transition-colors duration-150 ease-bmh hover:bg-brand-600"
                        >
                            <MessageSquarePlus aria-hidden className="h-4 w-4" strokeWidth={1.75} />
                            Nueva consulta
                        </button>

                        <h2 className="px-1 text-micro uppercase text-ink-tertiary">Recientes</h2>
                        <ul className="mt-2 space-y-0.5">
                            {conversations.length === 0 && (
                                <li className="px-1 py-2 text-caption text-ink-tertiary">
                                    Todavía no hiciste consultas.
                                </li>
                            )}
                            {conversations.map((conversation) => (
                                <li key={conversation.id}>
                                    <button
                                        type="button"
                                        onClick={() => router.visit(`/asesor/${conversation.id}`)}
                                        className={clsx(
                                            'w-full truncate rounded-card px-2 py-2 text-left text-caption transition-colors duration-150 ease-bmh',
                                            conversation.id === conversationId
                                                ? 'bg-brand-50 font-medium text-brand-900'
                                                : 'text-ink-secondary hover:bg-surface-sunken',
                                        )}
                                    >
                                        {conversation.title ?? 'Consulta sin título'}
                                        {conversation.status === 'handed_off' && (
                                            <span className="ml-1 text-micro uppercase text-ink-tertiary">· derivada</span>
                                        )}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </nav>

                    {/* Chat */}
                    <main className="flex min-h-0 flex-col">
                        <AdvisorHeader
                            status={status}
                            providerMode={settings.provider.mode}
                            debug={settings.features.debug}
                            onRequestHuman={() => void requestHuman()}
                            onToggleContext={() => setContextOpen((open) => !open)}
                        />

                        {error !== null && (
                            <div
                                role="alert"
                                className="flex items-start gap-2 border-b border-red-200 bg-red-50 px-4 py-2.5"
                            >
                                <p className="flex-1 text-caption text-state-danger">{error}</p>
                                <button
                                    type="button"
                                    onClick={dismissError}
                                    aria-label="Cerrar el aviso"
                                    className="text-state-danger"
                                >
                                    <X aria-hidden className="h-4 w-4" />
                                </button>
                            </div>
                        )}

                        <div
                            aria-live="polite"
                            className="bmh-scroll min-h-0 flex-1 space-y-5 overflow-y-auto px-4 py-5"
                        >
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
                                        onFeedback={(messageId, productId, wasCorrect) =>
                                            void sendFeedback(messageId, productId, wasCorrect)
                                        }
                                        onQuickReply={(text) => handleSend(text, [])}
                                    />
                                ))
                            )}
                            <div ref={bottomRef} />
                        </div>

                        <div
                            className="border-t border-edge-subtle bg-surface-base px-3 pt-3"
                            style={{ paddingBottom: 'calc(0.75rem + var(--composer-safe-bottom))' }}
                        >
                            <Composer
                                disabled={busy || !customer.enabled}
                                visionEnabled={settings.features.vision}
                                maxImages={MAX_IMAGES}
                                onSend={handleSend}
                            />
                            {!customer.enabled && (
                                <p className="mt-2 text-caption text-state-warning">
                                    Tu cuenta todavía no está habilitada. Un asesor de BMH la activa y ya podés consultar precios.
                                </p>
                            )}
                        </div>
                    </main>

                    {/* Contexto técnico */}
                    <div className="hidden xl:block">
                        <ContextPanel
                            context={context}
                            customer={customer}
                            recentPurchases={recentPurchases}
                            canAssertStock={settings.canAssertStock}
                        />
                    </div>
                </div>

                {/* Contexto en mobile/tablet: bottom sheet */}
                {contextOpen && (
                    <div className="fixed inset-0 z-40 xl:hidden">
                        <button
                            type="button"
                            aria-label="Cerrar el contexto técnico"
                            onClick={() => setContextOpen(false)}
                            className="absolute inset-0 bg-surface-inverse/40"
                        />
                        <div className="absolute inset-x-0 bottom-0 max-h-[75dvh] overflow-hidden rounded-t-bubble bg-surface-raised shadow-overlay">
                            <ContextPanel
                                context={context}
                                customer={customer}
                                recentPurchases={recentPurchases}
                                canAssertStock={settings.canAssertStock}
                            />
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

interface EmptyStateProps {
    name: string;
    visionEnabled: boolean;
    hasHistory: boolean;
    greeting: string | null;
    onPick: (text: string) => void;
}

/**
 * Empty state.
 *
 * No es una pantalla vacía ni un muro de opciones: una pregunta y tres o cuatro
 * accesos que corresponden a cómo arranca realmente una consulta.
 */
function EmptyState({ name, visionEnabled, hasHistory, greeting, onPick }: EmptyStateProps) {
    const actions = [
        visionEnabled && { icon: Camera, label: 'Mandar una foto', hint: 'La analizo y busco la pieza', text: '' },
        { icon: Tag, label: 'Buscar por código', hint: 'Aunque sea parcial', text: 'Necesito buscar por código' },
        { icon: Search, label: 'Describir una pieza', hint: 'Contame qué es y para qué', text: 'Necesito un rotor' },
        hasHistory && { icon: History, label: 'Ver compras anteriores', hint: 'Repetir un pedido', text: 'Mostrame lo que compré antes' },
    ].filter(Boolean) as { icon: typeof Camera; label: string; hint: string; text: string }[];

    return (
        <div className="mx-auto flex h-full max-w-xl flex-col justify-center py-8">
            <h2 className="text-display text-ink-primary">Hola, {name}.</h2>
            <p className="mt-1 text-body text-ink-secondary">
                {greeting ?? '¿Qué pieza necesitás? Escribime, pegá un código o mandame una foto.'}
            </p>

            <ul className="mt-6 grid gap-2 sm:grid-cols-2">
                {actions.map((action) => (
                    <li key={action.label}>
                        <button
                            type="button"
                            disabled={action.text === ''}
                            onClick={() => onPick(action.text)}
                            className={clsx(
                                'flex w-full items-start gap-3 rounded-card border border-edge-subtle bg-surface-raised p-3 text-left shadow-raised transition-all duration-150 ease-bmh',
                                action.text === ''
                                    ? 'cursor-default opacity-90'
                                    : 'hover:border-brand-400 hover:shadow-lifted',
                            )}
                        >
                            <action.icon aria-hidden className="mt-0.5 h-5 w-5 shrink-0 text-brand-500" strokeWidth={1.75} />
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
