# Rediseño BMH — estado y pendientes

Rama `rediseno` (Laravel 13, PHP 8.4, osole/ui). Actualizado: 2026-11-16.
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
- `2026_11_16_000001_quitar_columna_caracteristicas_de_productos` — saca `productos.caracteristicas` (texto viejo vacío que
  tapaba la relación); si tuviera datos la renombra a `caracteristicas_texto`.
- `2026_11_16_000002_borrar_caracteristicas_viejas` — **borra con sus datos** las 78 `columna_N` de `productos` y
  `categorias`, las columnas fijas de rodamientos y las tablas `categoria_caracteristica` / `producto_caracteristica`
  (decisión del usuario; el respaldo es la base de producción). No corre si `caracteristica_producto` está vacía.
- `2026_11_16_000003_categorias_nombre_sin_distinguir_mayusculas` — `categorias.nombre` de `utf8mb3_bin` a
  `utf8mb4_unicode_ci` (las búsquedas por nombre de categoría distinguían mayúsculas).
- `2026_11_16_000004_oferta_con_alcance` — `productos.oferta` pasa de sí/no a alcance (`publica` | `clientes` | null): las
  ofertas que ya había quedan «para todos».
- `2026_11_16_000005_descuento_de_la_oferta` — `productos.oferta_tipo` (`porcentaje` | `precio`) y `oferta_valor`: el
  precio de oferta que ven los clientes con sesión (la zona pública nunca muestra precios ni el porcentaje).
- `2026_11_17_000001_trabajamos_con_y_seccion_nosotros_de_la_home` — tablas `marcas_aliadas` (logos de «Trabajamos con»,
  arranca con los 4 del Figma) y `home_nosotros` (bloque Nosotros de la Home, cargado desde `imagenes` sector
  `nosotros-slider`). Los logos y fotos que se suben desde el admin van a `public_path('imagenes')`: en producción
  tiene que apuntar a `public_html/imagenes`.
- `2026_11_17_000002_titulos_editables_de_nosotros` — `nosotros_contenido.historia_titulo` y `valores_titulo`.
  Ojo: el admin viejo guardaba el texto de la historia en `info_home`; el nuevo guarda en `info`, que es lo que lee la página.

**En producción el orden importa:** correr todas juntas con `php artisan migrate` (primero la 000010 migra los datos,
después la 000002 del 16/11 borra lo viejo). Hacer el respaldo de la base de producción antes.

Para importar el backup parcial en una base que ya tenga el resto: `mysql -u USUARIO BASE < database/backups/rediseno-2026-11-12-sin-datos-personales.sql`
(reemplaza esas 7 tablas).

---

## 2. Características de productos — **hecho**

Antes vivían en 78 columnas sueltas (`categorias.columna_N` = nombre, `productos.columna_N` = valor), en las tablas
`categoria_caracteristica` / `producto_caracteristica` (valor cortado a 50) y con la regla de voltaje/diámetro en el código.

- **Base** (`bedf7d8`): `caracteristicas` (nombre único, orden de 2 caracteres, formato opcional: mayúsculas / numérica /
  unidad), `caracteristica_categoria` y `caracteristica_producto` con FKs. 157 características, 373 vínculos, 18.048 valores,
  0 perdidos.
- **Todo el sitio lee las tablas nuevas**: catálogo, filtros (`c[ID]=valor`; los links viejos `columna_N` redirigen),
  búsqueda, equivalencias, parecidos, fichas, zona privada, Excel y la capa de IA. Nada lee ni escribe `columna_N`
  (salvo `MigracionCaracteristicas` y el comando `bmh:normalize-catalog-attributes` de la capa de IA, que no se tocó).
- **Admin** (`882ca3c`):
  - *Características*: buscador, orden, «Todas / Sin usar», formato con ejemplo en vivo («12,50 → se guarda 12.5V»),
    aplicar el formato a los valores cargados mostrando cuántos cambian, borrar con impacto y fusionar duplicadas.
  - *Categorías*: selector con buscador (chips, teclado), crear al vuelo, traer de otra categoría o producto y las sin usar.
  - *Productos*: «Usar las de la categoría», copiar de otro producto (con o sin valores), crear al vuelo, validación en vivo
    y aviso de las que la categoría no usa (no se ven en el sitio) con «Sumarla a la categoría».

### Para decidir
- **Duplicadas**: «DISTANCIA ENTRE ORIFICIOS - [D1] (MM)» / «(D1) MM» y «DIAMETRO ORIFICIOS - [DA] (MM)» / «-(DA) MM»: se
  fusionan desde el admin (botón «Fusionar»).
- Valores raros en numéricas que quedaron como texto (p. ej. VOLTAJE «380VAC 3-FASES 50HZ», «.»): revisarlos.
- Hay productos con valores en características que su categoría no usa (no se ven en el sitio): el admin los marca.

---

## 3. Otros pendientes del rediseño

- **Páginas del Figma que faltan**: Carrito (nodo 3147:2500), Mis datos (3147:3439, con «Margen de reventa» general y por
  categoría; hoy el form de reventa está en productos-zona-privada), listado de productos de la zona privada (3173:6308),
  detalle de novedad; header/migas del listado y la ficha públicos.
- **Ofertas**: listas. El admin elige alcance (para todos / sólo Zona de Clientes) y un descuento opcional (% o precio de
  oferta); en la Zona de Clientes la tarjeta muestra precio de antes tachado, el de ahora y el −%.
- Turbo: sumar `@section('turbo', true)` en las páginas cuyo JS ya sea compatible.
- osole/ui: ícono opcional en «Leer más» de post-card; evaluar `whatsapp-button.v1` y `contact-form`/`contact-list` para Contacto.
- Rutas del vendedor (`vendedor.*`) están comentadas en `routes/web.php`: definir si se usan.
- Test conocido que falla desde antes: `AssistantCartTest::test_una_correccion_posterior_revoca_la_confirmacion_del_carrito`
  (y `ExampleTest`, porque la base de tests no tiene todas las tablas). `phpunit` sin argumentos avisa que `tests/Feature/Bmh`
  está en dos suites de `phpunit.xml`: correr `vendor/bin/phpunit tests/Feature/Bmh`.
- **Animaciones**: verificadas también en la Zona de Clientes con sesión real (Lista de precios, Historial, Mis datos y
  Carrito: nada queda invisible). AOS sigue cargado para tres pantallas viejas (carrito, registro y lista de productos del detalle); sacarlo
  cuando pasen al rediseño. La foto del hero trae un texto tenue («CALIDAD QUE MANTIENE TU MUNDO EN MOVIMIENTO») incrustado:
  conviene reemplazarla por una sin texto.

---

## 4. Hecho en esta etapa (resumen)

Rediseño público (home, nosotros, categorías, novedades, contacto con mapa, ofertas, historial, lista de precios, header,
footer pixel perfect, Turbo), admin rediseñado con osole/ui (login, sidebar, todas las pantallas, modales), Metadatos SEO como
Conelec con imagen para compartir y sitemap, Contacto en el admin (Mensajes / Contenido, destinatarios, mails con formato BMH),
anti-bots en lugar de reCAPTCHA (honeypot, token de tiempo firmado, origen, rate limits), Asesor IA con el estilo del rediseño,
PDF de pedidos rediseñado, año del footer automático, características relacionales con su admin, animaciones (entrada del
hero y apariciones al scrollear, como Conelec/Rapivac, con Turbo y «reducir movimiento») y arreglos de seguridad (Zona de
Clientes por `?id=`, `/datos-kc`, rutas viejas de Breeze y `/profile` fuera).

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
