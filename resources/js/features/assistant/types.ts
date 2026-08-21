/**
 * Tipos del asesor.
 *
 * Espejan exactamente lo que serializa el backend. Si algo no está acá, la UI
 * no debería estar mostrándolo.
 */

export type ConfidenceBand = 'very_high' | 'high' | 'ambiguous' | 'low';

export type FactState = 'confirmed' | 'inferred' | 'unknown';

export interface Provenance {
    source: string;
    source_detail?: string;
    source_id?: number | string;
    confidence: number;
    factual: boolean;
}

export interface AttributeValue {
    key: string;
    label: string;
    value: string;
    raw: string;
    type: 'dimension' | 'electrical' | 'count' | 'cross_reference' | 'text';
    unit: string | null;
    provenance: Provenance;
}

export interface CrossReference {
    kind: 'equivalence' | 'related_part';
    label: string;
    code: string;
    provenance: Provenance;
}

export interface CategoryView {
    id: number;
    name: string;
    alias: string | null;
    product_count: number;
}

export interface ProductView {
    id: number;
    code: string;
    name: string;
    category: CategoryView | null;
    brand: string | null;
    model: string | null;
    condition: { value: string; label: string };
    attributes: AttributeValue[];
    equivalences: CrossReference[];
    related_parts: CrossReference[];
    images: string[] | { file: string; url: string }[];
    duplicate_code: boolean;
}

export interface Candidate {
    product: ProductView;
    confidence: number;
    confidence_band: ConfidenceBand;
    confidence_label: string;
    confidence_short: string;
    matched_on: string[];
    /** Precio del cliente ya calculado por el PricingEngine para esta card. */
    price?: PriceQuote | null;
    debug?: { score: number; signals: Record<string, number> };
}

export interface PriceQuote {
    product_id: number;
    status: 'verified' | 'requires_validation' | 'unavailable';
    list_price: number | null;
    customer_discount: number;
    product_discount: number;
    category_discount: number;
    bonification: number;
    tax: number;
    net_price: number | null;
    price_with_tax: number | null;
    resale_price: number | null;
    currency: string;
    calculation_source: string;
    notes: string[];
}

export interface NextQuestion {
    key: string;
    label: string;
    options: string[];
    gain: number;
}

export interface ContextPanelData {
    known: { key: string; value: string; state: FactState }[];
    missing: string[];
    category: string | null;
    candidate_count: number;
}

export interface VisionAnalysis {
    part_type: string | null;
    confidence: number;
    visible_codes: string[];
    detected_text: string[];
    attributes: Record<string, string>;
    category_hints: string[];
    brand_guess: string | null;
    description: string | null;
    usable: boolean;
    reason: string | null;
}

export interface DebugInfo {
    intent: string;
    strategy: string;
    provider: string | null;
    model: string | null;
    prompt_version: string;
    usage: {
        input_tokens: number;
        output_tokens: number;
        images_analyzed: number;
        estimated_cost: number | null;
    };
    total_ms: number;
    tools: { tool: string; latency_ms: number; ok: boolean }[];
    disambiguation: string;
}

export interface TurnResult {
    message: { id: number; role: 'assistant'; content: string; created_at: string | null };
    candidates: Candidate[];
    candidate_count: number;
    next_question: NextQuestion | null;
    price: PriceQuote | null;
    handoff: { id: number; reason: string } | null;
    conflicts: { key: string; confirmed: string; observed: string }[];
    context: ContextPanelData;
    vision: VisionAnalysis[];
    debug?: DebugInfo;
}

export interface ChatAttachment {
    id: number;
    url: string;
}

export interface ChatMessage {
    id: number | string;
    role: 'user' | 'assistant';
    content: string;
    createdAt?: string | null;
    pending?: boolean;
    candidates?: Candidate[];
    candidateCount?: number;
    nextQuestion?: NextQuestion | null;
    price?: PriceQuote | null;
    handoff?: { id: number; reason: string } | null;
    conflicts?: { key: string; confirmed: string; observed: string }[];
    vision?: VisionAnalysis[];
    attachments?: ChatAttachment[];
    debug?: DebugInfo;
    feedbackGiven?: boolean;
}

export interface ConversationSummary {
    id: number;
    title: string | null;
    status: string;
    updated_at: string;
}

export interface AssistantPageProps {
    customer: {
        name: string;
        code: string | null;
        segment: string;
        enabled: boolean;
        resale: boolean;
    };
    conversations: ConversationSummary[];
    activeConversation: number | null;
    recentPurchases: {
        product_id: number;
        code: string;
        name: string;
        category: string | null;
    }[];
    settings: {
        provider: { provider: string; mode: 'MOCK' | 'LIVE'; fallback: string | null; prompt_version: string };
        features: { vision: boolean; history: boolean; debug: boolean };
        canAssertStock: boolean;
    };
}

/** Estados reales del asesor. Nunca se muestra uno que no esté ocurriendo. */
export type AdvisorStatus =
    | { kind: 'idle' }
    | { kind: 'uploading'; label: string }
    | { kind: 'analyzing'; label: string }
    | { kind: 'searching'; label: string }
    | { kind: 'writing'; label: string }
    | { kind: 'error'; label: string };
