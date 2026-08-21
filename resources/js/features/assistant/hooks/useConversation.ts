import { useCallback, useRef, useState } from 'react';
import { ApiError, apiGet, apiPost, apiStream, apiUpload } from '@/lib/api';
import type {
    AdvisorStatus,
    ChatAttachment,
    ChatMessage,
    ContextPanelData,
    TurnResult,
} from '../types';

interface StartOptions {
    conversationId?: number | null;
}

/**
 * Estado de una conversación con el asesor.
 *
 * Concentra todo el ciclo: crear la conversación, subir fotos, mandar el turno
 * por streaming y acumular el contexto técnico. Los componentes sólo pintan.
 */
export function useConversation({ conversationId: initialId = null }: StartOptions = {}) {
    const [conversationId, setConversationId] = useState<number | null>(initialId);
    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [status, setStatus] = useState<AdvisorStatus>({ kind: 'idle' });
    const [context, setContext] = useState<ContextPanelData | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);

    const abortRef = useRef<AbortController | null>(null);

    const ensureConversation = useCallback(async (): Promise<number> => {
        if (conversationId !== null) {
            return conversationId;
        }

        const created = await apiPost<{ conversation: { id: number }; greeting: string }>(
            '/api/assistant/conversations',
        );

        setConversationId(created.conversation.id);
        setMessages([
            {
                id: `greeting-${created.conversation.id}`,
                role: 'assistant',
                content: created.greeting,
            },
        ]);

        return created.conversation.id;
    }, [conversationId]);

    const load = useCallback(async (id: number) => {
        setLoading(true);
        setError(null);

        try {
            const payload = await apiGet<{
                conversation: { id: number; title: string | null; status: string };
                messages: {
                    id: number;
                    role: 'user' | 'assistant';
                    content: string;
                    created_at: string | null;
                    attachments: ChatAttachment[];
                }[];
            }>(`/api/assistant/conversations/${id}`);

            setConversationId(payload.conversation.id);
            setMessages(
                payload.messages.map((message) => ({
                    id: message.id,
                    role: message.role,
                    content: message.content,
                    createdAt: message.created_at,
                    attachments: message.attachments,
                })),
            );
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'No pudimos cargar la conversación.');
        } finally {
            setLoading(false);
        }
    }, []);

    /** Sube las fotos y devuelve los ids para adjuntar al turno. */
    const uploadImages = useCallback(
        async (id: number, files: File[]): Promise<ChatAttachment[]> => {
            if (files.length === 0) {
                return [];
            }

            setStatus({ kind: 'uploading', label: 'Subiendo imagen…' });

            const form = new FormData();
            files.forEach((file) => form.append('images[]', file));

            const payload = await apiUpload<{ attachments: ChatAttachment[]; errors: string[] }>(
                `/api/assistant/conversations/${id}/attachments`,
                form,
            );

            if (payload.errors.length > 0 && payload.attachments.length === 0) {
                throw new ApiError(payload.errors[0] ?? 'No pudimos subir la imagen.', 422);
            }

            return payload.attachments;
        },
        [],
    );

    const send = useCallback(
        async (text: string, files: File[] = []) => {
            if (text.trim() === '' && files.length === 0) {
                return;
            }

            setError(null);

            const controller = new AbortController();
            abortRef.current = controller;

            try {
                const id = await ensureConversation();
                const attachments = await uploadImages(id, files);

                const userMessage: ChatMessage = {
                    id: `local-${Date.now()}`,
                    role: 'user',
                    content: text,
                    attachments,
                };

                const placeholderId = `pending-${Date.now()}`;

                setMessages((current) => [
                    ...current,
                    userMessage,
                    { id: placeholderId, role: 'assistant', content: '', pending: true },
                ]);

                setStatus({
                    kind: files.length > 0 ? 'analyzing' : 'searching',
                    label: files.length > 0 ? 'Analizando imagen…' : 'Buscando en catálogo…',
                });

                let streamed = '';

                await apiStream(
                    `/api/assistant/conversations/${id}/stream`,
                    { message: text, attachment_ids: attachments.map((a) => a.id) },
                    {
                        signal: controller.signal,
                        onEvent: (event, data) => {
                            if (event === 'status') {
                                const payload = data as { label: string };
                                setStatus({ kind: 'analyzing', label: payload.label });
                                return;
                            }

                            if (event === 'data') {
                                // Los datos duros llegan antes que el texto: las
                                // cards se pintan mientras se escribe la respuesta.
                                const payload = data as Pick<
                                    TurnResult,
                                    'candidates' | 'candidate_count' | 'price' | 'context' | 'next_question' | 'handoff'
                                >;

                                setContext(payload.context);
                                setStatus({
                                    kind: 'writing',
                                    label:
                                        payload.candidate_count > 1
                                            ? `Comparando ${payload.candidate_count} artículos…`
                                            : 'Preparando la respuesta…',
                                });

                                setMessages((current) =>
                                    current.map((message) =>
                                        message.id === placeholderId
                                            ? {
                                                  ...message,
                                                  candidates: payload.candidates,
                                                  candidateCount: payload.candidate_count,
                                                  price: payload.price,
                                                  nextQuestion: payload.next_question,
                                                  handoff: payload.handoff,
                                              }
                                            : message,
                                    ),
                                );
                                return;
                            }

                            if (event === 'token') {
                                streamed += (data as { text: string }).text;

                                setMessages((current) =>
                                    current.map((message) =>
                                        message.id === placeholderId
                                            ? { ...message, content: streamed }
                                            : message,
                                    ),
                                );
                                return;
                            }

                            if (event === 'done') {
                                const payload = data as { message: TurnResult['message']; debug?: TurnResult['debug'] };

                                setMessages((current) =>
                                    current.map((message) =>
                                        message.id === placeholderId
                                            ? {
                                                  ...message,
                                                  id: payload.message.id,
                                                  content: payload.message.content,
                                                  createdAt: payload.message.created_at,
                                                  pending: false,
                                                  debug: payload.debug,
                                              }
                                            : message,
                                    ),
                                );
                                setStatus({ kind: 'idle' });
                                return;
                            }

                            if (event === 'error') {
                                const payload = data as { message: string };
                                setError(payload.message);
                                setMessages((current) => current.filter((m) => m.id !== placeholderId));
                                setStatus({ kind: 'error', label: payload.message });
                            }
                        },
                    },
                );
            } catch (caught) {
                if (controller.signal.aborted) {
                    return;
                }

                const message =
                    caught instanceof ApiError
                        ? caught.message
                        : 'No pudimos conectarnos con el asesor. Probá de nuevo.';

                setError(message);
                setStatus({ kind: 'error', label: message });
                setMessages((current) => current.filter((m) => !m.pending));
            } finally {
                abortRef.current = null;
            }
        },
        [ensureConversation, uploadImages],
    );

    const sendFeedback = useCallback(
        async (messageId: number | string, productId: number | null, wasCorrect: boolean) => {
            if (conversationId === null || typeof messageId !== 'number') {
                return;
            }

            setMessages((current) =>
                current.map((m) => (m.id === messageId ? { ...m, feedbackGiven: true } : m)),
            );

            try {
                await apiPost(`/api/assistant/conversations/${conversationId}/feedback`, {
                    message_id: messageId,
                    product_id: productId,
                    was_correct: wasCorrect,
                });
            } catch {
                // El feedback es best-effort: si falla, no vale interrumpir al
                // cliente con un error.
            }
        },
        [conversationId],
    );

    const requestHuman = useCallback(async () => {
        if (conversationId === null) {
            return;
        }

        try {
            const payload = await apiPost<{ message: string }>(
                `/api/assistant/conversations/${conversationId}/handoff`,
            );

            setMessages((current) => [
                ...current,
                { id: `handoff-${Date.now()}`, role: 'assistant', content: payload.message },
            ]);
        } catch (caught) {
            setError(caught instanceof ApiError ? caught.message : 'No pudimos derivar la consulta.');
        }
    }, [conversationId]);

    const reset = useCallback(() => {
        abortRef.current?.abort();
        setConversationId(null);
        setMessages([]);
        setContext(null);
        setError(null);
        setStatus({ kind: 'idle' });
    }, []);

    return {
        conversationId,
        messages,
        status,
        context,
        error,
        loading,
        send,
        load,
        reset,
        sendFeedback,
        requestHuman,
        dismissError: () => setError(null),
    };
}
