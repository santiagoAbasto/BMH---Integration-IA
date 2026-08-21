import clsx from 'clsx';
import { CircleAlert, ThumbsDown, ThumbsUp, UserRound } from 'lucide-react';
import { ProductCard } from './ProductCard';
import type { ChatMessage } from '../types';

interface Props {
    message: ChatMessage;
    showDebug: boolean;
    onConfirmProduct: (messageId: number | string, productId: number) => void;
    onFeedback: (messageId: number | string, productId: number | null, wasCorrect: boolean) => void;
    onQuickReply: (text: string) => void;
}

export function MessageBubble({ message, showDebug, onConfirmProduct, onFeedback, onQuickReply }: Props) {
    const isUser = message.role === 'user';

    if (isUser) {
        return (
            <div className="flex animate-bubble-in justify-end">
                <div className="max-w-bubble">
                    {message.attachments !== undefined && message.attachments.length > 0 && (
                        <div className="mb-1.5 flex flex-wrap justify-end gap-1.5">
                            {message.attachments.map((attachment) => (
                                <img
                                    key={attachment.id}
                                    src={attachment.url}
                                    alt="Foto enviada por vos"
                                    className="h-28 w-28 rounded-bubble border border-edge-subtle object-cover"
                                />
                            ))}
                        </div>
                    )}

                    {message.content.trim() !== '' && (
                        <p className="rounded-bubble rounded-br-[4px] bg-brand-500 px-3.5 py-2.5 text-body text-white">
                            {message.content}
                        </p>
                    )}
                </div>
            </div>
        );
    }

    const candidates = message.candidates ?? [];
    const topProductId = candidates[0]?.product.id ?? null;

    // Los atributos que difieren son los que hay que resaltar en las cards.
    const highlightKeys = differingAttributeKeys(candidates);

    return (
        <div className="flex animate-bubble-in flex-col gap-2.5">
            {message.content.trim() !== '' || message.pending ? (
                <div className="max-w-bubble rounded-bubble rounded-bl-[4px] border border-edge-subtle bg-surface-raised px-3.5 py-2.5 shadow-raised">
                    {message.content.trim() === '' && message.pending ? (
                        <span className="flex gap-1 py-1" aria-label="El asesor está escribiendo">
                            {[0, 1, 2].map((dot) => (
                                <span
                                    key={dot}
                                    className="h-1.5 w-1.5 animate-pulse rounded-full bg-ink-tertiary"
                                    style={{ animationDelay: `${dot * 150}ms` }}
                                />
                            ))}
                        </span>
                    ) : (
                        <p className="whitespace-pre-wrap text-body text-ink-primary">{message.content}</p>
                    )}
                </div>
            ) : null}

            {message.conflicts !== undefined && message.conflicts.length > 0 && (
                <div className="max-w-bubble rounded-card border border-amber-200 bg-amber-50 px-3 py-2">
                    {message.conflicts.map((conflict) => (
                        <p key={conflict.key} className="flex items-start gap-2 text-caption text-state-warning">
                            <CircleAlert aria-hidden className="mt-0.5 h-4 w-4 shrink-0" strokeWidth={1.75} />
                            <span>
                                Antes me dijiste <strong>{conflict.confirmed}</strong>, pero en la foto parece{' '}
                                <strong>{conflict.observed}</strong>. ¿Cuál es?
                            </span>
                        </p>
                    ))}
                </div>
            )}

            {candidates.length > 0 && (
                <div
                    className={clsx(
                        'grid max-w-bubble gap-2.5',
                        candidates.length > 1 && 'sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2',
                    )}
                >
                    {candidates.map((candidate, index) => (
                        <ProductCard
                            key={candidate.product.id}
                            candidate={candidate}
                            price={
                                // Cada candidato ya viene con su precio. El de
                                // `message.price` es el de una consulta explícita
                                // de precio y sólo aplica al producto cotizado.
                                candidate.price ??
                                (message.price?.product_id === candidate.product.id ? message.price : null)
                            }
                            highlightKeys={highlightKeys}
                            index={index}
                            showDebug={showDebug}
                            onConfirm={(productId) => onConfirmProduct(message.id, productId)}
                        />
                    ))}
                </div>
            )}

            {message.candidateCount !== undefined && message.candidateCount > candidates.length && (
                <p className="text-caption text-ink-tertiary">
                    Hay {message.candidateCount - candidates.length} coincidencias más. Con un dato extra las achico.
                </p>
            )}

            {/* Quick replies que salen del estado real de la búsqueda, no de una lista fija. */}
            {message.nextQuestion !== null && message.nextQuestion !== undefined && (
                <div className="flex max-w-bubble flex-wrap gap-1.5">
                    {message.nextQuestion.options.slice(0, 5).map((option) => (
                        <button
                            key={option}
                            type="button"
                            onClick={() => onQuickReply(`${message.nextQuestion?.label}: ${option}`)}
                            className="min-h-[2.25rem] rounded-full border border-edge bg-surface-raised px-3 text-caption text-ink-secondary transition-colors duration-150 ease-bmh hover:border-brand-400 hover:text-brand-700"
                        >
                            {option}
                        </button>
                    ))}
                </div>
            )}

            {message.handoff !== null && message.handoff !== undefined && (
                <div className="flex max-w-bubble items-center gap-2 rounded-card border border-brand-200 bg-brand-50 px-3 py-2">
                    <UserRound aria-hidden className="h-4 w-4 shrink-0 text-brand-700" strokeWidth={1.75} />
                    <p className="text-caption text-brand-900">
                        La consulta quedó preparada para un asesor de BMH.
                    </p>
                </div>
            )}

            {/* "¿Era este?" — discreto, sólo cuando hubo una identificación. */}
            {candidates.length > 0 && typeof message.id === 'number' && (
                <div className="flex items-center gap-2">
                    {message.feedbackGiven === true ? (
                        <p className="text-caption text-ink-tertiary">Gracias, lo tenemos en cuenta.</p>
                    ) : (
                        <>
                            <span className="text-caption text-ink-tertiary">¿Era este?</span>
                            <button
                                type="button"
                                onClick={() => onFeedback(message.id, topProductId, true)}
                                className="inline-flex min-h-[2rem] items-center gap-1 rounded-full border border-edge px-2.5 text-caption text-ink-secondary hover:border-signal-500 hover:text-signal-700"
                            >
                                <ThumbsUp aria-hidden className="h-3.5 w-3.5" strokeWidth={1.75} />
                                Sí
                            </button>
                            <button
                                type="button"
                                onClick={() => onFeedback(message.id, topProductId, false)}
                                className="inline-flex min-h-[2rem] items-center gap-1 rounded-full border border-edge px-2.5 text-caption text-ink-secondary hover:border-state-danger hover:text-state-danger"
                            >
                                <ThumbsDown aria-hidden className="h-3.5 w-3.5" strokeWidth={1.75} />
                                No
                            </button>
                        </>
                    )}
                </div>
            )}

            {showDebug && message.debug !== undefined && (
                <details className="max-w-bubble rounded-card border border-dashed border-edge bg-surface-sunken px-3 py-2">
                    <summary className="cursor-pointer text-micro uppercase text-ink-tertiary">
                        AI debug · {message.debug.strategy} · {message.debug.total_ms} ms
                    </summary>
                    <dl className="mt-2 grid grid-cols-2 gap-x-4 gap-y-1 font-mono text-[0.6875rem] text-ink-secondary">
                        <dt>intent</dt>
                        <dd>{message.debug.intent}</dd>
                        <dt>provider</dt>
                        <dd>{message.debug.provider}</dd>
                        <dt>model</dt>
                        <dd>{message.debug.model ?? '—'}</dd>
                        <dt>prompt</dt>
                        <dd>{message.debug.prompt_version}</dd>
                        <dt>tokens in/out</dt>
                        <dd>
                            {message.debug.usage.input_tokens}/{message.debug.usage.output_tokens}
                        </dd>
                        <dt>imágenes</dt>
                        <dd>{message.debug.usage.images_analyzed}</dd>
                        <dt>desambiguación</dt>
                        <dd>{message.debug.disambiguation}</dd>
                    </dl>
                </details>
            )}
        </div>
    );
}

/** Claves de atributo en las que los candidatos no coinciden. */
function differingAttributeKeys(candidates: ChatMessage['candidates']): string[] {
    if (candidates === undefined || candidates.length < 2) {
        return [];
    }

    const byKey = new Map<string, Set<string>>();

    for (const candidate of candidates) {
        for (const attribute of candidate.product.attributes) {
            const values = byKey.get(attribute.key) ?? new Set<string>();
            values.add(attribute.value);
            byKey.set(attribute.key, values);
        }
    }

    return [...byKey.entries()].filter(([, values]) => values.size > 1).map(([key]) => key);
}
