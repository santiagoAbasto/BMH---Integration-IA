# BMH — Plataforma comercial con asesor técnico asistido por IA

Aplicación web para gestionar un catálogo técnico de repuestos, la operación comercial de clientes y la identificación guiada de productos.

## Descripción

BMH centraliza un catálogo técnico, productos, contenido, clientes, carrito, pedidos e historial de compras. Está orientado a una empresa de repuestos eléctricos para automotores y a sus clientes, vendedores y administradores.

El proyecto amplía una aplicación Laravel con un asesor conversacional dentro de la Zona de Clientes. Interpreta texto, códigos y fotografías, consulta el catálogo, compara candidatos y calcula precios según las reglas vigentes. El módulo está implementado y probado; el repositorio no permite confirmar que la versión actual esté desplegada en producción.

## Funcionalidades principales

- Catálogo público y privado con búsqueda corregida, filtros, paginación, ofertas, equivalencias y fichas de producto.
- Registro e inicio de sesión para clientes, acceso administrativo y validación del estado de la cuenta.
- Zona de Clientes con precios personalizados, historial y repetición de pedidos.
- Carrito, cálculo de descuentos e impuestos, generación de pedidos y notificaciones por correo.
- Panel administrativo para productos, categorías, atributos, clientes, pedidos, contenido, apariencia, biblioteca de imágenes, descargas y listas de precios.
- Importación y exportación de datos comerciales mediante hojas de cálculo.
- Equivalencias, aplicaciones y piezas relacionadas del catálogo.
- Asesor conversacional con comparación de candidatos, feedback y derivación humana.
- Búsqueda por código, equivalencia, descripción, atributos e historial del cliente.
- Análisis de imágenes y memoria estructurada de hechos confirmados e inferidos.
- Sincronización idempotente de un índice de búsqueda y comandos de auditoría, normalización del catálogo y optimización de imágenes en WebP.
- API autenticada para conversaciones, adjuntos, búsqueda y comparación, con respuestas JSON y streaming mediante SSE.

## Arquitectura

El núcleo comercial es un monolito Laravel con MVC y vistas Blade. El asesor añade una capa de dominio y servicios: React se integra mediante un widget aislado con Shadow DOM y también dispone de una página Inertia. Sus controladores delegan la búsqueda, desambiguación, precios, memoria y coordinación de IA en servicios especializados.

Interfaces y repositorios desacoplan el dominio del esquema heredado; una capa semántica traduce sus atributos posicionales. El chat usa REST y SSE. No se encontraron microservicios ni arquitectura dirigida por eventos.

```text
Cliente / Administrador
          |
   Blade + Bootstrap
   React + Inertia (asesor)
          |
 Laravel: rutas, controladores y middleware
          |
 Dominio comercial + orquestador de conversación
       /                         \
MySQL legacy             MySQL del asesor
catálogo y pedidos       conversaciones e índice
       \                         /
        Gemini / OpenAI / Mock
```

La cola predeterminada es síncrona y los correos se envían en la solicitud. No hay workers ni tareas programadas activas en `app/Console/Kernel.php`; la sincronización periódica del catálogo solo está documentada como operación de despliegue.

## Stack tecnológico

### Backend

- PHP `^8.1` y Laravel 10.48.12.
- Eloquent ORM, Query Builder, validación mediante Form Requests y sesiones de Laravel.
- Laravel Sanctum 3.3.3 disponible para autenticación de API; el asesor reutiliza la sesión web existente.
- Guzzle 7, PhpSpreadsheet 2.1.0 y `codersfree/shoppingcart` 1.x.

### Frontend

- Blade, Bootstrap 5.2.3, JavaScript y Alpine.js 3.13.7 para la aplicación comercial.
- React 19.2.8, TypeScript 7.0.2 e Inertia para el asesor.
- Tailwind CSS 3.4.1 encapsulado en el Shadow DOM del widget.
- Vite 5.4.21 para compilación de recursos.

### Base de datos

- MySQL mediante tres conexiones lógicas: aplicación principal, lectura del catálogo legacy y datos propios del asesor.
- Índice materializado con columnas indexadas y búsqueda `FULLTEXT`.

### Testing

- PHPUnit 10.5.20.
- Pruebas unitarias y de integración/feature para autenticación, aislamiento de clientes, búsquedas, ranking, visión, precios, adjuntos, streaming, proveedores y seguridad del asesor.
- Laravel Pint disponible como formateador PHP.

### DevOps / Infraestructura

- Despliegue manual documentado para hosting Linux/cPanel.
- Compilación de assets con npm y cachés de configuración, rutas y vistas de Laravel.
- No se encontraron Docker, Docker Compose, pipelines CI/CD, Kubernetes, Helm ni Terraform.

### IA

- Abstracción `AiProviderInterface` con proveedores Mock, Google Gemini y OpenAI.
- Modelos configurables por entorno para conversación, extracción rápida, visión y embeddings.
- Structured outputs, function calling, análisis visual, prompts versionados y fallback configurable.

### Integraciones

- Google Gemini y OpenAI para tareas asistidas por IA.
- Google reCAPTCHA en formularios de contacto.
- API Georef de Argentina para provincias y localidades durante el pedido.
- Correo mediante Laravel Mail.

## Modelo de datos

Eloquent representa clientes, administradores, productos, categorías, imágenes, pedidos, impuestos y contenido. Los productos se relacionan con usos, medidas, características, equivalencias, aplicaciones y otras piezas. La actualización de estas relaciones usa transacciones.

El esquema normalizado agrega atributos técnicos con claves únicas e índices. La base del asesor guarda conversaciones, mensajes, adjuntos, candidatos, contexto, feedback, derivaciones, auditoría e índice de búsqueda. Usa claves foráneas internas, pero referencia clientes y productos legacy sin constraints entre bases. No se encontraron soft deletes ni multi-tenancy; las conversaciones se aíslan por propietario.

## Seguridad

- La aplicación usa sesiones y guards separados para clientes y administradores, con middleware por rol. El asesor obtiene siempre el cliente de la sesión.
- Las rutas del asesor requieren autenticación, protección CSRF y un límite de 30 solicitudes por minuto por cliente; los intentos de login también tienen limitación.
- Las conversaciones y adjuntos se consultan con el identificador del cliente autenticado. Un cliente no puede solicitar el historial o los archivos de otro mediante un identificador enviado por el frontend.
- Las imágenes se validan por tamaño, MIME y contenido, se guardan en un disco privado y se entregan mediante una ruta autorizada. Las versiones procesadas eliminan metadatos EXIF.
- La IA no ejecuta SQL. Solo puede solicitar herramientas registradas con argumentos controlados; los precios y la selección final de productos permanecen en lógica determinística.
- Las consultas revisadas usan Eloquent, Query Builder y bindings. También hay SQL raw y columnas construidas desde mapas internos, que deben mantenerse fuera del control del usuario.
- Las llamadas, candidatos, proveedor, modelo, latencia y versión del prompt se registran en tablas de auditoría, sin almacenar razonamiento interno del modelo.
- La configuración usa variables de entorno para credenciales de base de datos, correo e IA. Se detectó una credencial de reCAPTCHA incrustada en código heredado; debe rotarse y trasladarse al entorno antes de publicar el repositorio.
- Varias vistas Blade heredadas renderizan HTML administrable sin escape. Esto requiere confiar en los usuarios administrativos o incorporar sanitización. El middleware CSP existe, pero está desactivado en el grupo web.

## APIs e integraciones

La API del asesor ofrece conversaciones, mensajes, streaming SSE, adjuntos, feedback y derivación. La API de catálogo expone categorías, detalle, búsqueda y comparación. Ambas requieren cliente autenticado y aplican rate limiting.

Gemini y OpenAI se consumen detrás de una interfaz común; Mock permite ejecutar el flujo sin credenciales externas. La aplicación usa reCAPTCHA, Georef y correo. Aunque existen dependencias y referencias visuales de Mercado Pago, no se verificó la creación activa de preferencias de pago.

## Inteligencia Artificial

La IA interpreta texto y fotografías, extrae datos estructurados y redacta. El orquestador acumula contexto, dirige la consulta, ejecuta herramientas autorizadas y persiste el resultado. La memoria diferencia hechos `confirmed`, `inferred` y `unknown`.

La selección del producto, el ranking, la desambiguación, el cálculo de precios y la decisión de no afirmar stock son determinísticos. El modelo no recibe acceso directo a la base ni calcula importes. La búsqueda combina coincidencias exactas, equivalencias, atributos, texto, visión e historial; la similitud visual tiene menor peso que un código exacto.

No se encontró RAG documental ni base vectorial. El índice actual usa MySQL `FULLTEXT`; la búsqueda semántica y los modelos de embeddings están configurados, pero la feature está desactivada por defecto. Hay límites de imágenes, tool calls y tokens por conversación, fallback opcional entre proveedores, validación de salidas estructuradas, auditoría y derivación humana ante baja confianza o conflictos.

## Testing y calidad

La suite BMH fue ejecutada con `php -d memory_limit=512M vendor/bin/phpunit --testsuite=BMH` en PHP 8.5.10: **411 pruebas y 2.730 aserciones correctas**. La ejecución mostró avisos de deprecación procedentes de dependencias compatibles con versiones anteriores de PHP, sin fallos de la suite. El límite de 512 MB permite ejecutar en un mismo proceso las pruebas del optimizador que generan imágenes grandes.

Las pruebas cubren HTTP, aislamiento, prompt injection, privacidad, uploads, streaming, proveedores, búsqueda, ranking, visión y precios. Requieren una copia de solo lectura del catálogo y una base desechable. No se encontraron pruebas E2E, de carga ni un pipeline de calidad.

```bash
php -d memory_limit=512M vendor/bin/phpunit --testsuite=BMH
```

## DevOps y despliegue

El repositorio documenta un despliegue manual en cPanel: configurar el entorno y la base del asesor, migrar, sincronizar el índice, compilar assets y activar las cachés de Laravel. `public/build` no se versiona.

El proceso contempla una conexión legacy de solo lectura, flags de debug desactivados, almacenamiento privado y ejecución periódica de `bmh:catalog-sync`. No hay automatización de despliegue, contenedores, infraestructura como código o backups gestionados por el repositorio.

## Instalación local

Requisitos: PHP `^8.1` (se recomienda 8.2/8.3), Composer 2, Node.js 20+ y MySQL/MariaDB. Se necesita un esquema legacy autorizado; los datos reales no deben distribuirse.

1. Instalar dependencias:

   ```bash
   composer install
   npm install --legacy-peer-deps
   ```

2. Crear el archivo de entorno y la clave de aplicación:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Configurar en `.env` las conexiones `mysql`, `mysql_legacy` y `mysql_ai`. Usar un usuario con permiso `SELECT` para `mysql_legacy` y mantener `AI_PROVIDER=mock` si no se usarán servicios externos.

4. Con el esquema legacy preparado y la base del asesor creada, aplicar la migración del módulo y generar su índice:

   ```bash
   php artisan migrate --database=mysql_ai --path=database/migrations/2026_08_10_000100_create_bmh_advisor_tables.php
   php artisan bmh:catalog-sync
   ```

5. Compilar y ejecutar:

   ```bash
   npm run build
   php artisan serve
   ```

La normalización y los datos de demostración se documentan en `docs/normalizacion-catalogo-runbook.md` y `docs/deployment.md`.

## Estructura del proyecto

```text
app/
├── Domain/                 # Catálogo, búsqueda, precios, pedidos e inventario
├── Http/                   # Controladores, middleware y validaciones
├── Models/                 # Modelos comerciales y del asesor
├── Services/Ai/            # Orquestación, proveedores, memoria y tools
└── Console/Commands/       # Sync, auditoría, normalización y diagnóstico
database/
├── migrations/             # Esquema comercial, normalización y asesor
└── seeders/
resources/
├── js/                     # React/Inertia y widget del asesor
├── prompts/                # Prompts versionados
└── views/                  # Vistas Blade públicas y administrativas
routes/                     # Web, autenticación y API del asesor
tests/
├── Feature/Bmh/            # Suite principal del asesor y dominio
└── Feature/Auth/           # Pruebas heredadas de autenticación
docs/                       # Arquitectura, seguridad, datos y despliegue
```

## Decisiones técnicas destacables

- Mantener la base legacy detrás de repositorios y una capa semántica, con credenciales de solo lectura para las consultas del asesor.
- Separar la interpretación asistida por IA de las decisiones determinísticas sobre productos, precios y disponibilidad.
- Materializar un índice reconstruible con hash de contenido y `FULLTEXT` en la base del asesor, sin modificar el catálogo original.
- Reutilizar la autenticación de la Zona de Clientes y aislar el CSS del widget React mediante Shadow DOM.
- Versionar prompts y registrar herramientas, consumo y resultados para poder auditar cada conversación.

## Desafíos técnicos resueltos

- Problema: el catálogo heredado almacena decenas de atributos técnicos en columnas posicionales. Solución: una capa semántica y una normalización aditiva traducen esos slots a atributos tipados sin eliminar los datos originales.
- Problema: códigos duplicados y consultas incompletas pueden producir múltiples piezas válidas. Solución: ranking multifactor y selección de la siguiente pregunta según ganancia de información.
- Problema: incorporar una interfaz Tailwind/React dentro de páginas Bootstrap existentes genera colisiones de estilos. Solución: montar el widget en Shadow DOM y mantener Inertia/React acotado al asesor.

## Estado del proyecto

**Proyecto cliente.** El repositorio contiene un sistema comercial amplio, datos y reglas de dominio heredadas, documentación de despliegue y un módulo de asesor completamente implementado y probado. El estado de despliegue productivo de la versión actual no está verificado desde el código.

## Mi contribución

Diseñé e implementé el asesor sobre la aplicación Laravel: adapté el esquema legacy mediante contratos y repositorios, construí la búsqueda y desambiguación, separé la lógica determinística de los proveedores de IA e integré React en la Zona de Clientes. También implementé controles de acceso, almacenamiento privado, auditoría, sincronización y pruebas de los flujos críticos.

## Resumen para portfolio

### Portfolio Summary

Nombre:
BMH — Plataforma comercial con asesor técnico asistido por IA

Tipo:
Proyecto cliente

Rol:
Full Stack Developer, Software Architect y AI Engineer

Descripción corta:
Plataforma Laravel para catálogo, clientes y pedidos, ampliada con un asesor que identifica repuestos mediante texto, códigos e imágenes y valida los resultados contra datos comerciales reales.

Stack principal:
PHP, Laravel 10, MySQL, Blade, React, TypeScript, Inertia, Tailwind CSS, Bootstrap y Vite.

Arquitectura:
Monolito MVC con capa de dominio, repositorios para el esquema legacy, API REST/SSE y base separada para conversaciones e índice del asesor.

Seguridad:
Sesiones y roles, CSRF, rate limiting, aislamiento por cliente, uploads privados validados y tools de IA sin acceso directo a SQL.

Testing:
PHPUnit 10; suite BMH verificada con 411 pruebas y 2.730 aserciones correctas.

DevOps:
Despliegue manual documentado para Linux/cPanel; sin Docker ni CI/CD verificados.

IA:
Gemini, OpenAI y Mock detrás de una interfaz común; visión, structured outputs, function calling, memoria estructurada y derivación humana.

5 habilidades clave:
1. Laravel
2. PHP
3. MySQL
4. React y TypeScript
5. Integración de IA generativa

3 logros o aportes verificables:

- Implementé una capa de adaptación para consultar un catálogo legacy sin acoplar el asesor a sus columnas posicionales.
- Construí un flujo híbrido que usa IA para interpretar y lógica determinística para buscar, rankear, cotizar y validar resultados.
- Añadí aislamiento por cliente, auditoría, manejo privado de imágenes y una suite automatizada de 411 pruebas.

Nivel de madurez:
Proyecto cliente con funcionalidades comerciales y módulo de IA implementados; despliegue actual del módulo de IA no verificado.

Elementos que no deben publicarse:

- Archivos `.env`, credenciales, claves de proveedores y configuración privada del servidor.
- Dumps SQL, datos de clientes, pedidos, imágenes o cualquier copia de la base operativa.
- Credenciales de demostración y detalles internos de acceso o despliegue.
- La credencial reCAPTCHA detectada en código heredado; debe rotarse y externalizarse.
- El nombre, código o capturas del cliente cuando el acuerdo de confidencialidad no autorice su publicación.

## Evidencia técnica

| Afirmación | Evidencia |
| ---------- | --------- |
| Laravel 10, PHP y dependencias | `composer.json`, `composer.lock` |
| React, TypeScript, Inertia, Tailwind y Vite | `package.json`, `package-lock.json`, `resources/js/` |
| Catálogo, clientes, carrito, pedidos y administración | `routes/web.php`, `app/Http/Controllers/`, `app/Models/` |
| API REST y streaming SSE del asesor | `routes/assistant.php`, `app/Http/Controllers/Api/AssistantController.php` |
| Capa de dominio y repositorios legacy | `app/Domain/`, `app/Providers/BmhAdvisorServiceProvider.php` |
| Gemini, OpenAI, Mock y tools controladas | `app/Services/Ai/`, `config/bmh.php`, `resources/prompts/` |
| Base separada, auditoría e índice FULLTEXT | `database/migrations/2026_08_10_000100_create_bmh_advisor_tables.php` |
| Autenticación, CSRF y rate limiting | `app/Http/Kernel.php`, `app/Providers/RouteServiceProvider.php`, `routes/assistant.php` |
| Seguridad de adjuntos y aislamiento por cliente | `app/Services/Ai/AttachmentService.php`, `app/Models/Ai/AiConversation.php`, `app/Http/Controllers/Api/AssistantController.php` |
| Suite automatizada | `phpunit.xml`, `tests/Feature/Bmh/` |
| Despliegue cPanel y operación local | `docs/deployment.md` |
