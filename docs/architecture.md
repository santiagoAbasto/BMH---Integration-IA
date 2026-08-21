# BMH — Arquitectura

```
CLIENTE (navegador / mobile)
     │
     ▼
REACT + INERTIA  ─── resources/js/features/assistant
     │  REST + SSE
     ▼
ASSISTANT API  ─── app/Http/Controllers/Api/AssistantController   (delgado)
     │
     ▼
CONVERSATION ORCHESTRATOR ─── app/Services/Ai/ConversationOrchestrator
     ├── ConversationMemoryService     hechos confirmados vs. inferidos
     ├── VisionAnalysisService*        (dentro del provider)
     ├── QueryRouter                   exact | structured | semantic | vision | hybrid
     ├── HybridProductSearchService
     ├── CandidateRankingService       multifactor, código > visión
     ├── CandidateDisambiguationService  ganancia de información
     ├── PricingEngine                 fórmula verificada
     ├── InventoryService              stock no verificado
     ├── HumanHandoffService
     └── AiProviderInterface ──► Gemini | OpenAI | Mock
     │
     ▼
SEMANTIC DOMAIN LAYER ─── app/Domain
     ├── CatalogSemanticLayer          columna_N → atributos con etiqueta
     ├── LegacyAttributeMap            diccionario de 74 slots
     └── DuplicateProductResolver
     │
     ▼
LEGACY REPOSITORIES ─── app/Domain/*/Legacy   (única capa que sabe SQL legacy)
     │
     ▼
BASE LEGACY BMH  (mysql_legacy · usuario con SELECT solamente)
```

---

## 1. Las tres bases

| Conexión | Base | Usuario | Permisos | Para qué |
|---|---|---|---|---|
| `mysql` | `bmh_legacy` | `bmh_app` | RW | El sitio actual (Blade). Sin cambios. |
| `mysql_legacy` | `bmh_legacy` | `bmh_reader` | **SELECT** | Todo lo que lee el asesor. |
| `mysql_ai` | `bmh_ai` | `bmh_app` | RW | Conversaciones, índice, auditoría. |

La separación no es una convención: `bmh_reader` tiene `GRANT SELECT` y nada
más. Si una capa del asesor intentara escribir en el catálogo, **MySQL la
rechaza**. Está cubierto por
`LegacyCatalogMappingTest::test_la_conexion_legacy_es_de_solo_lectura`.

Ninguna tabla de `bmh_ai` tiene foreign key contra la legacy: se referencian por
id, sin constraint, para no acoplar el esquema nuevo al viejo ni bloquear la
migración futura.

---

## 2. Anti-corruption layer

```
Base legacy
    ↓  LegacyBmh*Repository        ← el único SQL contra el esquema viejo
    ↓  CatalogSemanticLayer        ← columna_57 → "TERMINALES DESCRIPCION"
    ↓  ProductView / AttributeValue / PriceQuote   (DTOs con provenance)
    ↓  Services                     (búsqueda, pricing, desambiguación)
    ↓  ToolRegistry                 ← lo único que la IA puede invocar
```

**Ningún componente de React sabe que existe `columna_57`.** Tampoco el prompt,
ni las tools, ni el orquestador. Ese conocimiento vive en dos clases:
`LegacyAttributeMap` y `CatalogSemanticLayer`.

Interfaces del dominio (`app/Domain/*/Contracts`), atadas en
[`BmhAdvisorServiceProvider`](../app/Providers/BmhAdvisorServiceProvider.php):

| Interfaz | Implementación |
|---|---|
| `CatalogRepositoryInterface` | `LegacyBmhCatalogRepository` |
| `CustomerRepositoryInterface` | `LegacyBmhCustomerRepository` |
| `OrderHistoryRepositoryInterface` | `LegacyBmhOrderHistoryRepository` |
| `PricingRepositoryInterface` | `LegacyBmhPricingRepository` |

El día que BMH normalice el esquema, se escribe una implementación nueva y se
cambia el binding. Nada aguas arriba se entera.

---

## 3. El orden de un turno

Es el corazón del diseño y refleja la regla central del proyecto.

```
1. cargar contexto y memoria de la conversación
2. interpretar imágenes            → INFERENCIA (provenance: ai_vision)
3. interpretar texto               → INFERENCIA (provenance: ai_text)
4. la APLICACIÓN decide qué buscar → SearchQuery acumulada
5. la BASE confirma                → candidatos reales
6. desambiguación + pricing        → determinístico
7. ¿hace falta derivar?            → HumanHandoffService
8. recién ahora el modelo REDACTA  → con los datos ya resueltos
9. persistir + auditar
```

Cuando le toca hablar al modelo, **qué producto y a qué precio ya están
decididos**. El modelo pone las palabras.

Por eso la demo con `MockAiProvider` se siente inteligente: la inteligencia está
en los pasos 4-7, no en el modelo. Conectar Gemini mejora la comprensión de
lenguaje libre y habilita visión real, pero no cambia qué se elige ni cuánto
sale.

---

## 4. Autenticación

**No se creó un segundo login.** Se reutiliza la sesión de la Zona de Clientes
que ya existe: guard `web` sobre `users`, middleware `cliente`.

Lo único que se agregó es `customer.api`
([EnsureCustomerForApi](../app/Http/Middleware/EnsureCustomerForApi.php)), que
hace lo mismo que `cliente` pero devuelve **401 JSON** en vez de redirigir a
`/`. Sin eso, un fetch con la sesión vencida recibiría un 200 con HTML y el chat
se rompería de forma incomprensible.

Las rutas del asesor viven en el grupo `web` a propósito: sesión por cookie y
CSRF sin configuración extra. Ver [routes/assistant.php](../routes/assistant.php).

### Aislamiento de clientes

- El cliente **siempre** se resuelve desde `Auth::guard('web')`. Nunca desde el
  request.
- `AiConversation::scopeOwnedBy()` + `findOrFail` → un id ajeno da 404, no 403:
  no se filtra ni la existencia.
- `OrderHistoryRepository` pone `pedidos.cliente_id` en el WHERE del SQL. **No
  existe** un método que devuelva el historial de otro.
- Los adjuntos se sirven por una ruta que valida propiedad; el disco es privado.

---

## 5. Frontend

```
resources/js/
  app.tsx                       bootstrap de Inertia
  lib/api.ts                    fetch + CSRF + parser de SSE
  features/assistant/
    types.ts                    espejo de lo que serializa el backend
    hooks/useConversation.ts    todo el ciclo del chat
    components/
      AdvisorHeader.tsx         estado real, nunca fake loading
      Composer.tsx              texto + cámara + adjunto + paste + drag
      MessageBubble.tsx         burbuja + cards + quick replies + feedback
      ProductCard.tsx           card de producto
      ConfidenceBadge.tsx       color + icono + texto
      ContextPanel.tsx          panel técnico con chips confirmado/inferido
  pages/Customer/Assistant/Index.tsx
```

TypeScript en `strict` + `noUncheckedIndexedAccess`. Sin `any`.

**Inertia para páginas, REST/SSE para el chat.** Forzar el streaming y la subida
de archivos por Inertia habría sido pelearse con la herramienta.

⚠️ El cliente `@inertiajs/react` tiene que ser **v2**, no v3: v3 cambió el
transporte del payload (`<script type="application/json">` en lugar de
`data-page` en el div) y no es compatible con `inertia-laravel` v2. Con la
combinación equivocada la página monta en blanco.

---

## 6. Streaming

`POST /api/assistant/conversations/{id}/stream` responde `text/event-stream`:

| Evento | Cuándo | Contenido |
|---|---|---|
| `status` | al empezar | "Analizando imagen…" / "Buscando en catálogo…" |
| `data` | datos resueltos | candidatos, precio, contexto, próxima pregunta |
| `token` | redacción | fragmentos de texto |
| `done` | fin | mensaje final + debug |
| `error` | falla | mensaje accionable |

`data` va **antes** que los `token`: las cards se pintan mientras se escribe la
respuesta. Y el precio ya está verificado cuando se emite — nunca se transmite
un número provisional.

`EventSource` no sirve (no soporta POST ni headers), así que `lib/api.ts` lee el
ReadableStream y parsea los frames a mano.

---

## 7. La base propia del asesor

| Tabla | Para qué |
|---|---|
| `ai_conversations` | Conversación, intención, artículo resuelto, consumo. |
| `ai_messages` | Mensajes + metadata. **Sin chain-of-thought.** |
| `ai_attachments` | Original, versión de análisis y thumbnail. |
| `ai_message_tool_calls` | Qué tools se ejecutaron y cuánto tardaron. |
| `ai_product_candidates` | Qué se le mostró al cliente y con qué confianza. |
| `ai_customer_context` | Hechos: `confirmed` / `inferred` / `unknown`. |
| `ai_feedback` | "¿Era este?" Sí/No. |
| `ai_handoffs` | Derivaciones, con resumen para el asesor. |
| `ai_audit_logs` | Eventos, proveedor, modelo, versión de prompt, latencia. |
| `catalog_search_documents` | Índice materializado + FULLTEXT. |
| `catalog_sync_runs` | Métricas y anomalías de cada sync. |

---

## 8. Comandos

```bash
php artisan bmh:catalog-sync     # idempotente: 5.054 productos en ~2 s
php artisan bmh:catalog-sync --fresh
php artisan bmh:data-audit       # hallazgos por severidad
php artisan bmh:data-audit --json
```

`catalog-sync` guarda un hash del contenido por documento: una corrida sobre un
catálogo sin cambios reescribe **cero** filas.

---

## 9. Decisiones tomadas (y por qué)

| Decisión | Motivo |
|---|---|
| Sin vector store | 5.054 productos. MySQL responde en decenas de ms. Ver [catalog-search.md](catalog-search.md). |
| Sin Elasticsearch | Mismo motivo. El índice propio con FULLTEXT alcanza. |
| Índice materializado en base propia | La legacy no tiene índice sobre `codigo`/`marca`/`nombre` y no la tocamos. |
| Reutilizar el login existente | Ya funciona y es de sesión. Un segundo login sería superficie de ataque nueva. |
| Diccionario global de slots | Los 74 slots tienen etiqueta única en las 25 categorías. Un mapa en vez de 25. |
| Mock como proveedor por defecto | La demo tiene que correr sin API key. |
| Replicar producción en pricing | Cotizar distinto del carrito destruye la confianza. |
| `estado = 0` fuera de resultados | Son productos despublicados. |
| Handoff sólo si no queda pregunta útil | Derivar teniendo una pregunta disponible es renunciar antes de tiempo. |
