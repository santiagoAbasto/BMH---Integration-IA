import clsx from 'clsx';
import { CircleAlert, CircleCheck, CircleDashed, CircleHelp } from 'lucide-react';
import type { ConfidenceBand } from '../types';

/**
 * Coincidencia.
 *
 * Nunca muestra el número: el cliente no necesita saber que el score fue 0.87,
 * necesita saber si puede confiar. Color + icono + texto, para que no dependa
 * sólo del color.
 */
const STYLES: Record<ConfidenceBand, { className: string; Icon: typeof CircleCheck }> = {
    very_high: { className: 'bg-signal-500/15 text-signal-700 ring-signal-500/30', Icon: CircleCheck },
    high: { className: 'bg-brand-50 text-brand-700 ring-brand-200', Icon: CircleCheck },
    ambiguous: { className: 'bg-amber-50 text-state-warning ring-amber-200', Icon: CircleAlert },
    low: { className: 'bg-surface-sunken text-ink-tertiary ring-edge-subtle', Icon: CircleHelp },
};

interface Props {
    band: ConfidenceBand;
    /** Texto corto que se pinta en el badge. */
    label: string;
    /** Texto completo, para el tooltip y los lectores de pantalla. */
    title?: string;
    className?: string;
}

export function ConfidenceBadge({ band, label, title, className }: Props) {
    const style = STYLES[band] ?? { className: 'bg-surface-sunken text-ink-tertiary ring-edge-subtle', Icon: CircleDashed };
    const { Icon } = style;

    return (
        <span
            title={title ?? label}
            className={clsx(
                'inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-micro uppercase ring-1',
                style.className,
                className,
            )}
        >
            <Icon aria-hidden className="h-3 w-3" strokeWidth={2.25} />
            {label}
        </span>
    );
}
