import { CircleAlert, CircleCheck, History, Layers, ShieldCheck } from 'lucide-react';
import clsx from 'clsx';
import type { AssistantPageProps, ContextPanelData } from '../types';

interface Props {
    context: ContextPanelData | null;
    customer: AssistantPageProps['customer'];
    recentPurchases: AssistantPageProps['recentPurchases'];
    canAssertStock: boolean;
}

const SEGMENT_LABEL: Record<string, string> = {
    preferencial: 'Cuenta preferencial',
    mayorista: 'Cuenta mayorista',
    con_acuerdo: 'Con acuerdo comercial',
    lista: 'Precio de lista',
};

/** Traduce la clave técnica del backend a algo que el cliente entienda. */
const FACT_LABEL: Record<string, string> = {
    category: 'Rubro',
    brand: 'Marca',
    model: 'Modelo',
    code: 'Código',
    voltage: 'Voltaje',
    amperes: 'Amperes',
    diameter: 'Diámetro',
    inner_diameter: 'Diámetro interno',
    outer_diameter: 'Diámetro externo',
    total_length: 'Largo total',
    splines: 'Estrías',
    teeth: 'Dientes',
    slots: 'Ranuras',
    pins: 'Pines',
    terminals: 'Terminales',
    application: 'Aplicación',
    rotation: 'Giro',
    type: 'Tipo',
};

export function ContextPanel({ context, customer, recentPurchases, canAssertStock }: Props) {
    return (
        <aside
            aria-label="Contexto técnico de la consulta"
            className="bmh-scroll flex h-full flex-col gap-5 overflow-y-auto border-l border-edge-subtle bg-surface-raised p-4"
        >
            <section>
                <h2 className="text-micro uppercase text-ink-tertiary">Consulta actual</h2>

                {context === null || (context.known.length === 0 && context.category === null) ? (
                    <p className="mt-2 text-caption text-ink-tertiary">
                        Acá vas a ver lo que el asesor va deduciendo de tu consulta.
                    </p>
                ) : (
                    <>
                        {context.category !== null && (
                            <p className="mt-2 flex items-center gap-1.5 text-subtitle text-ink-primary">
                                <Layers aria-hidden className="h-4 w-4 text-brand-500" strokeWidth={1.75} />
                                {context.category}
                            </p>
                        )}
                        <p className="mt-1 text-caption text-ink-secondary">
                            {context.candidate_count === 0
                                ? 'Sin candidatos todavía'
                                : `${context.candidate_count} candidato${context.candidate_count === 1 ? '' : 's'} en el catálogo`}
                        </p>
                    </>
                )}
            </section>

            {context !== null && context.known.length > 0 && (
                <section>
                    <h2 className="text-micro uppercase text-ink-tertiary">Datos conocidos</h2>
                    <dl className="mt-2 space-y-1.5">
                        {context.known.map((fact) => (
                            <div key={fact.key} className="flex items-baseline justify-between gap-2">
                                <dt className="text-caption text-ink-tertiary">
                                    {FACT_LABEL[fact.key] ?? fact.key}
                                </dt>
                                <dd className="flex items-center gap-1.5">
                                    <span className="text-caption font-medium text-ink-primary">{fact.value}</span>
                                    {/*
                                      El chip confirmado/inferido es la traducción visual
                                      del data provenance: un dato que dijo el cliente no
                                      es lo mismo que uno que dedujo una foto.
                                    */}
                                    <span
                                        title={
                                            fact.state === 'confirmed'
                                                ? 'Dato confirmado por vos o por el catálogo'
                                                : 'Deducido del análisis; puede no ser exacto'
                                        }
                                        className={clsx(
                                            'inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-micro uppercase ring-1',
                                            fact.state === 'confirmed'
                                                ? 'bg-signal-500/15 text-signal-700 ring-signal-500/30'
                                                : 'bg-surface-sunken text-ink-tertiary ring-edge-subtle',
                                        )}
                                    >
                                        {fact.state === 'confirmed' ? (
                                            <CircleCheck aria-hidden className="h-2.5 w-2.5" strokeWidth={2.5} />
                                        ) : (
                                            <CircleAlert aria-hidden className="h-2.5 w-2.5" strokeWidth={2.5} />
                                        )}
                                        {fact.state === 'confirmed' ? 'Confirmado' : 'Inferido'}
                                    </span>
                                </dd>
                            </div>
                        ))}
                    </dl>
                </section>
            )}

            {context !== null && context.missing.length > 0 && (
                <section>
                    <h2 className="text-micro uppercase text-ink-tertiary">Datos que faltan</h2>
                    <ul className="mt-2 flex flex-wrap gap-1.5">
                        {context.missing.map((item) => (
                            <li
                                key={item}
                                className="rounded-full border border-dashed border-edge px-2 py-0.5 text-caption text-ink-secondary"
                            >
                                {item}
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <section>
                <h2 className="text-micro uppercase text-ink-tertiary">Tu cuenta</h2>
                <p className="mt-2 flex items-center gap-1.5 text-caption text-ink-primary">
                    <ShieldCheck aria-hidden className="h-4 w-4 text-brand-500" strokeWidth={1.75} />
                    {SEGMENT_LABEL[customer.segment] ?? 'Cuenta activa'}
                </p>
                {customer.code !== null && (
                    <p className="mt-1 font-mono text-caption text-ink-tertiary">Cliente {customer.code}</p>
                )}
                {!canAssertStock && (
                    <p className="mt-2 rounded-card border border-amber-200 bg-amber-50 px-2 py-1.5 text-caption text-state-warning">
                        La disponibilidad la confirma un asesor: no la publicamos automáticamente.
                    </p>
                )}
            </section>

            {recentPurchases.length > 0 && (
                <section>
                    <h2 className="text-micro uppercase text-ink-tertiary">Historial relacionado</h2>
                    <ul className="mt-2 space-y-2">
                        {recentPurchases.slice(0, 5).map((purchase) => (
                            <li key={purchase.product_id} className="flex items-start gap-2">
                                <History aria-hidden className="mt-0.5 h-3.5 w-3.5 shrink-0 text-ink-tertiary" strokeWidth={1.75} />
                                <div className="min-w-0">
                                    <p className="truncate text-caption text-ink-primary" title={purchase.name}>
                                        {purchase.name}
                                    </p>
                                    <p className="font-mono text-micro text-ink-tertiary">{purchase.code}</p>
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </aside>
    );
}
