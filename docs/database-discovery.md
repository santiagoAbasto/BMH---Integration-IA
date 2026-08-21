# BMH — Database Discovery

**Fuente:** `bmhbobinajes_bmh.sql` (phpMyAdmin 5.2.3, servidor origen MariaDB 11.4.12).
**Copia local de trabajo:** base `bmh_legacy` en MySQL 9.6 (127.0.0.1:3306). Importada sin errores.
**Fecha del dump:** 2026-08-07 23:22.

> Esta base es **solo lectura** para el asistente. Nada de lo que se documenta acá se
> modifica desde el nuevo módulo. Ver `docs/security.md`.

---

## 1. Inventario de tablas (37)

| Tabla | Filas | Rol en el nuevo sistema |
|---|---:|---|
| `productos` | **5.054** | Catálogo. Núcleo. Legacy EAV posicional (`columna_1..78`). |
| `categorias` | 25 | Rubros. **Además: diccionario de etiquetas de `columna_N`.** |
| `caracteristicas` | 782 | Diccionario EAV moderno. |
| `producto_caracteristica` | 13.218 | Valores EAV modernos (referencias cruzadas / equivalencias). |
| `categoria_caracteristica` | 1.541 | Qué características aplican a cada rubro. |
| `imagenes` | 5.223 | 5.214 de sector `producto`; 4.810 productos con imagen. |
| `users` | 437 | Clientes (todos `rol='cliente'`). Condición comercial. |
| `pedidos` | 302 | Historial de pedidos. |
| `pedido_producto` | 2.913 | Líneas históricas con precio unitario y descontado. |
| `bonificaciones` | 7 | Escala de bonificación por volumen. |
| `impuestos` | 1 | IVA = 21 %. |
| `equivalencias` | 13 | Casi vacía. **No es la fuente real de equivalencias.** |
| `medidas` | 2 | Prácticamente vacía. |
| `medida_producto` | 0 | Vacía. |
| `dimensiones` | 0 | Vacía. |
| `subcategorias` | 0 | Vacía. |
| `repuestos` / `producto_repuesto` | 1 / 0 | Vacías en la práctica. |
| `usos` / `producto_uso` | 3 / 0 | Vacías en la práctica. |
| resto (`admins`, `anuncios`, `contacto`, `descargas`, `mails`, `metadatos`, `newsletter`, `nosotros_contenido`, `novedades`, `xml`, `zonas_postales`, `carrito_informacion`, `dimension_pedido`, `failed_jobs`, `migrations`, `password_reset_tokens`, `personal_access_tokens`) | — | CMS / operativo del sitio actual. Fuera del alcance del asesor. |

Los números del brief (4.797 productos) quedaron **desactualizados**: hoy son **5.054**.

---

## 2. El hallazgo central: `columna_N` es un EAV posicional

La estructura `columna_1 … columna_78` **no es basura ni columnas sueltas**. Es un EAV
por posición con el diccionario guardado en otra tabla:

```
categorias.columna_N  →  ETIQUETA del atributo para ese rubro
productos.columna_N   →  VALOR de ese atributo para ese producto
```

Confirmado en `resources/views/frontend/producto.blade.php:557-570`, que recorre
`$i = 1..78` y sólo imprime el par cuando **ambos** lados están cargados.

### 2.1 El slot es globalmente consistente

Cada slot tiene **una sola etiqueta** en todas las categorías que lo usan. No hay
colisiones semánticas (salvo `columna_51`/`columna_70` = `EQUIVALENCIA` y
`columna_25`/`Columna_39` = `TERMINALES`, que son duplicaciones benignas).

Eso permite construir **un diccionario global de 74 atributos** en vez de 25 mapeos
por rubro. Es la base del `LegacyAttributeMap`.

| Slot | Atributo | Rubros que lo usan |
|---|---|---:|
| 1 | VOLTAJE | 18 |
| 2 | DIAMETRO | 6 |
| 4 | MASA POLAR | 1 |
| 5 | GIRO | 2 |
| 6 | TIPO | 5 |
| 7 | CIRCUITO TIPO | 3 |
| 8 | LARGO | 2 |
| 9 | ANCHO | 5 |
| 10 | AMPERES | 9 |
| 12 | ARO COLECTOR | 3 |
| 13 | DIENTES | 4 |
| 14 | ESTRIAS | 4 |
| 15 | RANURAS | 3 |
| 16 | BUJE LADO COLECTOR | 1 |
| 17 | BUJE LADO BENDIX | 1 |
| 18 | DIAMETRO FIJACION | 2 |
| 21 | RODAMIENTO COLECTOR | 1 |
| 22 | RODAMIENTO POLEA | 1 |
| 24 | DISTANCIA ENTRE ORIFICIOS DE MONTAJE | 7 |
| 25 | TERMINALES | 3 |
| 26 | ROSCAS | 2 |
| 27 | APLICACIÓN | 9 |
| 28 | ALTURA | 3 |
| 29 | TIPO SERIE | 2 |
| 30 | CIRCUITO | 1 |
| 31 | DISTANCIA | 1 |
| 32 | PINES | 3 |
| 33 | TOLERANCIA | 2 |
| 34 | BLINDAJE | 2 |
| 35 | DIAMETRO INTERNO | 8 |
| 36 | DIAMETRO EXTERNO | 9 |
| 37 | TERMINACION | 1 |
| 38 | PESO | 2 |
| 39 | TERMINALES | 1 |
| 40 | CANTIDAD | 4 |
| 41 | FUNCION | 3 |
| 42 | EQUIVALENCIA UNIPOINT | 1 |
| 43 | EQUIVALENCIA TAMATEL | 3 |
| 44 | EQUIVALENCIA NOSSO | 7 |
| 45 | FICHA | 4 |
| 46 | ANCHO DE BANDA | 2 |
| 47 | DIAMETRO VENTILADOR | 2 |
| 48 | LARGO VENTILADOR | 2 |
| 49 | LARGO TOTAL | 8 |
| 50 | MARCA | 5 |
| 51 | EQUIVALENCIA | 7 |
| 53 | CANTIDAD DE DIODOS | 4 |
| 54 | TIPO DE DIODOS | 2 |
| 55 | AMPERAJE DE DIODOS | 2 |
| 56 | TERMINALES CANTIDAD | 3 |
| 57 | TERMINALES DESCRIPCION | 2 |
| 58 | DIODO ADICIONAL | 2 |
| 59 | DIAMETRO EXTERNO PIÑON | 2 |
| 60 | CODIGO ZEN | 5 |
| 61 | CODIGO GV | 2 |
| 62 | CODIGO PH | 5 |
| 63 | CODIGO DIPRA | 2 |
| 64 | MEDIDAS | 3 |
| 65 | RODAMIENTO LADO COLECTOR | 2 |
| 66 | RODAMIENTO LADO POLEA | 2 |
| 67 | CODIGO ZM | 2 |
| 68 | DIAMETRO DE ORIFICIOS DE MONTAJE | 2 |
| 69 | CARBON | 3 |
| 70 | EQUIVALENCIA | 3 |
| 71 | PARS | 1 |
| 72 | EQUIVALENCIA BMH | 6 |
| 73 | EQUIVALENCIA NF | 1 |
| 74 | SALIDAS | 2 |
| 75 | EQUIVALENCIA INDUCIDO NUEVO | 1 |
| 76 | EQUIVALENCIA ESTATOR NUEVO | 1 |
| 77 | EQUIVALENCIA ROTOR NUEVO | 1 |
| 78 | EQUIVALENCIA SOLENOIDE NUEVO | 1 |

Slots `3`, `11`, `19`, `20`, `23`, `52` no tienen etiqueta en ninguna categoría: si
`productos.columna_3` tiene datos (362 filas la tienen), **son valores huérfanos sin
etiqueta** y no se pueden interpretar. Ver `docs/data-quality-report.md`.

### 2.2 Tipado derivado

Del nombre del atributo se puede derivar un tipo semántico y usarlo para búsqueda:

- **dimension** (mm): DIAMETRO, DIAMETRO INTERNO/EXTERNO, LARGO, LARGO TOTAL, ANCHO,
  ALTURA, DISTANCIA*, ANCHO DE BANDA, DIAMETRO * …
- **electrical**: VOLTAJE, AMPERES, AMPERAJE DE DIODOS
- **count**: DIENTES, ESTRIAS, RANURAS, PINES, TERMINALES*, CANTIDAD*, SALIDAS, CANALES
- **cross_reference**: EQUIVALENCIA*, CODIGO ZEN/GV/PH/DIPRA/ZM, FICHA
- **text**: TIPO, GIRO, FUNCION, APLICACIÓN, BLINDAJE, TERMINACION, MARCA, MEDIDAS

Esto lo implementa `App\Domain\Catalog\LegacyAttributeMap`.

---

## 3. El segundo EAV: `producto_caracteristica`

Además del EAV posicional existe un EAV clásico y **normalizado**, mucho mejor hecho:

```
caracteristicas(id, nombre)
categoria_caracteristica(categoria_id, caracteristica_id)
producto_caracteristica(producto_id, caracteristica_id, valor)
```

13.218 valores. Pero **no guarda atributos técnicos**: guarda **referencias cruzadas a
otras piezas y equivalencias de proveedor**.

| Característica | Valores |
|---|---:|
| ESCOBILLA | 914 |
| Nº ORIGINAL | 704 |
| BUJE LADO BENDIX | 563 |
| BUJE LADO COLECTOR | 562 |
| PORTAESCOBILLA | 556 |
| BUJE INTERMEDIO | 553 |
| EQUIVALENCIA ORO | 531 |
| EQUIVALENCIA PORTAFICH | 500 |
| REGULADOR DE VOLTAJE | 380 |
| PLAQUETA RECTIFICADORA | 379 |
| ALTERNADOR | 371 |
| ESTATOR | 358 |
| COLECTOR | 356 |
| INDUCIDO NUEVO | 224 |
| EQUIVALENCIA PH | 217 |
| EQUIVALENCIA BOSCH 1 | 152 |
| ROTOR | 141 |

Es decir: dado un rotor, la base sabe **qué escobilla, qué colector, qué regulador y qué
plaqueta le corresponden**, y con qué códigos de la competencia equivale.

**Esto es oro para el asesor.** Es lo que permite responder "necesito la escobilla de
este alternador" y "tenés el equivalente del Bosch XXXX".

Ojo: gran parte de los valores son `-` (placeholder de "no aplica"). Hay que filtrarlos.

### 3.1 `equivalencias` (la tabla) es una pista falsa

Sólo 13 filas. Las equivalencias reales viven en:
1. `producto_caracteristica` con característica `EQUIVALENCIA *` / `Nº ORIGINAL`
2. `productos.columna_42..44, 51, 60..63, 67, 70, 72..78`

El `EquivalenceRepository` lee de las dos fuentes, no de `equivalencias`.

---

## 4. Campos de `productos` — semántica confirmada

| Campo | Verificado | Semántica real |
|---|---|---|
| `codigo` | ✅ | Código BMH. **No es único** (11 colisiones). |
| `nombre` | ✅ | Descripción corta. Siempre presente. Contiene rubro + marca + modelo + medidas. |
| `descripcion` | ⚠️ | **Vacía en 5.035 de 5.054**. Inútil. |
| `precio` | ✅ | Precio de lista **sin IVA**, ARS. |
| `precioN` | ❌ | **100 % NULL.** Columna muerta. |
| `iva` | ⚠️ | `1` en las 5.054 filas. Sin poder discriminante. La lógica que lo usaba está comentada (`Producto::precio_cliente`). |
| `descuento` | ⚠️ | Sólo `NULL` (2.964) o `'0'` (2.090). **Nunca hay descuento de producto.** |
| `aumento` | ⚠️ | `0` (4.030) o `10` (1.024). **Se guarda pero NO se aplica** (código comentado). Ver pricing. |
| `estado` | ✅ | `1` = NUEVO (4.067), `2` = RECONSTRUIDO (984), `0` = oculto (2), NULL (1). Confirmado en `ProductoController::mapEstado` y en las vistas. **No es stock.** |
| `stock` | ❌ | **`1` en las 5.054 filas.** Un solo valor distinto. No es cantidad. Ver §5. |
| `marca` | ✅ | Presente en 4.536. |
| `modelo` | ✅ | Presente en 2.546. Suele ser aplicación vehicular ("RENAULT 9 - TRAFIC"). |
| `categoria_id` | ✅ | FK a `categorias`. |
| `diametroInterno/Externo`, `anchoBanda`, `tolerancia`, `blindaje` | ⚠️ | Campos dedicados de RODAMIENTOS, duplicados por `columna_33..36`. |
| `orden`, `destacada`, `ficha`, `caracteristicas` | — | Presentación/CMS. |

---

## 5. `stock` — semántica NO verificada

Los 5.054 productos tienen `stock = 1`. Las lecturas posibles:

1. booleano "disponible", nunca puesto en 0;
2. default de la migración (`DEFAULT 1`) que nadie mantuvo;
3. campo heredado de otro sistema.

No hay ninguna consulta en el código que filtre por `stock`, ni pantalla de admin que
lo edite. La hipótesis más fuerte es **(2): default nunca mantenido**.

**Decisión de implementación:** `InventoryService` devuelve
`availability = 'unknown'` con `source = 'stock_semantics_unverified'`. El asistente
tiene **prohibido afirmar disponibilidad**. Cuando el cliente pregunta por stock,
responde que lo confirma un asesor y ofrece handoff.

Para revertir esto cuando BMH confirme la semántica: un solo lugar,
`config/bmh.php → inventory.semantics_verified`.

---

## 6. `users` — el cliente

| Campo | Valores | Uso |
|---|---|---|
| `rol` | `cliente` (437/437) | Sin roles mixtos en esta tabla; los admins están en `admins`. |
| `habilitado` | 1 (390) / 0 (47) | Gate de acceso a Zona Clientes. |
| `descuento` | 0 (221), 6 (146), 9 (37), 14 (15), 16 (7), 18 (6), 10 (2), 12/28/50 (1 c/u) | **Descuento comercial del cliente, en %.** Se aplica. |
| `reventa` | NULL (351) + 21..100 | % de markup sugerido de reventa. Se muestra sólo al propio cliente. |
| `codigo` | — | Código de cliente en el ERP. |
| `vendedor_id` | — | Vendedor asignado. Útil para el handoff. |
| `password` | bcrypt | **Nunca sale del backend.** |
| `dni`, `direccion`, `celular`, `email`, `name` | — | PII. **No se envía al LLM.** Ver `docs/security.md`. |

---

## 7. Historial de pedidos

`pedidos` (302) → `pedido_producto` (2.913).

`pedido_producto` conserva, por línea:
`cantidad`, `precio_unitario`, `precio_descontado`, `descuento_producto`.

Es el **registro histórico de cómo se calculó el precio en su momento** y sirve para
validar el `PricingEngine` contra la realidad. `pedidos` guarda además
`descuento_cliente` y `bonificacion` del momento de la compra.

Los 302 pedidos tienen `cliente_id`, así que el historial por cliente es explotable al
100 %. 295 quedaron `Pendiente` y 7 `Procesado` (el estado de orden no se mantiene).

---

## 8. Imágenes

`imagenes.sector='producto'` → 5.214 filas, **4.810 productos distintos** (95 %).
`tipo`: `portada` (4.916) e `imagen` (307).
`path` guarda **sólo el nombre de archivo** (`media_66cc9fb17b7d5.png`); el archivo vive
en `public/imagenes/`.

⚠️ En el checkout local hay **4.414 archivos** en `public/imagenes/` contra 5.223
registros: hay imágenes referenciadas que no están en el filesystem local. El
`ProductImageService` verifica existencia y degrada a placeholder.

---

## 9. Índices

El dump define PK e índices en las tablas Laravel estándar, pero **no hay índice sobre
`productos.codigo`, `productos.marca` ni `productos.nombre`**, que son exactamente las
columnas por las que el asesor va a buscar.

Como no tocamos la base legacy, el índice de búsqueda se materializa en
`catalog_search_documents` (base propia), con FULLTEXT. Ver `docs/catalog-search.md`.

---

## 10. Conclusión

La base **no está rota**: está *sub-documentada*. Tiene un modelo de atributos
razonable escondido detrás de una convención posicional, y un grafo de piezas
relacionadas (`producto_caracteristica`) que nadie está explotando.

El trabajo del asesor no es arreglar la base. Es **leerla bien**.
