# BMH — Búsqueda del catálogo

---

## 1. Por qué no hay vector store

El brief lo pedía explícitamente: no agregar tecnología por moda.

**5.054 productos.** Números medidos en esta máquina:

| Operación | Tiempo |
|---|---|
| Búsqueda por código exacto | ~8 ms |
| Búsqueda híbrida texto + marca | ~35 ms |
| Hidratar 40 productos (3 queries, sin N+1) | ~50 ms |
| Sync completo del catálogo | 2,2 s |

Un Pinecone/Qdrant agregaría una dependencia externa, un costo mensual, un punto
de falla y un problema de sincronización **para resolver algo que MySQL hace en
milisegundos**.

`AiProviderInterface::embed()` existe y `MockAiProvider` devuelve un vector
determinístico, así que el pipeline semántico se puede testear. Pero
`SEMANTIC_SEARCH_ENABLED=false` por defecto.

**Cuándo reconsiderarlo:** si el catálogo pasa de ~50.000 artículos, o si se
demuestra con consultas reales fallidas que la búsqueda léxica no alcanza. Hasta
entonces, no.

---

## 2. QueryRouter

No todo necesita IA.

| Estrategia | Cuándo |
|---|---|
| `EXACT` | Hay código, o el texto *es* un código |
| `STRUCTURED` | Hay filtros de atributos, sin texto libre |
| `SEMANTIC` | Texto libre sin código ni filtros |
| `VISION` / `HYBRID` | Hay imagen |
| `HISTORY` | "el que compro siempre" |

`looksLikeCode()` acepta `REG40016`, `912345`, `RP 033155`; rechaza
`necesito un rotor`.

`extractCode()` saca un código embebido en una frase. Cuidado con el español:
un patrón ingenuo `[A-Z]{2,4}\s?\d{4,8}` convierte **"el 912345"** en el código
`"el 912345"`, porque "el" matchea el prefijo alfabético. Por eso no se admite
espacio entre letras y dígitos, y hay una lista de artículos y preposiciones que
se descartan. Está cubierto por test.

---

## 3. Las siete vías

`HybridProductSearchService` combina, en orden de autoridad:

1. **Código exacto** — `productos.codigo`
2. **Código normalizado** — sin espacios, guiones, puntos ni barras
3. **Equivalencias** — `producto_caracteristica` + slots cross_reference
4. **Código parcial** — fragmento
5. **Atributos** — filtros técnicos, con tolerancia numérica
6. **Palabras clave** — nombre, marca, modelo, código
7. **Historial del cliente**

El resultado es una **unión**: un producto que aparece por varias vías acumula
señales, que es exactamente lo que queremos.

### 3.1 Descartar contradicciones

La unión sola tiene un problema: agregar información no reduce nada. El cliente
contesta "largo total 152" y le siguen apareciendo 24 opciones.

Por eso, después de la unión, `discardContradictions()` aplica una regla
**asimétrica**:

- si el producto tiene el atributo y **no coincide** → se descarta;
- si el producto **no tiene el atributo cargado** → se conserva.

Lo segundo importa: 518 productos no tienen marca y muchos tienen atributos
vacíos. Descartar por dato faltante haría que la respuesta correcta desaparezca
por un agujero del catálogo, que es peor que mostrarla más abajo en el ranking.

Si el filtro deja el conjunto vacío, se devuelve el pool sin filtrar: mejor
mostrar candidatos ordenados que decir "no existe nada".

Efecto real medido: **24 → 6 → 2** en tres turnos.

Un match exacto de código **no** se filtra por atributos. El código manda.

---

## 4. Ranking multifactor

Pesos en `config/bmh.php → ranking.weights`:

| Señal | Peso |
|---|---:|
| `exact_code` | **100** |
| `normalized_code` | 80 |
| `equivalence` | 60 |
| `partial_code` | 35 |
| `attribute_match` | 12 |
| `brand_match` | 10 |
| `model_match` | 8 |
| `customer_history` | 7 |
| `category_match` | 6 |
| `text_similarity` | 5 |
| `semantic_similarity` | 4 |
| `vision_similarity` | **3** |
| `popularity` | 2 |

**La similitud visual nunca le gana a un código exacto.** No es una convención:
`exact_code` está más de 30× por encima de `vision_similarity`, así que ninguna
combinación de señales blandas puede darlo vuelta. Hay dos tests que lo fijan.

---

## 5. La pregunta más inteligente

`CandidateDisambiguationService` calcula, sobre los candidatos que **realmente
hay**, qué dato partiría mejor el conjunto.

Para cada atributo:

```
ganancia = entropía_normalizada(distribución de valores) × cobertura
```

- **Entropía normalizada** (Shannon, 0..1): 1.0 = parte los candidatos en grupos
  parejos; 0.0 = todos caen en el mismo valor.
- **Cobertura**: qué proporción de candidatos tiene el atributo cargado. Un
  atributo que sólo tiene 2 de 20 no sirve aunque los distinga.

Reglas:

- ≤ 3 candidatos → no se pregunta, se muestra;
- el primero con confianza `very_high` → no se pregunta;
- ganancia < 0.15 → no se pregunta;
- `never_ask` (PESO, MEDIDAS, TERMINACION) → nunca, el cliente no los sabe.

Y compiten en igualdad marca y modelo, que no son del EAV pero son lo que el
cliente suele saber.

**Ejemplo real** (24 rotores Bosch de 12v):

```
Modelo        gain 0.9952   ← se pregunta este
Largo total   gain 0.9552
Diámetro      gain 0.9278
Amperes       gain 0.8736
```

Las opciones calculadas se ofrecen como quick-replies en el chat. Cuando el
cliente toca una, se envía `Etiqueta: valor`, que el extractor resuelve contra el
diccionario de los 74 slots.

---

## 6. Índice materializado

La base legacy **no tiene índice** sobre `codigo`, `marca` ni `nombre` — que son
exactamente las columnas por las que se busca. Y no la tocamos.

Solución: `catalog_search_documents` en la base propia, con FULLTEXT sobre
`searchable_text` y `name`, más índices en `code`, `normalized_code`, `brand` y
`category_id`.

```text
CODIGO: 1833
RUBRO: ROTORES
NOMBRE: ROTOR WAPSA BOSCH 24V 125x197
MARCA: WAPSA
CARACTERISTICAS:
  VOLTAJE: 24v
  DIAMETRO: 125 mm
  AMPERES: 75 A
  LARGO TOTAL: 197 mm
EQUIVALENCIAS:
  ...
```

`descripcion` no participa: está vacía en el 99,6 % de los productos.

### Sync idempotente

```bash
php artisan bmh:catalog-sync
```

Cada documento guarda un `content_hash`. Una corrida sobre un catálogo sin
cambios reescribe **cero** filas:

```
Productos leídos          5054
Documentos creados           0
Documentos actualizados      0
Sin cambios               5054
Duración                  2.2 s
```

Los productos que ya no están en la legacy salen del índice. Las anomalías
(sin precio, sin imagen, sin rubro, sin atributos ni marca) quedan en
`catalog_sync_runs.report`.

---

## 7. Códigos duplicados

`codigo` **no es único**. El repositorio devuelve siempre una **lista**, nunca un
producto suelto, y `DuplicateProductResolver` decide:

- un solo publicable → se resuelve solo;
- varios → **no elige el primero**: calcula qué los distingue y el asesor
  pregunta. Si difieren en rubro, esa es la primera pregunta.

---

## 8. Rendimiento

- Hidratación en batch: 3 queries fijas sin importar cuántos productos
  (test que falla si supera 4).
- Categorías y duplicados cacheados 1 h.
- Imágenes: existencia cacheada por request.
- Candidatos topeados a 24; se presentan 3.
