# BMH — Integración de Asistente IA

Aplicación Laravel para el catálogo y zona de clientes de BMH, ampliada con un
asesor técnico asistido por IA. El sistema busca repuestos por código,
equivalencias, atributos técnicos, historial del cliente e imágenes, pero evita
afirmar coincidencias, precios o stock cuando los datos no son suficientes.

## Qué incluye actualmente

- Asistente conversacional con proveedores `mock`, Gemini y OpenAI.
- Búsqueda híbrida determinística y semántica del catálogo.
- Análisis de imágenes con límites, optimización y metadatos.
- Desambiguación de productos duplicados por atributos relevantes.
- Memoria estructurada y derivación a un asesor humano.
- Auditoría de herramientas, consumo y feedback.
- Base del asistente separada de la base legacy.
- Índice materializado de búsqueda, reconstruible e idempotente.
- Auditoría reproducible de calidad de datos.
- Normalización aditiva de los 78 slots técnicos legacy, sin pérdida de datos.

## Requisitos

- PHP y Composer.
- Node.js y npm.
- MySQL 8 o compatible.
- Extensiones PHP requeridas por Laravel y procesamiento de imágenes.

## Instalación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Crear las bases `bmh_legacy` y `bmh_ai`, completar las credenciales locales en
`.env` y ejecutar:

```bash
php artisan migrate
npm run build
php artisan serve
```

Para desarrollar sin credenciales externas:

```dotenv
AI_PROVIDER=mock
AI_API_KEY=
```

## Preparación del catálogo

```bash
php artisan bmh:data-audit
php artisan bmh:normalize-catalog-attributes --dry-run
php artisan bmh:normalize-catalog-attributes
php artisan bmh:normalize-catalog-attributes --verify-only
php artisan bmh:catalog-sync
```

La normalización es aditiva: copia los atributos a tablas relacionadas y
conserva `productos.columna_1..columna_78` para verificación y rollback. No se
deben eliminar las columnas legacy hasta completar escritura dual, observación
en producción y una restauración comprobada del backup.

El procedimiento para trasladar la base real está en
[`docs/normalizacion-catalogo-runbook.md`](docs/normalizacion-catalogo-runbook.md).

## Arquitectura

- `mysql_legacy`: catálogo, clientes, pedidos y operación existente.
- `mysql_ai`: conversaciones, mensajes, adjuntos, auditoría e índice del asesor.
- `app/Domain/Catalog`: adaptación y semántica del catálogo legacy.
- `app/Domain/Search`: ranking, filtros y desambiguación.
- `app/Services/Ai`: proveedores, orquestación, memoria y herramientas.
- `resources/prompts`: prompts versionados.

La IA no consulta la base directamente. Sólo recibe resultados estructurados de
servicios y herramientas controladas por la aplicación.

## Seguridad

- Nunca versionar `.env`, dumps SQL, API keys, archivos de clientes o uploads.
- Usar un usuario de sólo lectura para `mysql_legacy` desde el asesor.
- Mantener `APP_DEBUG=false` en producción.
- Verificar backups mediante restauración antes de cualquier migración real.

Documentación adicional:

- [`README-ASESOR.md`](README-ASESOR.md)
- [`docs/architecture.md`](docs/architecture.md)
- [`docs/security.md`](docs/security.md)
- [`docs/data-quality-report.md`](docs/data-quality-report.md)
- [`docs/normalizacion-catalogo-ejecucion-local-2026-08-14.md`](docs/normalizacion-catalogo-ejecucion-local-2026-08-14.md)

## Pruebas

```bash
php artisan test
npm run build
```

Las pruebas de integración del catálogo requieren acceso a una copia local de la
base legacy configurada en `.env`.
