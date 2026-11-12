# Rediseño BMH — estado y pendientes

Rama `rediseno` (Laravel 13, PHP 8.4, osole/ui). Corte: 2026-11-12.
**No va a producción todavía.** Producción sigue en `new-products-schema` (Laravel 10, PHP 8.2).

---

## 1. Base de datos y backups

| Qué | Dónde | Para qué |
|---|---|---|
| Backup parcial **sin datos personales** | `database/backups/rediseno-2026-11-12-sin-datos-personales.sql` | Tablas que cambió el rediseño: `caracteristicas`, `caracteristica_categoria`, `caracteristica_producto`, `metadatos`, `seo_settings`, `contacto`, `migrations`. |
| Backup **completo** de la base local modificada | Fuera del repo: `C:\Users\Osole\dev\soporte\BMH-backups\bmh_legacy-rediseno-20261112-1649.sql.gz` | Restaurar todo tal cual quedó. |

> **Por qué el backup completo no está en GitHub:** el repo `santiagoAbasto/BMH---Integration-IA` es **público**. La base completa
> tiene DNI, teléfonos, emails, direcciones, pedidos y hashes de contraseña de los clientes. No se sube a un repo público.
> Si el repo pasa a privado se puede agregar, pero lo recomendable es guardarlo en un almacenamiento privado.

**La base se reconstruye sola desde la original:** con la base de producción (o la local de antes) alcanza con
`php artisan migrate`. Las migraciones nuevas son aditivas y la de características carga los datos
(`App\Support\Caracteristicas\MigracionCaracteristicas`, idempotente).

Migraciones nuevas del rediseño (todas aditivas, con guards):
- `2026_11_12_000001_add_oferta_to_productos` — `productos.oferta`.
- `2026_11_12_000002_add_seo_to_metadatos` — `metadatos.titulo` + tabla `seo_settings`.
- `2026_11_12_000003_create_contacto_mensajes_table` — `contacto_mensajes` + columnas de avisos en `contacto`.
- `2026_11_12_000010_normalizar_caracteristicas` — características relacionales (ver punto 2).

Para importar el backup parcial en una base que ya tenga el resto: `mysql -u USUARIO BASE < database/backups/rediseno-2026-11-12-sin-datos-personales.sql`
(reemplaza esas 7 tablas).

---

## 2. Características de productos — **a medio hacer** (lo más importante)

Pedido: sacar las 78 columnas sueltas (`categorias.columna_N` = nombre, `productos.columna_N` = valor) y el sistema viejo
(`categoria_caracteristica` / `producto_caracteristica`) y pasar a tablas relacionales, con muy buena UI/UX en el admin.

### Hecho (en `rediseno`)
- `bedf7d8` — Esquema y datos:
  - `caracteristicas`: `nombre` único, `orden` de 2 caracteres (aa, ab…), formato opcional `mayusculas` / `numerica` / `unidad`
    (VOLTAJE → `12V`, DIÁMETRO → `59.5MM`), utf8mb4.
  - `caracteristica_categoria` y `caracteristica_producto` (con `valor` hasta 255): FKs y PK por par.
  - Datos migrados y verificados: 157 características, 373 vínculos con categorías, 18.048 valores, **0 perdidos**.
    `-` = sin dato. EQUIVALENCIA estaba en dos columnas (51 y 70): quedó una sola.
  - `App\Support\Caracteristicas\FormatoCaracteristica` (normaliza valores) + tests.
- `2319974` — Modelos sobre las tablas nuevas (`Caracteristica`, `Categoria::caracteristicas()`, `Producto::caracteristicas()`)
  y un solo lugar para guardar valores (`SincronizarCaracteristicasProducto`).
- **Las columnas `columna_N` y las tablas viejas NO se borraron** (respaldo). El código de `rediseno` todavía las lee: el sitio
  funciona igual que antes.

### Trabajo en curso guardado en la rama `rediseno-wip-caracteristicas` (commit `e06a3ac`)
Se cortó a mitad de camino: **no está terminado ni probado en conjunto. No mergear sin completar.**
- Catálogo, filtros, búsqueda, equivalencias, parecidos y capa de IA leyendo las tablas nuevas.
- Se sacan `NormalizadorUnidades`, `UnidadesProducto`, `ProductCaracteristica` y sus tests.
- Actions `AplicarFormatoCaracteristica` y `FusionarCaracteristicas`, CSS/JS del admin, cambios en el modal de categorías y en
  crear/editar producto. Estaba pasando las rutas cuando se cortó.

### Falta
1. **Terminar la lectura en las tablas nuevas** y que nada lea/escriba `columna_N` (salvo `MigracionCaracteristicas`):
   `ProductoController` (Excel import/export), vistas `carrito-productos`, `productos-clientes-listado`, `modalCarrito`,
   `producto`, `productoBmh`, `productoRodamiento`; filtros del catálogo con parámetro estable `c[ID]=valor`
   (redirigir los `columna_N` viejos).
2. **Admin → Características**: buscador, uso (categorías/productos), editar nombre/orden/formato con ejemplo en vivo
   («escribís 12 → se guarda 12V»), aplicar formato a valores ya cargados mostrando cuántos cambian, eliminar con impacto,
   fusionar duplicados.
3. **Modal de categorías**: sin los 78 checkboxes; selector con buscador (estilo partes relacionadas) con chips, crear
   característica rápido, «ver/traer de otra categoría o producto», y al final «características sin usar».
4. **Crear/editar producto**: sección Características con «Usar características de la categoría», «Copiar de otro producto»
   (con o sin valores), agregar existente o crear nueva, validación en vivo con el formato; al cambiar de categoría no borrar
   valores, avisar.
5. Tests: actualizar `CatalogoTest`, `CatalogoParecidosTest`, `BuscadorHeaderTest`, `BusquedaPorEquivalenciaTest`,
   `ExcelProductosTest`, `CaracteristicaDashboardTest`, `AssistantConversationTest`, `LegacyCatalogMappingTest`; suite completa.
6. Verificar cada pantalla en navegador (1366 y 390).
7. **Decidir después**: migración que borre `columna_N` y las tablas viejas (sólo cuando lo anterior esté probado);
   fusionar los 2 pares casi duplicados («DISTANCIA ENTRE ORIFICIOS - [D1] (MM)» / «(D1) MM» y
   «DIAMETRO ORIFICIOS - [DA] (MM)» / «-(DA) MM»); unos pocos valores raros en numéricas quedaron como texto
   (p. ej. VOLTAJE «380VAC 3-FASES 50HZ», «.»).
- No tocar `atributos_producto` / `producto_atributo` ni `NormalizeCatalogAttributesCommand` (son de la capa de IA de Santiago).

---

## 3. Otros pendientes del rediseño

- **Sacar las rutas viejas de Breeze** (`routes/auth.php`: `POST login`, `forgot-password`, `reset-password`): siguen vivas y
  `POST /login` parece roto. El usuario ya pidió sacarlas.
- **Páginas del Figma que faltan**: Carrito (nodo 3147:2500), Mis datos (3147:3439, con «Margen de reventa» general y por
  categoría; hoy el form de reventa está en productos-zona-privada), listado de productos de la zona privada (3173:6308),
  detalle de novedad; header/migas del listado y la ficha públicos.
- **Ofertas en la Zona de Clientes**: en discusión (las ofertas públicas ya están: `/ofertas`, campo `productos.oferta`).
- Turbo: sumar `@section('turbo', true)` en las páginas cuyo JS ya sea compatible.
- osole/ui: ícono opcional en «Leer más» de post-card; evaluar `whatsapp-button.v1` y `contact-form`/`contact-list` para Contacto.
- Rutas del vendedor (`vendedor.*`) están comentadas en `routes/web.php`: definir si se usan.
- Tests conocidos que fallan desde antes: `AssistantCartTest::test_una_correccion_posterior_revoca_la_confirmacion_del_carrito`
  y `LegacyCatalogMappingTest`.

---

## 4. Hecho en esta etapa (resumen)

Rediseño público (home, nosotros, categorías, novedades, contacto con mapa, ofertas, historial, lista de precios, header,
footer pixel perfect, Turbo), admin rediseñado con osole/ui (login, sidebar, todas las pantallas, modales), Metadatos SEO como
Conelec con imagen para compartir y sitemap, Contacto en el admin (Mensajes / Contenido, destinatarios, mails con formato BMH),
anti-bots en lugar de reCAPTCHA (honeypot, token de tiempo firmado, origen, rate limits), Asesor IA con el estilo del rediseño,
PDF de pedidos rediseñado, año del footer automático y arreglos de seguridad (Zona de Clientes por `?id=`, `/datos-kc`).

**Ya en producción** (rama `new-products-schema`, `88704ef`): arreglo de la subida pública de `/datos-kc` (sólo planillas,
extensión decidida por el servidor, límite de envíos) y `.htaccess` que impide ejecutar scripts en `public_html/archivos`.

---

## 5. Para subir el rediseño a producción (cuando toque)

- El hosting tiene que pasar a **PHP 8.4** (hoy la web corre en 8.2). `composer install` con Laravel 13.
- Correr las migraciones del punto 1.
- `npm run build` y subir `public/build` a **las dos carpetas** (`~/bmh/public/build` y `~/public_html/build`).
- En producción `public_path()` es `~/bmh/public` (no se sirve); las subidas a `archivos/` usan la ruta relativa
  (`public_html/archivos`). Revisar cualquier código nuevo que use `public_path()` para archivos subidos.
- `.env`: ya no hace falta reCAPTCHA. Revisar `MAIL_*` (avisos y acuses de Contacto), `AI_ACCESS_MODE=allowlist` y
  `AI_ALLOWED_CUSTOMER_IDS=6065` (beta del asesor sólo para `demo`).
- Producción tiene rutas y config cacheadas: `php artisan route:cache`, `config:cache`, `view:clear` después de subir.
- Copiar `public/archivos/.htaccess` a `public_html/archivos` (ya está). `public_html/imagenes/.htaccess` (WebP) ya está en
  producción y no se versiona.
- Acceso SSH por clave: `ssh bmh` (clave `~/.ssh/bmh_cpanel`, autorizada en cPanel). Nunca subir backups al servidor.
