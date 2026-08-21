# BMH — Arquitectura de IA

> **La IA interpreta. La aplicación decide. La base de datos confirma.**

---

## 1. Qué hace y qué no hace el modelo

| Hace | No hace |
|---|---|
| Interpretar lenguaje libre | Elegir el producto |
| Extraer atributos de un mensaje | Calcular precios |
| Describir lo que ve en una foto | Identificar un artículo del catálogo |
| Redactar la respuesta | Ejecutar SQL |
| Decidir *qué preguntar* de una lista calculada | Afirmar disponibilidad |

Cuando el modelo redacta, **el producto y el precio ya están decididos** por el
orquestador. Ver [architecture.md §3](architecture.md).

Esto tiene una consecuencia práctica: la demo con `MockAiProvider` se siente
inteligente. La inteligencia está en la búsqueda, el ranking y la
desambiguación, no en el modelo.

---

## 2. AiProviderInterface

```php
interface AiProviderInterface
{
    public function name(): string;
    public function chat(array $messages, array $tools = [], array $options = []): AiResponse;
    public function analyzeImage(string $imagePath, string $context = ''): ImageAnalysis;
    public function structuredOutput(string $prompt, array $schema, array $options = []): array;
    public function embed(string $text): array;
    public function isAvailable(): bool;
}
```

| Implementación | Estado |
|---|---|
| `MockAiProvider` | Completo. Default sin API key. |
| `GeminiAiProvider` | Completo: chat, tool calling, visión con `responseSchema`, embeddings. |
| `OpenAiProvider` | Completo: chat, tool calling, visión con `json_schema`, embeddings. |

`AiProviderManager` elige. Sin `AI_API_KEY`, cae a `mock` y lo registra una vez.
La Zona de Clientes **nunca** queda inutilizable por falta de credenciales.

### AiModelRouter

No se usa el modelo caro para todo (`config/bmh.php → ai.routing`):

| Tarea | Modelo |
|---|---|
| Clasificación de intención | `fast` |
| Extracción estructurada | `fast` |
| Análisis de imagen | `vision` |
| Redacción final | `chat` |

### Fallback

`AI_FALLBACK_ENABLED=false` por defecto. Cambiar de proveedor cuesta plata: no
puede pasar solo.

---

## 3. MockAiProvider

No es un stub que devuelve texto de relleno. Interpreta con heurísticas reales:

- **Intención**: precio, recompra, equivalencia, humano, disponibilidad, identificación.
- **Rubro**: 35 palabras del oficio → rubro del catálogo ("burro" → MOTORES DE
  ARRANQUE, "ruleman" → RODAMIENTOS).
- **Atributos**: `12v`, `75a`, `28 mm`, `9 estrías`, `diámetro 88.8`, y pares
  `Etiqueta: valor` resueltos contra el diccionario de los 74 slots.
- **Marcas**: 18 marcas presentes en el catálogo.
- **Códigos**: alfanuméricos y numéricos.

En visión es **honesto**: sin modelo real no puede mirar la foto, así que
declara confianza baja (0.28–0.55, siempre por debajo del umbral de "alta") y no
inventa un tipo de pieza. Eso hace que el escenario "foto insuficiente" sea
demostrable de verdad.

---

## 4. Data provenance

Cada dato lleva de dónde salió:

```json
{ "value": "28 mm", "source": "producto_caracteristica", "source_id": 8291, "confidence": 1, "factual": true }
{ "value": "posible rotor", "source": "ai_vision", "confidence": 0.78, "factual": false }
```

`Provenance::isFactual()` distingue lo que se puede **afirmar** de lo que hay que
**matizar**. En la UI se traduce a los chips `CONFIRMADO` / `INFERIDO` del panel
de contexto: el cliente ve qué sabe el sistema y qué está suponiendo.

---

## 5. Memoria

Tres estados:

| Estado | Origen |
|---|---|
| `confirmed` | Lo dijo el cliente, o lo confirma la base |
| `inferred` | Lo dedujo visión o la interpretación de texto |
| `unknown` | Se preguntó y no hubo respuesta útil |

**Una inferencia nunca pisa un hecho confirmado.** Si el cliente dijo "es Bosch"
y una foto sugiere Valeo, `absorbImageAnalysis()` devuelve un **conflicto** en
vez de sobrescribir, y el asistente pregunta cuál corresponde.

Cubierto por `test_una_inferencia_no_pisa_un_dato_confirmado` y
`test_una_foto_que_contradice_lo_confirmado_expone_el_conflicto`.

La memoria se reconstruye en `SearchQuery` en cada turno: "es Bosch" dicho hace
tres mensajes sigue filtrando ahora.

---

## 6. Tool calling

10 tools (`ToolRegistry`). El modelo sólo puede **nombrar** una y pasarle
argumentos; Laravel valida, autoriza, consulta y devuelve JSON.

```
search_products · search_by_code · search_by_equivalence · get_product
compare_products · get_customer_price · get_customer_order_history
list_categories · check_availability · request_human_assistance
```

El `CustomerAccount` se inyecta al construir el registro, **no viaja como
argumento**: el modelo no puede pedir datos de otro cliente ni intentándolo.

`sanitizeAttributes()` descarta cualquier clave que no esté en el diccionario.

**En ningún escenario se ejecuta SQL producido por un modelo.**

---

## 7. Structured output

La extracción usa JSON Schema (`responseSchema` en Gemini, `json_schema` en
OpenAI) y **se valida igual** en `sanitizeInterpretation()`:

- `intent` tiene que ser un string del enum, si no → `product_identification`;
- `extracted_attributes` sólo acepta pares string/escalar;
- `confidence` se acota a 0..1.

Sin esto, un modelo desalineado que devuelve un array donde va un string tumba
el turno con "Array to string conversion". Pasó durante el desarrollo; ahora hay
un test (`test_un_json_malformado_del_modelo_no_rompe_el_turno`).

---

## 8. Confianza

Umbral único en `config/bmh.php`:

| Banda | Rango | Qué puede decir |
|---|---|---|
| `very_high` | ≥ 0.90 | "Encontré esta pieza." |
| `high` | 0.75–0.89 | "Encontré una coincidencia muy probable." |
| `ambiguous` | 0.50–0.74 | Pedir otro dato. |
| `low` | < 0.50 | "No tengo información suficiente." |

Techos deliberados en `Candidate::confidence()`:

- con **código exacto** → hasta 0.99;
- con código normalizado o equivalencia → hasta 0.89;
- **sin código** → tope 0.74.

Una coincidencia por atributos o por foto **nunca** llega a "muy alta". Sin un
código, el sistema no afirma una pieza.

Al cliente no se le muestra el número, sólo la etiqueta.

---

## 9. Visión

1. La imagen se optimiza (1280 px, calidad 82, EXIF removido).
2. Se pide **describir**, no identificar. El prompt lo dice explícitamente.
3. La salida entra como inferencia y alimenta filtros.
4. **La base confirma.**

Un código legible por OCR vale mucho más que un parecido de forma: entra con
confianza mínima 0.6 y dispara búsqueda por código, que tiene autoridad máxima
en el ranking.

### Comparación visual (preparado, no activado)

La arquitectura soporta el enfoque en embudo: 5.054 → búsqueda estructurada → 20
→ filtros técnicos → 5 → recién ahí comparación multimodal contra las imágenes de
los candidatos. Reduce costo, latencia y alucinaciones. No se activó porque con
`MockAiProvider` no aporta y con un proveedor real conviene medir primero.

---

## 10. Control de costos

Por conversación se acumulan tokens de entrada, de salida e imágenes analizadas
(`ai_conversations`). Cada turno queda en `ai_audit_logs` con proveedor, modelo,
versión de prompt y latencia.

Techos en `config/bmh.php → ai.limits`. Rate limit de 30 req/min por cliente.

Una foto de 12 MB nunca se manda tal cual.

---

## 11. Prompt versionado

`resources/prompts/bmh-sales-advisor/v1.md`. La versión se registra en cada
mensaje y en la auditoría (`prompt_version`), así se puede comparar rendimiento
entre versiones más adelante.

El prompt no está desperdigado en los Services.

---

## 12. Fallback sin IA

Si el proveedor falla o está apagado:

- la **búsqueda funciona igual** (no depende del modelo);
- el precio se calcula igual;
- la respuesta se arma con una plantilla determinística sobre los mismos datos;
- el cliente ve una respuesta útil, no un error.

Cubierto por `test_si_el_proveedor_falla_la_zona_de_clientes_sigue_respondiendo`:
con un proveedor que lanza excepción en todos sus métodos, la consulta por código
`1833` sigue devolviendo el producto correcto.

**La IA es un potenciador, no un single point of failure.**

---

## 13. Feedback y aprendizaje

Después de una identificación, "¿Era este?" Sí/No → `ai_feedback`.

Con el tiempo esto arma un dataset (foto + consulta + producto finalmente
vendido). **No hay auto-training.** Primero recolectar, limpiar, anonimizar,
revisar y versionar. Hoy sólo se almacena feedback estructurado.
