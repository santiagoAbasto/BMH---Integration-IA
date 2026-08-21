# BMH — Discovery

Fase 0. Qué se encontró antes de escribir una línea del asesor.

---

## 1. El repositorio no era un proyecto vacío

El brief planteaba construir sobre un dump. Lo que hay en el directorio es **el
sistema de producción de BMH**: Laravel 10.48, Blade + Alpine + Vite,
`codersfree/shoppingcart`, MercadoPago, PhpSpreadsheet.

Eso cambió el enfoque de entrada. No hubo que *adivinar* cómo BMH calcula
precios: hubo que **leer el código que lo hace**. La fórmula de
[pricing-rules.md](pricing-rules.md) sale de `Producto::precio_unitario_descontado()`,
no de una hipótesis.

Estructura relevante:

| Qué | Dónde |
|---|---|
| Modelos legacy | `app/Models` (32) |
| Controllers | `app/Http/Controllers` (26) |
| Zona de Clientes | `routes/web.php`, middleware `cliente` |
| Cálculo de precios | `app/Models/Producto.php`, `Carrito.php`, `Pedido.php` |
| Vistas | `resources/views/frontend`, `backend` |
| Colores de marca | `public/css/*.css` |

---

## 2. La base

Dump de MariaDB 11.4 del 2026-08-07. Importado sin errores en MySQL 9.6 local
como `bmh_legacy`.

Los números del brief estaban desactualizados:

| | Brief | Real |
|---|---:|---:|
| Productos | 4.797 | **5.054** |
| Categorías | 25 | 25 ✓ |
| Clientes | 437 | 437 ✓ |
| Pedidos | ~300 | 302 ✓ |
| Líneas de pedido | ~2.900 | 2.913 ✓ |
| producto_caracteristica | >13.000 | 13.218 ✓ |
| Imágenes | >5.000 | 5.223 ✓ |

Detalle completo en [database-discovery.md](database-discovery.md).

---

## 3. El hallazgo que definió la arquitectura

`columna_1 … columna_78` no es basura desnormalizada. Es un **EAV posicional**:

```
categorias.columna_N  →  ETIQUETA del atributo
productos.columna_N   →  VALOR
```

Confirmado en `resources/views/frontend/producto.blade.php:557`, que recorre
`$i = 1..78` y sólo imprime cuando ambos lados están cargados.

Y hay algo mejor: **cada slot tiene una única etiqueta en las 25 categorías**.
El slot es globalmente consistente. Eso permitió un diccionario de 74 atributos
(`LegacyAttributeMap`) en vez de 25 mapeos por rubro.

Sin ese hallazgo, el asesor habría tenido que tratar `columna_49` como texto
opaco. Con él, sabe que es "LARGO TOTAL", que es una dimensión en mm, y puede
usarla para desambiguar.

---

## 4. El segundo hallazgo: hay un grafo de piezas

`producto_caracteristica` (13.218 filas) no guarda atributos técnicos: guarda
**qué pieza va con qué pieza**.

| Característica | Valores |
|---|---:|
| ESCOBILLA | 914 |
| Nº ORIGINAL | 704 |
| BUJE LADO BENDIX | 563 |
| PORTAESCOBILLA | 556 |
| EQUIVALENCIA ORO | 531 |
| REGULADOR DE VOLTAJE | 380 |
| COLECTOR | 356 |

Dado un rotor, la base **ya sabe** qué colector, qué escobilla y qué regulador le
corresponden, y con qué códigos de la competencia equivale. Nadie lo estaba
explotando.

Es lo que permite responder "necesito la escobilla de este alternador" y "tenés
el equivalente del Bosch XXXX".

⚠️ La tabla `equivalencias` (13 filas) es una pista falsa. Las equivalencias
reales están acá y en los slots cross_reference.

---

## 5. Identidad visual

El sitio (`bmhbobinajes.com.ar`) devuelve poco por HTTP: el logo es un PNG y no
hay custom properties en el CSS.

La fuente autoritativa terminó siendo **el CSS de producción de este mismo
repositorio**:

| Color | Apariciones | Uso real |
|---|---:|---|
| `#0098DA` | 49 | Azul institucional: títulos, links, acentos |
| `#8BC34A` / `#ABD430` | 9 | Verde de estado — marca los productos NUEVO |
| `#090909`, `#121212` | 10 | Textos oscuros |
| `#F1F1F1`, `#E5E5E5` | 8 | Superficies |

`#ABD430` aparece literalmente en `producto.blade.php` marcando "Nuevo", y
`#0098DA` marcando "Reconstruido". Se conservó esa semántica: el verde es estado
positivo, nunca acción.

Detalle en [design-system.md](design-system.md).

---

## 6. Zona de Clientes actual

- Login por `POST /user/login`. El formulario manda un único campo `input_type`
  que puede ser usuario o email; `LoginRequest::prepareForValidation()` decide
  cuál es.
- Guard `web` sobre `users`, middleware `cliente` (acepta `cliente` y `vendedor`).
- Rutas existentes: `productos-zona-clientes`, `historial`, `mis-datos`,
  `cliente-pedido`.
- El acceso exige `habilitado = 1` (390 de 437 clientes).

**Decisión:** reutilizar esa sesión. No se creó un segundo login. Lo único que se
agregó es un middleware gemelo que devuelve 401 JSON en vez de redirigir, porque
un redirect rompe un fetch.

---

## 7. Deuda técnica encontrada (y no tocada)

| Qué | Dónde |
|---|---|
| Lógica de precios comentada | `Producto::precio()` líneas 90-106 |
| `precio_unitario_descontado_format()` descarta el descuento de producto cuando el cliente tiene descuento | `Producto.php:148` |
| Bonificación calculada pero no restada del total | `Carrito::total_format()` |
| `productos.iva` sin uso | lógica comentada en `precio_cliente()` |
| `public/index.php` con rutas del hosting hardcodeadas | corregido para soportar ambos layouts |
| Credenciales de producción en el `.env` local | reapuntadas a la copia local |

**Nada de esto se "arregló" en el sistema existente.** El asesor replica el
comportamiento de producción y documenta las diferencias. Cambiarlas es decisión
de BMH.

---

## 8. Qué se decidió después del discovery

| Decisión | Por qué |
|---|---|
| Diccionario global de 74 slots | El slot es consistente entre categorías |
| Índice materializado propio | La legacy no tiene índice sobre código/marca/nombre |
| Sin vector store | 5.054 productos; MySQL responde en ~8 ms |
| Conexión legacy con GRANT SELECT | Barrera real, no una convención |
| Replicar la fórmula de producción | Cotizar distinto del carrito rompe la confianza |
| `stock` como no verificado | Un solo valor distinto en toda la tabla |
| Reutilizar el login | Ya existe y es de sesión |
