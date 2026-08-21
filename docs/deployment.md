# BMH — Instalación y despliegue

---

## 1. Requisitos

| Componente | Versión | Nota |
|---|---|---|
| PHP | 8.2 – 8.5 | Ver §1.1 |
| MySQL / MariaDB | 8.0+ / 10.6+ | Probado en MySQL 9.6 |
| Node | 20+ | Probado en 24 |
| Composer | 2.x | |

### 1.1 PHP 8.5

Este checkout corre Laravel 10.48, que todavía emite `E_DEPRECATED` en PHP 8.5
(nullable implícito). Se hicieron tres cosas, todas reversibles:

1. `composer install --ignore-platform-req=php+` — `nette/schema` declara
   `php 8.1 - 8.3` y bloquea la instalación. Sólo lo usa el renderizador de
   markdown de los mails.
2. `error_reporting(... & ~E_DEPRECATED)` al inicio de `artisan`,
   `public/index.php` y `bootstrap/app.php`, con guarda `PHP_VERSION_ID >= 80400`.
3. `tests/bootstrap.php` enmascara deprecaciones del vendor; las de `app/`
   siguen reportándose (`restrictDeprecations="true"`).

**En PHP 8.2/8.3 nada de esto se activa.** Si el hosting de BMH usa 8.2, se puede
instalar sin `--ignore-platform-req`.

---

## 2. Instalación local

```bash
composer install --ignore-platform-req=php+
```

```bash
npm install --legacy-peer-deps
```

Crear las bases y los usuarios:

```bash
mysql -uroot -e "CREATE DATABASE bmh_legacy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE bmh_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE bmh_ai_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE bmh_app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```bash
mysql -uroot -e "CREATE USER IF NOT EXISTS 'bmh_reader'@'%' IDENTIFIED BY 'bmh_reader_local'; GRANT SELECT ON bmh_legacy.* TO 'bmh_reader'@'%'; CREATE USER IF NOT EXISTS 'bmh_app'@'%' IDENTIFIED BY 'bmh_app_local'; GRANT ALL ON bmh_legacy.* TO 'bmh_app'@'%'; GRANT ALL ON bmh_ai.* TO 'bmh_app'@'%'; CREATE USER IF NOT EXISTS 'bmh_test'@'%' IDENTIFIED BY 'bmh_test_local'; GRANT ALL ON bmh_app_test.* TO 'bmh_test'@'%'; GRANT ALL ON bmh_ai_test.* TO 'bmh_test'@'%'; GRANT SELECT ON bmh_legacy.* TO 'bmh_test'@'%'; FLUSH PRIVILEGES;"
```

> `bmh_reader` tiene **sólo SELECT**. Es la barrera real que impide que el asesor
> escriba en el catálogo.
>
> `bmh_test` tampoco puede escribir en `bmh_legacy`: los tests heredados usan
> `RefreshDatabase` y sin esa restricción borrarían la copia del catálogo.

Importar el dump:

```bash
mysql -uroot bmh_legacy < bmhbobinajes_bmh.sql
```

Configurar y arrancar:

```bash
cp .env.example .env && php artisan key:generate
```

```bash
php artisan migrate --database=mysql_ai --path=database/migrations/2026_08_10_000100_create_bmh_advisor_tables.php
```

```bash
php artisan bmh:catalog-sync
```

```bash
php artisan db:seed --class=BmhDemoSeeder
```

```bash
npm run build && php artisan serve
```

Entrar a `/login` con `demo` / `demo1234` y después a **`/asesor`**.

---

## 3. Variables de entorno

Ver [`.env.example`](../.env.example). Las del asesor:

```env
DB_LEGACY_DATABASE=bmh_legacy
DB_LEGACY_USERNAME=bmh_reader
DB_LEGACY_PASSWORD=

DB_AI_DATABASE=bmh_ai
DB_AI_USERNAME=bmh_app
DB_AI_PASSWORD=

AI_ENABLED=true
AI_VISION_ENABLED=true
CUSTOMER_HISTORY_ENABLED=true
SEMANTIC_SEARCH_ENABLED=false
AI_DEBUG=false
AI_FALLBACK_ENABLED=false

AI_PROVIDER=mock
AI_API_KEY=

BMH_STOCK_SEMANTICS_VERIFIED=false
```

---

## 4. Conectar Gemini

1. Crear la key en Google AI Studio.
2. En `.env`:

```env
AI_PROVIDER=gemini
AI_API_KEY=tu-key
AI_CHAT_MODEL=gemini-2.5-flash
AI_VISION_MODEL=gemini-2.5-flash
AI_FAST_MODEL=gemini-2.5-flash-lite
AI_EMBEDDING_MODEL=text-embedding-004
```

3. `php artisan config:clear`

No hay que reconstruir nada. El badge del header pasa de `AI: MOCK` a `AI: LIVE`.

Si la key falta o es inválida, el manager vuelve a `mock` y lo registra en el
log. La Zona de Clientes no se cae.

## 5. Conectar OpenAI

```env
AI_PROVIDER=openai
AI_API_KEY=sk-...
AI_CHAT_MODEL=gpt-4.1
AI_VISION_MODEL=gpt-4.1
AI_FAST_MODEL=gpt-4.1-mini
AI_EMBEDDING_MODEL=text-embedding-3-small
```

### Fallback entre proveedores

```env
AI_FALLBACK_ENABLED=true
AI_FALLBACK_PROVIDER=openai
```

Desactivado por defecto a propósito: cambiar de proveedor cuesta plata y no debe
pasar solo.

---

## 6. De local a producción

### 6.1 Base de datos

En producción `bmh_legacy` **es** la base real de BMH. Lo importante es el
usuario:

```sql
CREATE USER 'bmh_reader'@'localhost' IDENTIFIED BY '<password>';
GRANT SELECT ON bmhbobinajes_bmh.* TO 'bmh_reader'@'localhost';
```

`DB_LEGACY_DATABASE=bmhbobinajes_bmh` con ese usuario. La base del asesor
(`bmh_ai`) va aparte y sí necesita escritura.

**No se corre ninguna migración contra la base legacy.** Las migraciones del
asesor se aplican sólo a `mysql_ai`:

```bash
php artisan migrate --database=mysql_ai --path=database/migrations/2026_08_10_000100_create_bmh_advisor_tables.php --force
```

### 6.2 Front controller

`public/index.php` detecta el layout: en el hosting de BMH el código vive en un
directorio hermano `bmh/` y `public/` es el document root; en un checkout local
la app está un nivel arriba. Funciona en los dos sin tocar nada.

### 6.3 Assets

```bash
npm ci --legacy-peer-deps && npm run build
```

`public/build` está en `.gitignore`: hay que construir en el deploy o subir el
build.

### 6.4 Checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `AI_DEBUG=false` — expone intención, modelo y tokens
- [ ] `bmh_reader` con SELECT solamente
- [ ] `storage/app/ai-private` fuera del document root y escribible
- [ ] `php artisan config:cache route:cache view:cache`
- [ ] `php artisan bmh:catalog-sync` en cron (diario o al actualizar el catálogo)
- [ ] `AI_API_KEY` en el entorno del servidor, nunca en el repo
- [ ] Rotar la contraseña de producción que estaba en el `.env` local

### 6.5 Cron

```cron
0 3 * * * cd /path/bmh && php artisan bmh:catalog-sync >> storage/logs/catalog-sync.log 2>&1
```

---

## 7. Tests

```bash
vendor/bin/phpunit --testsuite=BMH
```

76 tests, 361 assertions.

> Usar `vendor/bin/phpunit` y no `php artisan test`: en PHP 8.5 el printer de
> Collision marca tests como `DEPR` por deprecaciones del vendor y en algunos
> casos aborta. Los tests pasan igual.

La suite completa (`vendor/bin/phpunit`) incluye los tests heredados de Breeze
(`tests/Feature/Auth`, `ExampleTest`, `ProfileTest`), que **fallan desde antes de
este trabajo**: dependen de un esquema que las 28 migraciones del repo no
reproducen (falta `imagenes`, entre otras). Están aislados —el usuario de test no
puede escribir en `bmh_legacy`— pero arreglarlos queda fuera de alcance.

---

## 8. Reutilizar el núcleo para WhatsApp

El orquestador no sabe nada de HTTP: recibe `(conversación, cliente, texto,
adjuntos)` y devuelve un payload. Para WhatsApp haría falta:

1. Un webhook que reciba el mensaje.
2. Resolver el `CustomerAccount` por teléfono (hoy es por sesión) — es el único
   punto real de trabajo, y hay que pensar la autenticación con cuidado: un
   número de teléfono no es una credencial.
3. Traducir el payload a mensajes de WhatsApp (las cards no existen; hay que
   degradar a texto + imagen).

Nada del dominio, la búsqueda, el pricing ni las tools cambia.
