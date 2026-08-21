import { useCallback, useRef, useState, type ChangeEvent, type ClipboardEvent, type DragEvent, type KeyboardEvent } from 'react';
import clsx from 'clsx';
import { Camera, Paperclip, Send, X } from 'lucide-react';

interface Props {
    disabled: boolean;
    visionEnabled: boolean;
    maxImages: number;
    onSend: (text: string, files: File[]) => void;
}

interface Staged {
    file: File;
    preview: string;
}

/**
 * Composer.
 *
 * Soporta escribir, adjuntar, pegar y arrastrar. En mobile el botón de cámara
 * es acción de primer nivel: el cliente saca la foto de la pieza ahí mismo, en
 * el taller.
 */
export function Composer({ disabled, visionEnabled, maxImages, onSend }: Props) {
    const [text, setText] = useState('');
    const [staged, setStaged] = useState<Staged[]>([]);
    const [dragging, setDragging] = useState(false);

    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const fileRef = useRef<HTMLInputElement>(null);
    const cameraRef = useRef<HTMLInputElement>(null);

    const addFiles = useCallback(
        (incoming: FileList | File[] | null) => {
            if (incoming === null || !visionEnabled) {
                return;
            }

            const images = [...incoming].filter((file) => file.type.startsWith('image/'));

            setStaged((current) => {
                const room = Math.max(0, maxImages - current.length);

                return [
                    ...current,
                    ...images.slice(0, room).map((file) => ({ file, preview: URL.createObjectURL(file) })),
                ];
            });
        },
        [maxImages, visionEnabled],
    );

    const removeStaged = (index: number) => {
        setStaged((current) => {
            const target = current[index];
            if (target !== undefined) {
                URL.revokeObjectURL(target.preview);
            }

            return current.filter((_, i) => i !== index);
        });
    };

    const submit = () => {
        if (disabled || (text.trim() === '' && staged.length === 0)) {
            return;
        }

        onSend(text, staged.map((s) => s.file));

        staged.forEach((s) => URL.revokeObjectURL(s.preview));
        setStaged([]);
        setText('');

        if (textareaRef.current !== null) {
            textareaRef.current.style.height = 'auto';
        }
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        // Enter envía; Shift+Enter hace salto de línea.
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            submit();
        }
    };

    const handleInput = (event: ChangeEvent<HTMLTextAreaElement>) => {
        setText(event.target.value);

        const element = event.target;
        element.style.height = 'auto';
        element.style.height = `${Math.min(element.scrollHeight, 160)}px`;
    };

    const handlePaste = (event: ClipboardEvent<HTMLTextAreaElement>) => {
        const files = [...event.clipboardData.files];

        if (files.length > 0) {
            event.preventDefault();
            addFiles(files);
        }
    };

    const handleDrop = (event: DragEvent<HTMLDivElement>) => {
        event.preventDefault();
        setDragging(false);
        addFiles(event.dataTransfer.files);
    };

    return (
        <div
            onDragOver={(event) => {
                event.preventDefault();
                if (visionEnabled) setDragging(true);
            }}
            onDragLeave={() => setDragging(false)}
            onDrop={handleDrop}
            className={clsx(
                'rounded-bubble border bg-surface-raised shadow-raised transition-colors duration-150 ease-bmh',
                dragging ? 'border-brand-500 ring-4 ring-brand-100' : 'border-edge',
            )}
        >
            {staged.length > 0 && (
                <ul className="flex flex-wrap gap-2 border-b border-edge-subtle p-2.5">
                    {staged.map((item, index) => (
                        <li key={item.preview} className="relative">
                            <img
                                src={item.preview}
                                alt={`Adjunto ${index + 1}`}
                                className="h-16 w-16 rounded border border-edge-subtle object-cover"
                            />
                            <button
                                type="button"
                                onClick={() => removeStaged(index)}
                                aria-label={`Quitar adjunto ${index + 1}`}
                                className="absolute -right-1.5 -top-1.5 rounded-full bg-surface-inverse p-0.5 text-ink-inverse"
                            >
                                <X aria-hidden className="h-3.5 w-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            <div className="flex items-end gap-1.5 p-2">
                {visionEnabled && (
                    <>
                        <button
                            type="button"
                            onClick={() => cameraRef.current?.click()}
                            disabled={disabled || staged.length >= maxImages}
                            aria-label="Sacar una foto"
                            className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-card text-ink-secondary transition-colors duration-150 ease-bmh hover:bg-surface-sunken disabled:opacity-40 sm:hidden"
                        >
                            <Camera aria-hidden className="h-5 w-5" strokeWidth={1.75} />
                        </button>

                        <button
                            type="button"
                            onClick={() => fileRef.current?.click()}
                            disabled={disabled || staged.length >= maxImages}
                            aria-label="Adjuntar una foto"
                            className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-card text-ink-secondary transition-colors duration-150 ease-bmh hover:bg-surface-sunken disabled:opacity-40"
                        >
                            <Paperclip aria-hidden className="h-5 w-5" strokeWidth={1.75} />
                        </button>

                        <input
                            ref={cameraRef}
                            type="file"
                            accept="image/*"
                            capture="environment"
                            hidden
                            onChange={(event) => {
                                addFiles(event.target.files);
                                event.target.value = '';
                            }}
                        />
                        <input
                            ref={fileRef}
                            type="file"
                            accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
                            multiple
                            hidden
                            onChange={(event) => {
                                addFiles(event.target.files);
                                event.target.value = '';
                            }}
                        />
                    </>
                )}

                <label htmlFor="bmh-composer" className="sr-only">
                    Escribí tu consulta
                </label>
                <textarea
                    id="bmh-composer"
                    ref={textareaRef}
                    rows={1}
                    value={text}
                    disabled={disabled}
                    onChange={handleInput}
                    onKeyDown={handleKeyDown}
                    onPaste={handlePaste}
                    placeholder="Escribí, pegá un código o mandá una foto…"
                    className="max-h-40 min-h-[2.75rem] flex-1 resize-none border-0 bg-transparent px-1 py-2.5 text-body text-ink-primary placeholder:text-ink-tertiary focus:ring-0"
                />

                <button
                    type="button"
                    onClick={submit}
                    disabled={disabled || (text.trim() === '' && staged.length === 0)}
                    aria-label="Enviar"
                    className="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-card bg-brand-500 text-white transition-colors duration-150 ease-bmh hover:bg-brand-600 disabled:bg-edge disabled:text-ink-tertiary"
                >
                    <Send aria-hidden className="h-5 w-5" strokeWidth={1.75} />
                </button>
            </div>
        </div>
    );
}
