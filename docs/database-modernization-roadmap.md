# BMH — Roadmap de modernización de la base

**Nada de esto se ejecutó.** La demo no toca el esquema legacy. Este documento
es el plan que sale del audit, para que la migración sea una decisión informada
y no una urgencia.

---

## Principio

La base **no está rota**: está sub-documentada. Tiene un modelo de atributos
razonable escondido detrás de una convención posicional y un grafo de piezas que
nadie explota. La modernización debe **preservar** eso, no barrerlo.

Cada fase deja el sistema funcionando. Sin big bang.

---

## Fase A — Capa semántica sin tocar la legacy ✅ hecha

Es lo que entrega esta demo.

- `LegacyAttributeMap`: los 74 slots documentados con clave, etiqueta, tipo y unidad.
- `CatalogSemanticLayer`: filas legacy → `ProductView`.
- Repositorios con la legacy en **sólo lectura**.
- `catalog_search_documents`: índice materializado en base propia.
- `bmh:data-audit`: los problemas quedan medidos y reproducibles.

**Resultado:** se puede construir sobre la base sin cambiarla. El riesgo de las
fases siguientes baja mucho, porque el comportamiento correcto ya está fijado por
tests.

---

## Fase B — Higiene sin cambios de esquema

Cambios de **datos**, no de estructura. Reversibles.

| Acción | Impacto |
|---|---|
| Resolver los 26 productos sin precio | Habilita cotización |
| Decidir qué pasa con `aumento = 10` (1.024 productos) | **Económico directo** |
| Identificar qué es `columna_3` (362 valores sin etiqueta) | Recupera un atributo |
| Cargar marca en los 518 que no la tienen | Mejora la desambiguación |
| Reponer las 240 imágenes faltantes | Mejora la card y la comparación visual |
| Revisar los 6 `RP*` duplicados entre rubro 39 y 49 | Elimina ambigüedad |
| Limpiar los 3.737 placeholders `-` | Simplifica el semantic layer |

**Prerrequisito:** ninguno. Se puede hacer desde el admin actual.

**Cómo medir:** `bmh:data-audit` antes y después.

---

## Fase C — Normalización incremental

Recién acá se toca el esquema, y **agregando**, nunca quitando.

### C.1 Índices sobre la legacy

```sql
ALTER TABLE productos ADD INDEX idx_codigo (codigo);
ALTER TABLE productos ADD INDEX idx_marca (marca(64));
ALTER TABLE productos ADD INDEX idx_categoria_estado (categoria_id, estado);
```

Sin riesgo, mejora también el sitio actual.

### C.2 Columna de código normalizado

```sql
ALTER TABLE productos
  ADD COLUMN codigo_normalizado VARCHAR(128)
    GENERATED ALWAYS AS (UPPER(REPLACE(REPLACE(REPLACE(codigo,' ',''),'-',''),'.',''))) STORED,
  ADD INDEX idx_codigo_norm (codigo_normalizado);
```

Elimina el scan que hoy hace el repositorio.

### C.3 Migrar `columna_N` al EAV moderno

Ya existe la estructura correcta (`caracteristicas` /
`producto_caracteristica`). El diccionario de 74 slots es el mapa de migración:

```
para cada slot con etiqueta en la categoría:
    característica = upsert(caracteristicas, etiqueta_del_slot)
    upsert(producto_caracteristica, producto, característica, valor)
```

Se puede correr **en paralelo**: escribir en el EAV sin borrar las columnas, y
que el semantic layer lea de las dos fuentes y compare. Cuando no haya
discrepancias durante N semanas, se corta la lectura de `columna_N`.

**Importante:** hay que resolver primero qué es `columna_3`, o se pierden 362
valores.

### C.4 Semántica de stock

Definir qué significa `productos.stock` y, si va a ser cantidad, empezar a
mantenerlo. Recién ahí `BMH_STOCK_SEMANTICS_VERIFIED=true`.

Es de las cosas de mayor impacto comercial: hoy el asesor **no puede** responder
"¿tenés stock?", que es una de las preguntas más frecuentes.

### C.5 Unicidad de código

Cuando la Fase B resuelva los duplicados:

```sql
ALTER TABLE productos ADD UNIQUE INDEX uq_codigo (codigo);
```

Ojo: si los `RP*` que cruzan rubros son legítimos, la unicidad debería ser
`(codigo, categoria_id)`, no `codigo` solo.

---

## Fase D — Deprecación controlada

Sólo cuando C esté estable.

1. Marcar `columna_1..78`, `precioN`, `descripcion` como deprecadas en el código.
2. Verificar por logs que nada las lee.
3. Dump completo de respaldo.
4. `ALTER TABLE productos DROP COLUMN …` en tandas chicas.

Se pasa de una tabla de **101 columnas** a una de ~25.

Candidatas a eliminar también: `medidas`, `medida_producto`, `dimensiones`,
`subcategorias`, `repuestos`, `producto_repuesto`, `usos`, `producto_uso`,
`equivalencias` — todas vacías o con menos de 15 filas.

---

## Orden por relación impacto/riesgo

| # | Acción | Impacto | Riesgo |
|---|---|---|---|
| 1 | Decidir `aumento = 10` | **Alto** (económico) | Nulo |
| 2 | Índices (C.1) | Alto | Nulo |
| 3 | Semántica de stock (C.4) | **Alto** (comercial) | Bajo |
| 4 | Identificar `columna_3` | Medio | Nulo |
| 5 | Precios faltantes | Medio | Nulo |
| 6 | Código normalizado (C.2) | Medio | Bajo |
| 7 | Duplicados | Medio | Bajo |
| 8 | Migrar EAV (C.3) | Alto | **Medio** |
| 9 | Drop de columnas (D) | Bajo | **Alto** |

Los primeros siete no requieren tocar el esquema de forma destructiva y resuelven
casi todo lo que hoy limita al asesor.

---

## Qué NO hacer

- **No** normalizar destructivamente ahora. La demo no lo necesita y el riesgo no
  se justifica.
- **No** meter embeddings en columnas legacy. Van en `catalog_search_documents`.
- **No** borrar `columna_N` antes de migrar los valores y verificar durante
  semanas.
- **No** tocar el esquema de producción para hacer funcionar el chatbot. No hizo
  falta y no debería hacer falta.
