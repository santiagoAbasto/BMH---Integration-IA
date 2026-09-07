import { useState } from 'react';
import clsx from 'clsx';
import { ChevronDown, ImageOff, Maximize2, Package, X } from 'lucide-react';
import { formatArs } from '@/lib/api';
import { ConfidenceBadge } from './ConfidenceBadge';
import type { Candidate, PriceQuote } from '../types';

interface Props {
    candidate: Candidate;
    price?: PriceQuote | null;
    /** Atributos que difieren entre candidatos: son los que vale la pena mostrar. */
    highlightKeys?: string[];
    onConfirm?: (productId: number) => void;
    showDebug?: boolean;
    index?: number;
}

function imageUrl(images: Candidate['product']['images']): string | null {
    const first = images[0];

    if (first === undefined) {
        return null;
    }

    return typeof first === 'string' ? `/imagenes/${first}` : first.url;
}

export function ProductCard({ candidate, price, highlightKeys, onConfirm, showDebug, index = 0 }: Props) {
    const [expanded, setExpanded] = useState(false);
    const [zoomed, setZoomed] = useState(false);
    const { product } = candidate;

    const url = imageUrl(product.images);

    // Se priorizan los atributos discriminantes; si no hay, los tres primeros.
    const ordered = highlightKeys?.length
        ? [
              ...product.attributes.filter((a) => highlightKeys.includes(a.key)),
              ...product.attributes.filter((a) => !highlightKeys.includes(a.key)),
          ]
        : product.attributes;

    const visible = expanded ? ordered : ordered.slice(0, 3);
    const hidden = ordered.length - visible.length;

    return (
        <article
            className="animate-card-in rounded-card border border-edge-subtle bg-surface-raised shadow-raised transition-shadow duration-200 ease-bmh hover:border-edge hover:shadow-lifted"
            style={{ animationDelay: `${index * 60}ms` }}
        >
            <div className="flex gap-3 p-3">
                <div className="h-28 w-28 shrink-0 overflow-hidden rounded border border-edge-subtle bg-white">
                    {url === null ? (
                        <div
                            className="flex h-full w-full flex-col items-center justify-center gap-1 bg-surface-sunken text-ink-tertiary"
                            title="Sin imagen en el catálogo"
                        >
                            <ImageOff aria-hidden className="h-5 w-5" strokeWidth={1.75} />
                            <span className="text-micro">Sin foto</span>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setZoomed(true)}
                            className="group relative block h-full w-full"
                            title="Ampliar la foto"
                            aria-label={`Ampliar la foto de ${product.name}`}
                        >
                            {/* `contain`, no `cover`: la pieza se compara por su
                                forma completa y recortarla es justamente perder
                                lo que el cliente está mirando. */}
                            <img
                                src={url}
                                alt={`${product.name} — código ${product.code}`}
                                loading="lazy"
                                className="h-full w-full object-contain p-1"
                            />
                            <span className="absolute bottom-1 right-1 rounded bg-ink-primary/70 p-1 opacity-0 transition-opacity duration-150 group-hover:opacity-100">
                                <Maximize2 aria-hidden className="h-3 w-3 text-white" strokeWidth={2} />
                            </span>
                        </button>
                    )}
                </div>

                <div className="min-w-0 flex-1">
                    <div className="flex items-start justify-between gap-2">
                        <span
                            className="min-w-0 flex-1 truncate text-micro uppercase text-ink-tertiary"
                            title={product.category?.name ?? undefined}
                        >
                            {product.category?.name ?? 'Sin rubro'}
                        </span>
                        <ConfidenceBadge
                            band={candidate.confidence_band}
                            label={candidate.confidence_short}
                            title={candidate.confidence_label}
                        />
                    </div>

                    {/* El nombre es lo que identifica la pieza: "TIPO BOSCH 5P"
                        y "TIPO BOSCH 2P" sólo se distinguen por el final, que es
                        exactamente lo que un `truncate` se come. */}
                    <h3 className="mt-0.5 line-clamp-2 text-subtitle text-ink-primary" title={product.name}>
                        {product.name}
                    </h3>

                    <div className="mt-1 flex flex-wrap items-center gap-2">
                        <code className="rounded bg-surface-sunken px-1.5 py-0.5 font-mono text-[0.875rem] font-medium text-ink-primary">
                            {product.code}
                        </code>

                        <span
                            className={clsx(
                                'rounded-full px-2 py-0.5 text-micro uppercase ring-1',
                                product.condition.value === 'new'
                                    ? 'bg-signal-500/15 text-signal-700 ring-signal-500/30'
                                    : 'bg-brand-50 text-brand-700 ring-brand-200',
                            )}
                        >
                            {product.condition.label}
                        </span>

                        {product.brand !== null && (
                            <span className="text-caption text-ink-secondary">{product.brand}</span>
                        )}

                        {product.duplicate_code && (
                            <span
                                className="rounded-full bg-amber-50 px-2 py-0.5 text-micro uppercase text-state-warning ring-1 ring-amber-200"
                                title="Este código figura en más de un artículo del catálogo"
                            >
                                Código repetido
                            </span>
                        )}
                    </div>

                    {/* Por qué está esta pieza acá. Un listado sin motivo obliga
                        al cliente a confiar a ciegas; decir "coincide el código"
                        o "por el parecido de la foto" es la diferencia entre una
                        lista y un asesor. */}
                    {candidate.matched_on.length > 0 && (
                        <p className="mt-1.5 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-caption text-ink-secondary">
                            <span className="text-ink-tertiary">Coincide por</span>
                            {candidate.matched_on.slice(0, 3).map((motivo) => (
                                <span
                                    key={motivo}
                                    className="rounded bg-brand-50 px-1.5 py-0.5 font-medium text-brand-700"
                                >
                                    {motivo}
                                </span>
                            ))}
                        </p>
                    )}
                </div>
            </div>

            {visible.length > 0 && (
                <dl className="border-t border-edge-subtle px-3 py-2">
                    {visible.map((attribute) => (
                        <div key={attribute.key} className="flex items-baseline justify-between gap-3 py-0.5">
                            <dt
                                className={clsx(
                                    'text-caption',
                                    highlightKeys?.includes(attribute.key)
                                        ? 'font-semibold text-ink-primary'
                                        : 'text-ink-tertiary',
                                )}
                            >
                                {attribute.label}
                            </dt>
                            <dd className="text-caption font-medium text-ink-primary">{attribute.value}</dd>
                        </div>
                    ))}

                    {hidden > 0 && (
                        <button
                            type="button"
                            onClick={() => setExpanded(true)}
                            className="mt-1 inline-flex items-center gap-1 text-caption text-brand-600 hover:text-brand-700"
                        >
                            <ChevronDown aria-hidden className="h-3.5 w-3.5" />
                            Ver {hidden} característica{hidden === 1 ? '' : 's'} más
                        </button>
                    )}
                </dl>
            )}

            {product.equivalences.length > 0 && (
                <div className="border-t border-edge-subtle px-3 py-2">
                    <p className="text-micro uppercase text-ink-tertiary">Equivalencias</p>
                    <p className="mt-1 font-mono text-caption text-ink-secondary">
                        {product.equivalences.slice(0, 4).map((e) => e.code).join(' · ')}
                    </p>
                </div>
            )}

            <div className="flex items-center justify-between gap-2 border-t border-edge-subtle px-3 py-2.5">
                <div>
                    {price?.status === 'verified' && price.net_price !== null ? (
                        <>
                            <p className="text-micro uppercase text-ink-tertiary">Tu precio</p>
                            <p className="text-subtitle text-ink-primary">
                                {formatArs(price.net_price)}{' '}
                                <span className="text-caption font-normal text-ink-tertiary">+ IVA</span>
                            </p>
                        </>
                    ) : price?.status === 'requires_validation' ? (
                        <p className="max-w-[16rem] text-caption text-state-warning">
                            El precio lo confirma un asesor.
                        </p>
                    ) : (
                        <p className="text-caption text-ink-tertiary">Consultá el precio en el chat</p>
                    )}
                </div>

                {onConfirm !== undefined && (
                    <button
                        type="button"
                        onClick={() => onConfirm(product.id)}
                        className="inline-flex min-h-[2.25rem] items-center gap-1.5 rounded-card bg-brand-500 px-3 text-caption font-semibold text-white transition-colors duration-150 ease-bmh hover:bg-brand-600"
                    >
                        <Package aria-hidden className="h-4 w-4" strokeWidth={1.75} />
                        Es este
                    </button>
                )}
            </div>

            {zoomed && url !== null && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label={`Foto de ${product.name}`}
                    className="fixed inset-0 z-[2147483647] flex items-center justify-center bg-ink-primary/80 p-6"
                    onClick={() => setZoomed(false)}
                >
                    <figure className="max-h-full max-w-2xl" onClick={(event) => event.stopPropagation()}>
                        <img
                            src={url}
                            alt={`${product.name} — código ${product.code}`}
                            className="max-h-[70vh] w-full rounded-card bg-white object-contain"
                        />
                        <figcaption className="mt-2 flex items-start justify-between gap-3 text-caption text-white">
                            <span>
                                <span className="font-mono font-semibold">{product.code}</span> — {product.name}
                            </span>
                            <button
                                type="button"
                                onClick={() => setZoomed(false)}
                                className="shrink-0 rounded p-1 hover:bg-white/15"
                                aria-label="Cerrar la foto"
                            >
                                <X aria-hidden className="h-4 w-4" strokeWidth={2} />
                            </button>
                        </figcaption>
                    </figure>
                </div>
            )}

            {showDebug && candidate.debug !== undefined && (
                <details className="border-t border-dashed border-edge-subtle px-3 py-2">
                    <summary className="cursor-pointer text-micro uppercase text-ink-tertiary">
                        Score {candidate.debug.score.toFixed(2)} · conf {candidate.confidence.toFixed(3)}
                    </summary>
                    <ul className="mt-1 space-y-0.5 font-mono text-[0.6875rem] text-ink-secondary">
                        {Object.entries(candidate.debug.signals).map(([signal, value]) => (
                            <li key={signal} className="flex justify-between gap-4">
                                <span>{signal}</span>
                                <span>+{value.toFixed(2)}</span>
                            </li>
                        ))}
                    </ul>
                </details>
            )}
        </article>
    );
}
